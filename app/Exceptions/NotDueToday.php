<?php

namespace App\Exceptions;

use RuntimeException;

class NotDueToday extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Misi ini tidak ada untuk hari ini.'));
    }
}
