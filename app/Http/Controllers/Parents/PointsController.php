<?php

namespace App\Http\Controllers\Parents;

use App\Exceptions\InsufficientPoints;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PointsLedger;
use Illuminate\Http\Request;

class PointsController extends Controller
{
    public function adjust(Request $request, User $child, PointsLedger $ledger)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'note' => ['required', 'string', 'max:100'],
        ]);

        try {
            $ledger->adjust($child, (int) $data['amount'], $data['note']);
        } catch (InsufficientPoints $e) {
            return back()->withErrors(['review' => 'Saldo tidak boleh di bawah 0.']);
        }

        return back()->with('status', 'Poin disesuaikan.');
    }
}
