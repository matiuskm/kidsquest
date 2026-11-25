<?php

namespace App\Support;

use App\Enums\MissionStatus;
use App\Models\Submission;
use App\Models\Task;

final readonly class Mission
{
    public function __construct(
        public Task $task,
        public MissionStatus $status,
        public ?Submission $submission,
    ) {}
}
