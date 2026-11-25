<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate(['locale' => ['required', Rule::in(SetLocale::LOCALES)]]);

        $request->user()->update($data);

        return back();
    }
}
