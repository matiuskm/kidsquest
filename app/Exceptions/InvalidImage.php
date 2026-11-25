<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidImage extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Foto tidak bisa dibaca. Coba ambil foto lagi ya.'));
    }
}
