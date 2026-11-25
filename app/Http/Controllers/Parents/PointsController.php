<?php

namespace App\Http\Controllers\Parents;

use App\Exceptions\InsufficientPoints;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Notifier;
use App\Services\PointsLedger;
use Illuminate\Http\Request;

class PointsController extends Controller
{
    public function adjust(Request $request, User $child, PointsLedger $ledger, Notifier $notifier)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'note' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $amount = (int) $data['amount'];
            $note = $data['note'] ?? __('Bonus dari orang tua');
            $ledger->adjust($child, $amount, $note);
        } catch (InsufficientPoints $e) {
            return back()->withErrors(['review' => __('Saldo tidak boleh di bawah 0.')]);
        }

        $amount > 0 && $notifier->toChild($child, 'Bonus dari ortu 🎁', '+:n poin. :note', route('child.missions', absolute: false), ['n' => $amount, 'note' => $note]);

        return back()->with('status', __('Poin disesuaikan.'));
    }
}
