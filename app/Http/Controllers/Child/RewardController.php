<?php

namespace App\Http\Controllers\Child;

use App\Exceptions\InsufficientPoints;
use App\Exceptions\RewardUnavailable;
use App\Http\Controllers\Controller;
use App\Models\Redemption;
use App\Models\Reward;
use App\Services\RewardService;

class RewardController extends Controller
{
    public function index()
    {
        return view('child.rewards', [
            'rewards' => Reward::where('active', true)->orderBy('cost')->get(),
            'claims' => Redemption::with('reward')->where('child_id', auth()->id())->latest('id')->limit(20)->get(),
        ]);
    }

    public function claim(Reward $reward, RewardService $rewards)
    {
        try {
            $rewards->claim(auth()->user(), $reward);
        } catch (InsufficientPoints|RewardUnavailable $e) {
            return back()->withErrors(['reward' => $e instanceof InsufficientPoints ? 'Poinmu belum cukup. Semangat! 💪' : $e->getMessage()])->with('sound', 'reject');
        }

        return redirect()->route('child.rewards')->with('status', 'Klaim terkirim! Tunggu ortu setuju ya 🎁')->with('sound', 'redeem');
    }
}
