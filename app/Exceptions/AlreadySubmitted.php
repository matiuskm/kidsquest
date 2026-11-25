<?php

namespace App\Exceptions;

use RuntimeException;

class AlreadySubmitted extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Misi ini sudah dikirim.'));
    }
}
