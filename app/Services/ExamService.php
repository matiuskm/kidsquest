<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Exceptions\AlreadySubmitted;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ExamService
{
    public function __construct(private PhotoStore $photos, private Notifier $notifier) {}

    /** @throws AlreadySubmitted|\App\Exceptions\InvalidImage */
    public function submit(User $child, string $subject, ?int $score, ?string $letter, UploadedFile $photo): ExamResult
    {
        return DB::transaction(function () use ($child, $subject, $score, $letter, $photo) {
            User::whereKey($child->id)->lockForUpdate()->first(); // serialisasi dobel-tap

            // Satu ulangan per mata pelajaran menunggu keputusan; sekaligus menahan dobel-tap dan kiriman berulang.
            if (ExamResult::where(['child_id' => $child->id, 'subject' => $subject, 'status' => ReviewStatus::Pending])->exists()) {
                throw new AlreadySubmitted(__('Nilai :subject masih menunggu ortu.', ['subject' => $subject]));
            }

            $path = $this->photos->store($photo);

            try {
                $exam = ExamResult::create([
                    'child_id' => $child->id,
                    'subject' => $subject,
                    'claimed_score' => $score,
                    'claimed_letter' => $letter,
                    'photo_path' => $path,
                    'status' => ReviewStatus::Pending,
                ]);

                DB::afterCommit(fn () => $this->notifier->toParent(
                    'Nilai dikirim 📝',
                    ':name mengirim nilai :subject.',
                    route('parent.dashboard', absolute: false),
                    ['name' => $child->name, 'subject' => $subject],
                ));

                return $exam;
            } catch (Throwable $e) {
                $this->photos->delete($path);

                throw $e;
            }
        });
    }
}
