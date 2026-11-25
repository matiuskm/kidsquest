<?php

namespace App\Http\Controllers\Parents;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('parent.dashboard');
    }
}
