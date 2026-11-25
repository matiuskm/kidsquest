<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChildLoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChildLoginController extends Controller
{
    public function pick()
    {
        if ($user = auth()->user()) {
            return redirect()->route($user->homeRoute());
        }

        return view('auth.child-pick', ['children' => User::children()->orderBy('name')->get()]);
    }

    public function store(ChildLoginRequest $request)
    {
        Auth::login($request->authenticate());
        $request->session()->regenerate();

        return redirect()->route('child.missions');
    }
}
