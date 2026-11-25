<?php

namespace App\Policies;

use App\Models\ExamResult;
use App\Models\User;

class ExamResultPolicy
{
    public function view(User $user, ExamResult $exam): bool
    {
        return $user->isParent() || $exam->child_id === $user->id;
    }
}
