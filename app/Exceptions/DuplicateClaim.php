<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicateClaim extends RuntimeException
{
    public function __construct(string $message = 'Klaim yang sama baru saja terkirim. Tunggu ortu ya!')
    {
        parent::__construct($message);
    }
}
