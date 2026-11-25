<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientPoints extends RuntimeException
{
    public function __construct(string $message = 'Poin tidak cukup.')
    {
        parent::__construct($message);
    }
}
