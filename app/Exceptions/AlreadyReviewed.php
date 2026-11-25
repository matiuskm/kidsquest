<?php

namespace App\Exceptions;

use RuntimeException;

class AlreadyReviewed extends RuntimeException
{
    public function __construct(string $message = 'Sudah diputuskan sebelumnya.')
    {
        parent::__construct($message);
    }
}
