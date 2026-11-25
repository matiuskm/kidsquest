<?php

namespace App\Exceptions;

use RuntimeException;

class AlreadySubmitted extends RuntimeException
{
    public function __construct(string $message = 'Misi ini sudah dikirim.')
    {
        parent::__construct($message);
    }
}
