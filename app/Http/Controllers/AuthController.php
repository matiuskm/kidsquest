<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Support\FamilyDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);

        if (! Auth::attempt([...$credentials, 'role' => Role::Parent->value])) {
            throw ValidationException::withMessages(['email' => __('Email atau password salah.')]);
        }

        $request->session()->regenerate();
        $request->session()->put(['parent_own_device' => $request->boolean('own_device'), 'parent_last_seen' => now()->timestamp]);
        FamilyDevice::remember();

        return redirect()->route('parent.dashboard');
    }

    public function destroy(Request $request)
    {
        $wasParent = $request->user()?->isParent();

        if ($endpoint = $request->string('push_endpoint')->toString()) {
            $request->user()?->deletePushSubscription($endpoint);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($wasParent ? 'login' : 'child.pick');
    }
}
