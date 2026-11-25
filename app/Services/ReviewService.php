<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Exceptions\AlreadyReviewed;
use App\Exceptions\MissingPhoto;
use App\Models\Redemption;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Keputusan parent atas submission dan klaim hadiah. Idempotent: hanya dari `pending`. */
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

    /** @throws AlreadyReviewed */
    public function approveRedemption(Redemption $redemption): void
    {
        DB::transaction(fn () => $this->lockPending($redemption)->update(['status' => ReviewStatus::Approved, 'reviewed_at' => now()]));
    }

    /** Poin dikembalikan sebesar snapshot biaya, tepat sekali. @throws AlreadyReviewed */
    public function rejectRedemption(Redemption $redemption, ?string $note = null): void
    {
        DB::transaction(function () use ($redemption, $note) {
            $r = $this->lockPending($redemption);
            $r->update(['status' => ReviewStatus::Rejected, 'reject_note' => $note, 'reviewed_at' => now()]);
            $this->ledger->refund($r->child, $r->cost, $r);
        });
    }

    /** @template T of Model @param T $model @return T */
    private function lockPending(Model $model): Model
    {
        $locked = $model::whereKey($model->getKey())->lockForUpdate()->firstOrFail();

        return $locked->status === ReviewStatus::Pending ? $locked : throw new AlreadyReviewed;
    }
}
