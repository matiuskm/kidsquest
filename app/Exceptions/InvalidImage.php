<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidImage extends RuntimeException
{
    public function __construct(string $message = 'Foto tidak bisa dibaca. Coba ambil foto lagi ya.')
    {
        parent::__construct($message);
    }
}
