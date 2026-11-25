<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'password', 'role', 'avatar', 'pin_hash', 'birthday_month', 'birthday_day'])]
#[Hidden(['password', 'remember_token', 'pin_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, SoftDeletes;

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

    public function scopeChildren(Builder $query): void
    {
        $query->where('role', Role::Child);
    }
}
