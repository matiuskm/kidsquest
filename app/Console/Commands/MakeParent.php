<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class MakeParent extends Command
{
    protected $signature = 'kidsquest:make-parent {email} {name}';

    protected $description = 'Buat akun orang tua (tidak ada registrasi publik)';

    public function handle(): int
    {
        if (User::where('email', $this->argument('email'))->exists()) {
            $this->error('Email sudah terdaftar.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password');

        if (mb_strlen($password) < 10) {
            $this->error('Password minimal 10 karakter.');

            return self::FAILURE;
        }

        User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => $password,
            'role' => Role::Parent,
            'avatar' => '🧑',
        ]);

        $this->info('Akun orang tua dibuat.');

        return self::SUCCESS;
    }
}
