<?php

namespace App\Models;

use Database\Factories\RewardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'icon', 'cost', 'active'])]
class Reward extends Model
{
    /** @use HasFactory<RewardFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['cost' => 'integer', 'active' => 'boolean'];
    }
}
