<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientPoints extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Poin tidak cukup.'));
    }
}
