<?php

namespace App\Exceptions;

use RuntimeException;

class MissingPhoto extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? __('Foto bukti tidak ditemukan, tidak bisa disetujui. Minta anak kirim ulang (Tolak).'));
    }
}
