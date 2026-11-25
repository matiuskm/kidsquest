<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Support\ExamGrade;
use Database\Factories\ExamResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'subject', 'claimed_score', 'final_score', 'photo_path', 'status', 'reject_note', 'points_awarded', 'reviewed_at'])]
class ExamResult extends Model
{
    /** @use HasFactory<ExamResultFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => ReviewStatus::class, 'reviewed_at' => 'datetime'];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id')->withTrashed();
    }

    public function grade(): ExamGrade
    {
        return ExamGrade::for($this->final_score ?? $this->claimed_score);
    }
}
