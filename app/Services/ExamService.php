<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Exceptions\AlreadySubmitted;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ExamService
{
    private const MAX_PENDING = 5;

    public function __construct(private PhotoStore $photos, private Notifier $notifier) {}

    /** @throws AlreadySubmitted|\App\Exceptions\InvalidImage */
    public function submit(User $child, string $subject, ?int $score, ?string $letter, UploadedFile $photo): ExamResult
    {
        return DB::transaction(function () use ($child, $subject, $score, $letter, $photo) {
            User::whereKey($child->id)->lockForUpdate()->first(); // serialisasi dobel-tap

            if (ExamResult::where(['child_id' => $child->id, 'status' => ReviewStatus::Pending])->count() >= self::MAX_PENDING) {
                throw new AlreadySubmitted(__('Masih ada :n nilai menunggu ortu. Tunggu diperiksa dulu ya.', ['n' => self::MAX_PENDING]));
            }

            // Satu ulangan per mata pelajaran menunggu keputusan; sekaligus menahan dobel-tap dan kiriman berulang.
            if (ExamResult::where(['child_id' => $child->id, 'subject' => $subject, 'status' => ReviewStatus::Pending])->exists()) {
                throw new AlreadySubmitted(__('Nilai :subject masih menunggu ortu.', ['subject' => $subject]));
            }

            $path = $this->photos->store($photo);

            try {
                $hash = $this->photos->hash($path);

                if ($this->alreadySent($child, $hash)) {
                    throw new AlreadySubmitted(__('Foto ini sudah pernah dikirim.'));
                }

                $exam = ExamResult::create([
                    'child_id' => $child->id,
                    'subject' => $subject,
                    'claimed_score' => $score,
                    'claimed_letter' => $letter,
                    'photo_path' => $path,
                    'photo_hash' => $hash,
                    'status' => ReviewStatus::Pending,
                ]);

                DB::afterCommit(fn () => $this->notifier->toParent(
                    'Nilai dikirim 📝',
                    ':name mengirim nilai :subject.',
                    route('parent.dashboard', absolute: false),
                    ['name' => $child->name, 'subject' => $subject],
                ));

                return $exam;
            } catch (Throwable $e) {
                $this->photos->delete($path);

                throw $e;
            }
        });
    }

    /** Foto yang persis sama dengan kiriman yang menunggu atau disetujui. Baris lama tanpa hash dilengkapi di sini. */
    private function alreadySent(User $child, string $hash): bool
    {
        $mine = fn () => ExamResult::where('child_id', $child->id)->where('status', '!=', ReviewStatus::Rejected);

        $mine()->whereNull('photo_hash')->whereNotNull('photo_path')->get()
            ->each(fn (ExamResult $e) => $e->update(['photo_hash' => $this->photos->hash($e->photo_path)]));

        return $mine()->where('photo_hash', $hash)->exists();
    }
}
