<?php

namespace App\Models;

use App\Enums\Recurrence;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'description', 'points', 'recurrence', 'weekdays', 'month_day', 'start_date', 'end_date', 'active'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'recurrence' => Recurrence::class,
            'weekdays' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'active' => 'boolean',
            'points' => 'integer',
        ];
    }

    public function submissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_child', 'task_id', 'user_id');
    }
}
