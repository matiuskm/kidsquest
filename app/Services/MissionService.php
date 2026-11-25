<?php

namespace App\Services;

use App\Enums\MissionStatus;
use App\Enums\Recurrence;
use App\Enums\ReviewStatus;
use App\Exceptions\AlreadySubmitted;
use App\Exceptions\NotDueToday;
use App\Models\Submission;
use App\Models\Task;
use App\Models\User;
use App\Support\Mission;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class MissionService
{
    public function __construct(private TaskSchedule $schedule, private PhotoStore $photos, private Notifier $notifier) {}

    /** @return Collection<int, Mission> */
    public function todayFor(User $child, CarbonImmutable $date): Collection
    {
        $tasks = $this->schedule->dueFor($child, $date);
        $submissions = Submission::where('child_id', $child->id)->whereIn('task_id', $tasks->modelKeys())
            ->orderBy('id')->get()->groupBy('task_id');

        return $tasks
            ->map(function (Task $task) use ($submissions, $date) {
                $key = $this->schedule->periodFor($task, $date)->toDateString();
                $latest = $submissions->get($task->id, collect())
                    ->last(fn (Submission $s) => $s->period_date->toDateString() === $key);

                return new Mission($task, match ($latest?->status) {
                    ReviewStatus::Approved => MissionStatus::Done,
                    ReviewStatus::Pending => MissionStatus::Pending,
                    ReviewStatus::Rejected => MissionStatus::Rejected,
                    default => MissionStatus::Todo,
                }, $latest);
            })
            // Tugas sekali yang sudah selesai hanya tampil di hari disetujuinya.
            ->reject(fn (Mission $m) => $m->task->recurrence === Recurrence::Once
                && $m->status === MissionStatus::Done
                && ! $m->submission->reviewed_at?->isSameDay($date))
            ->values();
    }

    /**
     * @throws NotDueToday|AlreadySubmitted|\App\Exceptions\InvalidImage
     */
    public function submit(User $child, Task $task, UploadedFile $photo, CarbonImmutable $date): Submission
    {
        return DB::transaction(function () use ($child, $task, $photo, $date) {
            User::whereKey($child->id)->lockForUpdate()->first(); // serialisasi dobel-tap

            if (! $this->schedule->dueFor($child, $date)->contains('id', $task->id)) {
                throw new NotDueToday;
            }

            $period = $this->schedule->periodFor($task, $date)->toDateString();

            $taken = Submission::where(['child_id' => $child->id, 'task_id' => $task->id, 'period_date' => $period])
                ->whereIn('status', [ReviewStatus::Pending, ReviewStatus::Approved])->exists();

            if ($taken) {
                throw new AlreadySubmitted;
            }

            $path = $this->photos->store($photo);

            try {
                $submission = Submission::create([
                    'task_id' => $task->id,
                    'child_id' => $child->id,
                    'period_date' => $period,
                    'photo_path' => $path,
                    'status' => ReviewStatus::Pending,
                ]);

                DB::afterCommit(fn () => $this->notifier->toParent(
                    'Misi dikirim 📷',
                    "{$child->name} mengirim “{$task->title}”.",
                    route('parent.dashboard', absolute: false),
                ));

                return $submission;
            } catch (Throwable $e) {
                $this->photos->delete($path);

                throw $e;
            }
        });
    }
}
