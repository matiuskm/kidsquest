<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reward_id', 'child_id', 'cost', 'status', 'reject_note', 'reviewed_at'])]
class Redemption extends Model
{
    protected function casts(): array
    {
        return ['status' => ReviewStatus::class, 'cost' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class)->withTrashed();
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id')->withTrashed();
    }
}
