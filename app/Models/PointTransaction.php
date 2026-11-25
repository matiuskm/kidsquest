<?php

namespace App\Models;

use App\Enums\LedgerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['user_id', 'amount', 'type', 'note', 'year'])]
class PointTransaction extends Model
{
    protected function casts(): array
    {
        return ['type' => LedgerType::class, 'amount' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function ref(): MorphTo
    {
        return $this->morphTo();
    }
}
