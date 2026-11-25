<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Exceptions\InsufficientPoints;
use App\Exceptions\RewardUnavailable;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RewardService
{
    public function __construct(private PointsLedger $ledger) {}

    /** Poin dipotong saat klaim (dikembalikan bila ditolak). Rollback penuh bila saldo kurang. @throws InsufficientPoints|RewardUnavailable */
    public function claim(User $child, Reward $reward): Redemption
    {
        return DB::transaction(function () use ($child, $reward) {
            if (! Reward::whereKey($reward->id)->where('active', true)->exists()) {
                throw new RewardUnavailable;
            }

            $redemption = Redemption::create([
                'reward_id' => $reward->id,
                'child_id' => $child->id,
                'cost' => $reward->cost,
                'status' => ReviewStatus::Pending,
            ]);
            $this->ledger->redeem($child, $redemption->cost, $redemption);

            return $redemption;
        });
    }
}
