<?php

namespace App\Http\Controllers\Parents;

use App\Exceptions\AlreadyReviewed;
use App\Exceptions\InsufficientPoints;
use App\Exceptions\MissingPhoto;
use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\Redemption;
use App\Models\Submission;
use App\Services\ReviewService;
use Closure;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function approveSubmission(Submission $submission, ReviewService $reviews)
    {
        return $this->decide(fn () => $reviews->approveSubmission($submission), 'Disetujui, poin masuk ✅');
    }

    public function rejectSubmission(Request $request, Submission $submission, ReviewService $reviews)
    {
        return $this->decide(fn () => $reviews->rejectSubmission($submission, $this->note($request)), 'Ditolak, anak bisa kirim ulang.');
    }

    public function approveExam(Request $request, ExamResult $exam, ReviewService $reviews)
    {
        $data = $request->validate(['score' => ['nullable', 'integer', 'between:0,100'], 'letter' => ['nullable', 'in:A,B,C,D,E']]);

        return $this->decide(fn () => $reviews->approveExam($exam, isset($data['score']) ? (int) $data['score'] : null, $data['letter'] ?? null), 'Nilai disetujui ✅');
    }

    public function rejectExam(Request $request, ExamResult $exam, ReviewService $reviews)
    {
        return $this->decide(fn () => $reviews->rejectExam($exam, $this->note($request)), 'Ditolak, anak bisa kirim ulang.');
    }

    public function approveRedemption(Redemption $redemption, ReviewService $reviews)
    {
        return $this->decide(fn () => $reviews->approveRedemption($redemption), 'Klaim hadiah disetujui 🎁');
    }

    public function rejectRedemption(Request $request, Redemption $redemption, ReviewService $reviews)
    {
        return $this->decide(fn () => $reviews->rejectRedemption($redemption, $this->note($request)), 'Klaim ditolak, poin dikembalikan.');
    }

    protected function note(Request $request): ?string
    {
        return $request->validate(['note' => ['nullable', 'string', 'max:200']])['note'] ?? null;
    }

    protected function decide(Closure $action, string $message)
    {
        try {
            $action();
        } catch (AlreadyReviewed|MissingPhoto|InsufficientPoints $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return back()->with('status', $message);
    }
}
