<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\ExamResult;
use App\Models\Message;
use App\Models\Redemption;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ChildService
{
    public function __construct(private ReviewService $reviews, private PhotoStore $photos) {}

    /** Soft delete: ledger dan riwayat tetap; foto dihapus; yang masih pending diselesaikan (klaim dikembalikan). */
    public function remove(User $child): void
    {
        DB::transaction(function () use ($child) {
            $note = 'Akun anak dihapus';
            $child->pushSubscriptions()->delete(); // sebelum penolakan agar tidak ada push ke akun yang dihapus

            Submission::where('child_id', $child->id)->where('status', ReviewStatus::Pending)->get()
                ->each(fn (Submission $s) => $this->reviews->rejectSubmission($s, $note));
            ExamResult::where('child_id', $child->id)->where('status', ReviewStatus::Pending)->get()
                ->each(fn (ExamResult $e) => $this->reviews->rejectExam($e, $note));
            Redemption::where('child_id', $child->id)->where('status', ReviewStatus::Pending)->get()
                ->each(fn (Redemption $r) => $this->reviews->rejectRedemption($r, $note));

            Submission::where('child_id', $child->id)->whereNotNull('photo_path')->get()->each(function (Submission $s) {
                $this->photos->delete($s->photo_path);
                $s->update(['photo_path' => null]);
            });

            ExamResult::where('child_id', $child->id)->whereNotNull('photo_path')->get()->each(function (ExamResult $e) {
                $this->photos->delete($e->photo_path);
                $e->update(['photo_path' => null]);
            });

            Message::where('sender_id', $child->id)->orWhere('recipient_id', $child->id)->delete();

            $child->delete();
        });
    }
}
