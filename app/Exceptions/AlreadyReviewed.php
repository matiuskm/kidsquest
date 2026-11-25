<?php

namespace App\Exceptions;

use RuntimeException;

class AlreadyReviewed extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Sudah diputuskan sebelumnya.'));
    }
}
