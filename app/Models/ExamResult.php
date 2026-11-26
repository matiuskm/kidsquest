<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Support\ExamGrade;
use Database\Factories\ExamResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['child_id', 'subject', 'claimed_score', 'claimed_letter', 'final_score', 'final_letter', 'photo_path', 'photo_hash', 'status', 'reject_note', 'points_awarded', 'reviewed_at'])]
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

    /** Ulangan terakhir yang disetujui untuk anak dan mata pelajaran yang sama (petunjuk bagi parent agar tidak menyetujui kertas yang sama dua kali). */
    public function lastApproved(): ?self
    {
        return static::where(['child_id' => $this->child_id, 'subject' => $this->subject, 'status' => ReviewStatus::Approved])
            ->latest('reviewed_at')->first();
    }

    /** Pelajaran dan nilai yang sama disetujui dalam 30 hari terakhir: kemungkinan kertas yang sama difoto ulang. */
    public function sameMarkApproved(): ?self
    {
        [$claimed, $final, $mark] = $this->isLetter()
            ? ['claimed_letter', 'final_letter', $this->claimed_letter]
            : ['claimed_score', 'final_score', $this->claimed_score];

        return static::where(['child_id' => $this->child_id, 'subject' => $this->subject, 'status' => ReviewStatus::Approved])
            ->where(fn ($q) => $q->where($claimed, $mark)->orWhere($final, $mark))
            ->where('reviewed_at', '>=', now()->subDays(30))
            ->latest('reviewed_at')->first();
    }

    public function isLetter(): bool
    {
        return $this->claimed_letter !== null;
    }

    /** Nilai yang ditampilkan: koreksi parent bila ada, kalau tidak klaim anak ("95" atau "B"). */
    public function mark(): string
    {
        return (string) ($this->final_letter ?? $this->final_score ?? $this->claimed_letter ?? $this->claimed_score);
    }

    public function grade(): ExamGrade
    {
        return $this->isLetter()
            ? ExamGrade::forLetter($this->final_letter ?? $this->claimed_letter)
            : ExamGrade::for($this->final_score ?? $this->claimed_score);
    }
}
