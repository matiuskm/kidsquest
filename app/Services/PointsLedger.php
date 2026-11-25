<?php

namespace App\Services;

use App\Enums\LedgerType;
use App\Exceptions\InsufficientPoints;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Satu-satunya sumber kebenaran poin: ledger append-only, saldo = SUM(amount). */
final class PointsLedger
{
    public function balance(User $child): int
    {
        return (int) PointTransaction::where('user_id', $child->id)->sum('amount');
    }

    public function earn(User $child, int $points, Model $ref): PointTransaction
    {
        return $this->write($child, $this->positive($points), LedgerType::Earn, $ref);
    }

    public function exam(User $child, int $points, Model $ref): PointTransaction
    {
        return $this->write($child, $this->positive($points), LedgerType::Exam, $ref);
    }

    /** @throws InsufficientPoints */
    public function redeem(User $child, int $cost, Model $ref): PointTransaction
    {
        return $this->write($child, -$this->positive($cost), LedgerType::Redeem, $ref);
    }

    public function refund(User $child, int $cost, Model $ref): PointTransaction
    {
        return $this->write($child, $this->positive($cost), LedgerType::Refund, $ref);
    }

    /** @throws InsufficientPoints bila saldo akan di bawah 0 */
    public function adjust(User $child, int $amount, string $note): PointTransaction
    {
        if ($amount === 0) {
            throw new InvalidArgumentException('Penyesuaian tidak boleh 0.');
        }

        return $this->write($child, $amount, LedgerType::Adjust, null, $note);
    }

    /** @return ?PointTransaction null bila bonus tahun itu sudah diberikan */
    public function birthday(User $child, int $year, int $points): ?PointTransaction
    {
        return DB::transaction(function () use ($child, $year, $points) {
            $this->lock($child);

            $exists = PointTransaction::where(['user_id' => $child->id, 'type' => LedgerType::Birthday, 'year' => $year])->exists();

            return $exists ? null : $this->write($child, $this->positive($points), LedgerType::Birthday, null, null, $year);
        });
    }

    private function write(User $child, int $amount, LedgerType $type, ?Model $ref = null, ?string $note = null, ?int $year = null): PointTransaction
    {
        return DB::transaction(function () use ($child, $amount, $type, $ref, $note, $year) {
            $this->lock($child);

            if ($amount < 0 && $this->balance($child) + $amount < 0) {
                throw new InsufficientPoints;
            }

            $tx = new PointTransaction(['user_id' => $child->id, 'amount' => $amount, 'type' => $type, 'note' => $note, 'year' => $year]);
            $ref && $tx->ref()->associate($ref);
            $tx->save();

            return $tx;
        });
    }

    /** Serialisasi mutasi per anak. */
    private function lock(User $child): void
    {
        User::whereKey($child->id)->lockForUpdate()->first();
    }

    private function positive(int $points): int
    {
        return $points > 0 ? $points : throw new InvalidArgumentException('Jumlah poin harus lebih dari 0.');
    }
}
