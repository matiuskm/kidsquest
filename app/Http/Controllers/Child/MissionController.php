<?php

namespace App\Http\Controllers\Child;

use App\Http\Controllers\Controller;

class MissionController extends Controller
{
    public function index()
    {
        return view('child.missions');
    }
}
