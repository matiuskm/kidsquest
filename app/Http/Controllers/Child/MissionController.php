<?php

namespace App\Http\Controllers\Child;

use App\Exceptions\AlreadySubmitted;
use App\Exceptions\InvalidImage;
use App\Exceptions\NotDueToday;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Task;
use App\Services\MissionService;

class MissionController extends Controller
{
    public function index(MissionService $missions)
    {
        return view('child.missions', ['missions' => $missions->todayFor(auth()->user(), now()->toImmutable())]);
    }

    public function store(StoreSubmissionRequest $request, Task $task, MissionService $missions)
    {
        try {
            $missions->submit($request->user(), $task, $request->file('photo'), now()->toImmutable());
        } catch (NotDueToday|AlreadySubmitted|InvalidImage $e) {
            return back()->withErrors(['photo' => $e->getMessage()]);
        }

        return redirect()->route('child.missions')->with('status', 'Foto terkirim! Tunggu ortu periksa ya ✨');
    }
}
