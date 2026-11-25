<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\PushMessage;
use Throwable;

/** Satu titik kirim push. Gagal kirim hanya dicatat; tidak pernah menggagalkan request. */
final class Notifier
{
    public function toParent(string $title, string $body, string $url): void
    {
        User::where('role', Role::Parent)->get()->each(fn (User $parent) => $this->send($parent, $title, $body, $url));
    }

    public function toChild(User $child, string $title, string $body, string $url): void
    {
        $this->send($child, $title, $body, $url);
    }

    private function send(User $user, string $title, string $body, string $url): void
    {
        try {
            if ($user->pushSubscriptions()->exists()) {
                $user->notify(new PushMessage($title, $body, $url));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
