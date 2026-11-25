<?php

namespace App\Services;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Chat 1-lawan-1. Parent hanya melihat percakapan miliknya sendiri; chat antar anak privat. */
final class ChatService
{
    public function __construct(private Notifier $notifier) {}

    /** Parent-parent tidak ada; antar anak butuh izin `peer_chat` dari kedua sisi. */
    public function canChat(User $me, User $other): bool
    {
        return match (true) {
            $me->is($other), $me->isParent() && $other->isParent() => false,
            $me->isParent() || $other->isParent() => true,
            default => $me->peer_chat && $other->peer_chat,
        };
    }

    /** @return Collection<int, User> */
    public function contacts(User $me): Collection
    {
        return User::orderBy('name')->get()->filter(fn (User $u) => $this->canChat($me, $u))->values();
    }

    /** @return array<int, int> jumlah belum dibaca per pengirim */
    public function unreadBySender(User $me): array
    {
        return Message::where('recipient_id', $me->id)->whereNull('read_at')
            ->selectRaw('sender_id, count(*) as n')->groupBy('sender_id')->pluck('n', 'sender_id')->all();
    }

    public function send(User $from, User $to, string $body): Message
    {
        $message = Message::create(['sender_id' => $from->id, 'recipient_id' => $to->id, 'body' => $body]);
        $this->notifier->toUser($to, "💬 {$from->name}", Str::limit($body, 80), route('chat.show', $from, absolute: false));

        return $message;
    }

    /** Pesan antara dua orang, terlama di atas. */
    public function thread(User $me, User $other, int $after = 0): Builder
    {
        return Message::where('id', '>', $after)->where(fn ($q) => $q
            ->where(fn ($q) => $q->where('sender_id', $me->id)->where('recipient_id', $other->id))
            ->orWhere(fn ($q) => $q->where('sender_id', $other->id)->where('recipient_id', $me->id)));
    }

    public function markRead(User $me, User $other): void
    {
        Message::where('sender_id', $other->id)->where('recipient_id', $me->id)->whereNull('read_at')->update(['read_at' => now()]);
    }
}
