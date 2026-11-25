<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'child_id', 'period_date', 'photo_path', 'status', 'reject_note', 'reviewed_at'])]
class Submission extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'period_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class)->withTrashed();
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id')->withTrashed();
    }
}
