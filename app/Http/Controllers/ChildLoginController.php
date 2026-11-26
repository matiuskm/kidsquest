<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ChildLoginRequest;
use App\Models\User;
use App\Services\PinService;
use App\Support\FamilyDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ChildLoginController extends Controller
{
    public function pick(Request $request)
    {
        if ($user = $request->user()) {
            return redirect()->route($user->homeRoute());
        }

        $known = FamilyDevice::known($request);

        return view('auth.child-pick', [
            'known' => $known,
            'children' => $known ? User::children()->orderBy('name')->get() : collect(),
        ]);
    }

    public function store(ChildLoginRequest $request, PinService $pins)
    {
        if (! FamilyDevice::known($request)) {
            throw ValidationException::withMessages(['pin' => __('HP ini belum dikenali. Minta orang tua masuk sekali di HP ini.')]);
        }

        Auth::login($pins->attempt($request->integer('child_id'), $request->string('pin')->toString()));
        $request->session()->regenerate();
        FamilyDevice::remember();

        // Notifikasi orang tua tidak boleh tertinggal di perangkat yang kini dipakai anak.
        if ($endpoint = $request->string('push_endpoint')->toString()) {
            User::where('role', Role::Parent)->get()->each(fn (User $parent) => $parent->deletePushSubscription($endpoint));
        }

        return redirect()->route('child.missions');
    }
}
