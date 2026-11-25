<?php

namespace App\Http\Controllers\Parents;

use App\Enums\MissionStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\Redemption;
use App\Models\Submission;
use App\Models\User;
use App\Services\MissionService;

class DashboardController extends Controller
{
    public function __invoke(MissionService $missions)
    {
        $today = now()->toImmutable();

        return view('parent.dashboard', [
            'pending' => Submission::with(['task', 'child'])->where('status', ReviewStatus::Pending)->oldest()->get(),
            'exams' => ExamResult::with('child')->where('status', ReviewStatus::Pending)->oldest()->get(),
            'claims' => Redemption::with(['reward', 'child'])->where('status', ReviewStatus::Pending)->oldest()->get(),
            'children' => User::children()->orderBy('name')->get()->map(function (User $child) use ($missions, $today) {
                $list = $missions->todayFor($child, $today);

                return [
                    'child' => $child,
                    'done' => $list->where('status', MissionStatus::Done)->count(),
                    'total' => $list->count(),
                ];
            }),
        ]);
    }
}
