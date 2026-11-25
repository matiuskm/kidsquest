<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Exceptions\AlreadyReviewed;
use App\Exceptions\MissingPhoto;
use App\Models\ExamResult;
use App\Models\Redemption;
use App\Models\Submission;
use App\Support\ExamGrade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Keputusan parent atas submission dan klaim hadiah. Idempotent: hanya dari `pending`. */
final class ReviewService
{
    public function __construct(private PointsLedger $ledger, private Notifier $notifier) {}

    /** @throws AlreadyReviewed|MissingPhoto */
    public function approveSubmission(Submission $submission): void
    {
        DB::transaction(function () use ($submission) {
            $s = $this->lockPending($submission);

            if (! $s->photo_path || ! Storage::disk('local')->exists($s->photo_path)) {
                throw new MissingPhoto;
            }

            $s->update(['status' => ReviewStatus::Approved, 'reviewed_at' => now()]);
            $this->ledger->earn($s->child, $s->task->points, $s);
            $this->tell($s->child, 'Poin masuk! ⭐', "+{$s->task->points} poin untuk “{$s->task->title}”.", 'child.missions');
        });
    }

    /** @throws AlreadyReviewed */
    public function rejectSubmission(Submission $submission, ?string $note = null): void
    {
        DB::transaction(function () use ($submission, $note) {
            $s = $this->lockPending($submission);
            $s->update(['status' => ReviewStatus::Rejected, 'reject_note' => $note, 'reviewed_at' => now()]);
            $this->tell($s->child, 'Coba lagi 🔁', "“{$s->task->title}” ditolak".($note ? ": $note" : '.'), 'child.missions');
        });
    }

    /** Nilai akhir = koreksi parent, atau nilai klaim anak. @throws AlreadyReviewed|MissingPhoto */
    public function approveExam(ExamResult $exam, ?int $score = null): void
    {
        DB::transaction(function () use ($exam, $score) {
            $e = $this->lockPending($exam);

            if (! $e->photo_path || ! Storage::disk('local')->exists($e->photo_path)) {
                throw new MissingPhoto;
            }

            $final = $score ?? $e->claimed_score;
            $grade = ExamGrade::for($final);

            $e->update(['status' => ReviewStatus::Approved, 'final_score' => $final, 'points_awarded' => $grade->points, 'reviewed_at' => now()]);
            $grade->points > 0 && $this->ledger->exam($e->child, $grade->points, $e);
            $this->tell($e->child, 'Nilai disetujui 📝', "{$e->subject}: {$grade->letter}".($grade->points > 0 ? ", +{$grade->points} poin." : '.'), 'child.exams');
        });
    }

    /** @throws AlreadyReviewed */
    public function rejectExam(ExamResult $exam, ?string $note = null): void
    {
        DB::transaction(function () use ($exam, $note) {
            $e = $this->lockPending($exam);
            $e->update(['status' => ReviewStatus::Rejected, 'reject_note' => $note, 'reviewed_at' => now()]);
            $this->tell($e->child, 'Coba lagi 🔁', "Nilai {$e->subject} ditolak".($note ? ": $note" : '.'), 'child.exams');
        });
    }

    /** @throws AlreadyReviewed */
    public function approveRedemption(Redemption $redemption): void
    {
        DB::transaction(function () use ($redemption) {
            $r = $this->lockPending($redemption);
            $r->update(['status' => ReviewStatus::Approved, 'reviewed_at' => now()]);
            $this->tell($r->child, 'Hadiah disetujui 🎁', "“{$r->reward->name}” siap kamu ambil!", 'child.rewards');
        });
    }

    /** Poin dikembalikan sebesar snapshot biaya, tepat sekali. @throws AlreadyReviewed */
    public function rejectRedemption(Redemption $redemption, ?string $note = null): void
    {
        DB::transaction(function () use ($redemption, $note) {
            $r = $this->lockPending($redemption);
            $r->update(['status' => ReviewStatus::Rejected, 'reject_note' => $note, 'reviewed_at' => now()]);
            $this->ledger->refund($r->child, $r->cost, $r);
            $this->tell($r->child, 'Klaim ditolak', "Poin {$r->cost} dikembalikan.".($note ? " $note" : ''), 'child.rewards');
        });
    }

    /** Push dikirim setelah transaksi berhasil commit. */
    private function tell(\App\Models\User $child, string $title, string $body, string $route): void
    {
        DB::afterCommit(fn () => $this->notifier->toChild($child, $title, $body, route($route, absolute: false)));
    }

    /** @template T of Model @param T $model @return T */
    private function lockPending(Model $model): Model
    {
        $locked = $model::whereKey($model->getKey())->lockForUpdate()->firstOrFail();

        return $locked->status === ReviewStatus::Pending ? $locked : throw new AlreadyReviewed;
    }
}
