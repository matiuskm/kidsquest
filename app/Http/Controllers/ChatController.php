<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private ChatService $chat) {}

    public function index(Request $request)
    {
        return view('chat.index', [
            'layout' => $this->layout($request),
            'contacts' => $this->chat->contacts($request->user()),
            'unread' => $this->chat->unreadBySender($request->user()),
        ]);
    }

    public function show(Request $request, User $user)
    {
        $me = $request->user();
        abort_unless($this->chat->canChat($me, $user), 403);

        $messages = $this->chat->thread($me, $user)->latest('id')->limit(100)->get()->reverse()->values();
        $this->chat->markRead($me, $user);

        return view('chat.show', ['layout' => $this->layout($request), 'other' => $user, 'messages' => $messages]);
    }

    public function store(Request $request, User $user)
    {
        abort_unless($this->chat->canChat($request->user(), $user), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:500']]);

        $this->chat->send($request->user(), $user, $data['body']);

        return redirect()->route('chat.show', $user);
    }

    public function poll(Request $request, User $user)
    {
        $me = $request->user();
        abort_unless($this->chat->canChat($me, $user), 403);

        $messages = $this->chat->thread($me, $user, $request->integer('after'))->oldest('id')->limit(50)->get();
        $this->chat->markRead($me, $user);

        return response()->json(['messages' => $messages->map(fn (Message $m) => $this->present($m, $me))]);
    }

    public static function present(Message $m, User $me): array
    {
        return ['id' => $m->id, 'body' => $m->body, 'mine' => $m->sender_id === $me->id, 'time' => $m->created_at->format('H:i')];
    }

    private function layout(Request $request): string
    {
        return $request->user()->isParent() ? 'layouts.parent' : 'layouts.child';
    }
}
