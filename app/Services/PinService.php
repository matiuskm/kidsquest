<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Login PIN anak: penguncian permanen setelah salah berturut-turut, disimpan di database. */
final class PinService
{
    private const MAX_FAILURES = 5;

    public function __construct(private Notifier $notifier) {}

    /** @throws ValidationException */
    public function attempt(int $childId, string $pin): User
    {
        // Baris dikunci agar tebakan paralel tidak melewati batas; lempar error di luar transaksi supaya hitungan tersimpan.
        [$child, $error, $justLocked] = DB::transaction(function () use ($childId, $pin) {
            $child = User::children()->lockForUpdate()->find($childId);

            if ($child?->pin_locked_at) {
                return [$child, $this->lockedMessage(), false];
            }

            if ($child && Hash::check($pin, $child->pin_hash)) {
                $child->forceFill(['pin_failures' => 0])->save();

                return [$child, null, false];
            }

            if (! $child) {
                return [null, __('PIN salah, coba lagi ya.'), false];
            }

            $failures = $child->pin_failures + 1;
            $lock = $failures >= self::MAX_FAILURES;
            $child->forceFill(['pin_failures' => $failures, 'pin_locked_at' => $lock ? now() : null])->save();

            return [$child, $lock ? $this->lockedMessage() : __('PIN salah, coba lagi ya.'), $lock];
        });

        $justLocked && $this->notifier->toParent('Akun anak terkunci 🔒', ':name salah PIN berkali-kali. Buka dari Beranda.', route('parent.dashboard', absolute: false), ['name' => $child->name]);

        return $error ? throw ValidationException::withMessages(['pin' => $error]) : $child;
    }

    /** Buka kunci oleh orang tua; anak wajib ganti PIN setelah masuk. */
    public function unlock(User $child): void
    {
        $child->forceFill(['pin_failures' => 0, 'pin_locked_at' => null, 'must_change_pin' => true])->save();
        $this->revokeSessions($child);
    }

    /** Dipanggil setelah orang tua mengganti PIN. */
    public function afterReset(User $child): void
    {
        if ($child->pin_locked_at) {
            $this->unlock($child);

            return;
        }

        $child->forceFill(['pin_failures' => 0])->save();
        $this->revokeSessions($child);
    }

    private function revokeSessions(User $child): void
    {
        DB::table(config('session.table'))->where('user_id', $child->id)->delete();
    }

    private function lockedMessage(): string
    {
        return __('Akun terkunci. Minta orang tua membukanya ya.');
    }
}
