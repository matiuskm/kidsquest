<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('child.profile', ['child' => auth()->user()]);
    }

    public function update(ProfileRequest $request)
    {
        $data = $request->profileData();
        if (isset($data['pin'])) {
            $data['pin_hash'] = $data['pin'];
            unset($data['pin']);
        }

        $request->user()->update($data);

        return redirect()->route('child.profile')->with('status', __('Profil disimpan ✨'));
    }
}
