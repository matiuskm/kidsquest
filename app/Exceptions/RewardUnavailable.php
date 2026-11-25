<?php

namespace App\Exceptions;

use RuntimeException;

class RewardUnavailable extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Hadiah ini sedang tidak tersedia.'));
    }
}
