<?php

namespace App\Exceptions;

use RuntimeException;

class NotDueToday extends RuntimeException
{
    public function __construct(string $message = 'Misi ini tidak ada untuk hari ini.')
    {
        parent::__construct($message);
    }
}
