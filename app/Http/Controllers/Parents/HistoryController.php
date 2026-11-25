<?php

namespace App\Http\Controllers\Parents;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\PointTransaction;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $childId = $request->integer('anak') ?: null;

        return view('parent.history', [
            'children' => User::children()->orderBy('name')->get(),
            'selected' => $childId,
            'submissions' => Submission::with(['task', 'child'])->where('status', '!=', ReviewStatus::Pending)
                ->when($childId, fn ($q) => $q->where('child_id', $childId))->latest('reviewed_at')->limit(50)->get(),
            'exams' => ExamResult::with('child')->where('status', '!=', ReviewStatus::Pending)
                ->when($childId, fn ($q) => $q->where('child_id', $childId))->latest('reviewed_at')->limit(50)->get(),
            'transactions' => PointTransaction::with('user')
                ->when($childId, fn ($q) => $q->where('user_id', $childId))->latest('id')->limit(50)->get(),
        ]);
    }
}
