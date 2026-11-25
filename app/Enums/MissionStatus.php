<?php

namespace App\Enums;

enum MissionStatus: string
{
    case Todo = 'todo';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Done = 'done';
}
