<?php

namespace App\Http\Controllers\Parents;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChildLoginRequest;
use App\Http\Requests\ChildRequest;
use App\Models\User;
use App\Services\ChildService;

class ChildController extends Controller
{
    public function index()
    {
        return view('parent.children.index', ['children' => User::children()->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('parent.children.form', ['child' => new User(['avatar' => config('kidsquest.avatars')[0]])]);
    }

    public function store(ChildRequest $request)
    {
        User::create([...$request->childData(), 'role' => Role::Child, 'pin_hash' => $request->validated('pin')]);

        return redirect()->route('parent.children.index')->with('status', __('Anak ditambahkan.'));
    }

    public function edit(User $child)
    {
        return view('parent.children.form', ['child' => $child]);
    }

    public function update(ChildRequest $request, User $child)
    {
        $data = $request->childData();
        $pin = $data['pin'] ?? null;
        unset($data['pin']);

        $child->update($pin ? [...$data, 'pin_hash' => $pin] : $data);
        $pin && ChildLoginRequest::clearLockout($child->id);

        return redirect()->route('parent.children.index')->with('status', __('Data anak disimpan.'));
    }

    public function destroy(User $child, ChildService $children)
    {
        $children->remove($child);

        return redirect()->route('parent.children.index')->with('status', __('Anak dihapus.'));
    }

    public function toggleChat(User $child)
    {
        $child->update(['peer_chat' => ! $child->peer_chat]);

        return back()->with('status', __($child->peer_chat ? 'Chat antar anak diaktifkan untuk :name.' : 'Chat antar anak dimatikan untuk :name.', ['name' => $child->name]));
    }
}
