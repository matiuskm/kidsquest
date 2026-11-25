<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\PushMessage;
use Throwable;

/** Satu titik kirim push. Gagal kirim hanya dicatat; tidak pernah menggagalkan request. */
final class Notifier
{
    /** Judul/isi adalah kunci terjemahan; diterjemahkan ke bahasa tiap penerima. */
    public function toParent(string $title, string $body, string $url, array $replace = []): void
    {
        User::where('role', Role::Parent)->get()->each(fn (User $parent) => $this->toUser($parent, $title, $body, $url, $replace));
    }

    public function toChild(User $child, string $title, string $body, string $url, array $replace = []): void
    {
        $this->toUser($child, $title, $body, $url, $replace);
    }

    public function toUser(User $user, string $title, string $body, string $url, array $replace = []): void
    {
        try {
            if ($user->pushSubscriptions()->exists()) {
                $user->notify(new PushMessage(__($title, $replace, $user->locale), __($body, $replace, $user->locale), $url));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
