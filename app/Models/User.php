<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'password', 'role', 'avatar', 'pin_hash', 'birthday_month', 'birthday_day'])]
#[Hidden(['password', 'remember_token', 'pin_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        // Tanggal lahir dikunci setelah terisi (dasar bonus ulang tahun).
        static::updating(function (User $user) {
            foreach (['birthday_month', 'birthday_day'] as $field) {
                if ($user->isDirty($field) && $user->getOriginal($field) !== null) {
                    throw new \LogicException('Tanggal lahir tidak bisa diubah.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'password' => 'hashed',
            'pin_hash' => 'hashed',
        ];
    }

    public function isParent(): bool
    {
        return $this->role === Role::Parent;
    }

    public function isChild(): bool
    {
        return $this->role === Role::Child;
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_child', 'user_id', 'task_id');
    }

    /** Saldo poin (dari ledger). */
    public function getPointsAttribute(): int
    {
        return app(\App\Services\PointsLedger::class)->balance($this);
    }

    public function scopeChildren(Builder $query): void
    {
        $query->where('role', Role::Child);
    }
}
