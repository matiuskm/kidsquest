<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicateClaim extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Klaim yang sama baru saja terkirim. Tunggu ortu ya!'));
    }
}
