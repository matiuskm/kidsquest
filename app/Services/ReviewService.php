<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Exceptions\AlreadyReviewed;
use App\Exceptions\MissingPhoto;
use App\Models\Submission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Keputusan parent atas submission (dan klaim hadiah, ditambah di Task 7). Idempotent: hanya dari `pending`. */
final class ReviewService
{
    public function __construct(private PointsLedger $ledger) {}

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
        });
    }

    /** @throws AlreadyReviewed */
    public function rejectSubmission(Submission $submission, ?string $note = null): void
    {
        DB::transaction(function () use ($submission, $note) {
            $this->lockPending($submission)->update([
                'status' => ReviewStatus::Rejected,
                'reject_note' => $note,
                'reviewed_at' => now(),
            ]);
        });
    }

    private function lockPending(Submission $submission): Submission
    {
        $locked = Submission::whereKey($submission->id)->lockForUpdate()->firstOrFail();

        return $locked->status === ReviewStatus::Pending ? $locked : throw new AlreadyReviewed;
    }
}
