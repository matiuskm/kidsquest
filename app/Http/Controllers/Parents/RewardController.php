<?php

namespace App\Http\Controllers\Parents;

use App\Http\Controllers\Controller;
use App\Http\Requests\RewardRequest;
use App\Models\Reward;

class RewardController extends Controller
{
    public function index()
    {
        return view('parent.rewards.index', ['rewards' => Reward::orderBy('cost')->get()]);
    }

    public function create()
    {
        return view('parent.rewards.form', ['reward' => new Reward(['cost' => 50, 'icon' => '🎁', 'active' => true])]);
    }

    public function store(RewardRequest $request)
    {
        Reward::create($request->rewardData());

        return redirect()->route('parent.rewards.index')->with('status', __('Hadiah ditambahkan.'));
    }

    public function edit(Reward $reward)
    {
        return view('parent.rewards.form', ['reward' => $reward]);
    }

    public function update(RewardRequest $request, Reward $reward)
    {
        $reward->update($request->rewardData());

        return redirect()->route('parent.rewards.index')->with('status', __('Hadiah disimpan.'));
    }

    public function destroy(Reward $reward)
    {
        $reward->delete();

        return redirect()->route('parent.rewards.index')->with('status', __('Hadiah dihapus.'));
    }
}
