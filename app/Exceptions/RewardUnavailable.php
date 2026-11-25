<?php

namespace App\Exceptions;

use RuntimeException;

class RewardUnavailable extends RuntimeException
{
    public function __construct(string $message = 'Hadiah ini sedang tidak tersedia.')
    {
        parent::__construct($message);
    }
}
