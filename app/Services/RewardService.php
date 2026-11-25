<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Exceptions\DuplicateClaim;
use App\Exceptions\InsufficientPoints;
use App\Exceptions\RewardUnavailable;
use App\Models\Redemption;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RewardService
{
    /** Hadiah boleh diklaim berulang, tapi bukan dua kali dalam sekejap (dobel-tap). */
    private const DOUBLE_TAP_SECONDS = 10;

    public function __construct(private PointsLedger $ledger, private Notifier $notifier) {}

    /** Poin dipotong saat klaim (dikembalikan bila ditolak). Rollback penuh bila saldo kurang. @throws InsufficientPoints|RewardUnavailable|DuplicateClaim */
    public function claim(User $child, Reward $reward): Redemption
    {
        return DB::transaction(function () use ($child, $reward) {
            User::whereKey($child->id)->lockForUpdate()->first(); // serialisasi dobel-tap

            $doubleTap = Redemption::where(['child_id' => $child->id, 'reward_id' => $reward->id, 'status' => ReviewStatus::Pending])
                ->where('created_at', '>=', now()->subSeconds(self::DOUBLE_TAP_SECONDS))->exists();

            if ($doubleTap) {
                throw new DuplicateClaim;
            }

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

            DB::afterCommit(fn () => $this->notifier->toParent(
                'Klaim hadiah 🎁',
                "{$child->name} ingin menukar “{$reward->name}”.",
                route('parent.dashboard', absolute: false),
            ));

            return $redemption;
        });
    }
}
