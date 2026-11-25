# Poin Nilai Ulangan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Anak mengirim nilai ulangan + foto; parent memverifikasi (boleh koreksi nilai); poin masuk ledger sesuai rentang nilai.

**Architecture:** Entitas baru `ExamResult` + tipe ledger `exam`. Rentang nilai di `config/kidsquest.php`, dihitung `ExamGrade`. Reuse `PhotoStore`, `ReviewService` (lockPending, push via afterCommit), `PointsLedger`, komponen `review-item`.

**Tech Stack:** Laravel 13, Blade + Alpine, PHPUnit (`php vendor/bin/phpunit`), MySQL.

**Spec:** `docs/superpowers/specs/2026-11-25-exam-points-design.md`

## Global Constraints
- Rentang: 90-100 A = 5, 80-89 B = 4, 70-79 C = 3, di bawah 70 = 0 poin.
- Nilai integer 0-100. Foto wajib (max 10 MB, lewat `PhotoStore`). Bahasa UI Indonesia.
- Poin hanya lewat `PointsLedger`; poin 0 = tidak menulis ledger.
- Satu PR per task-group, branch `feat/exam-points`, SemVer: masuk `CHANGELOG.md` [Unreleased]; jalankan `graphify update .` setelah kode berubah.
- Test pakai `RefreshDatabase`, `Storage::fake('local')` (lihat `tests/Feature/ReviewSubmissionTest.php`).

## Review Focus
- Nilai di luar 0-100 / bukan angka / desimal → ditolak validasi, baik dari anak maupun koreksi parent.
- Dobel-tap kirim (dua request <10 detik, data sama) → hanya satu baris `pending`.
- Approve dua kali → ledger tepat satu entri; nilai di bawah 70 → approved, saldo tidak berubah.
- Koreksi parent mengubah poin (klaim 95, koreksi 75 → 3 poin) dan `final_score` tersimpan.
- Anak lain / guest tidak bisa melihat foto ulangan; foto hilang saat approve → ditolak seperti `MissingPhoto`.

---

### Task 1: ExamGrade + tipe ledger

**Files:**
- Create: `app/Support/ExamGrade.php`, `tests/Unit/ExamGradeTest.php`
- Modify: `config/kidsquest.php`, `app/Enums/LedgerType.php`, `app/Services/PointsLedger.php`, `tests/Unit/PointsLedgerTest.php`

**Interfaces:**
- Produces: `ExamGrade::for(int $score): ExamGrade` dengan properti publik readonly `string $letter`, `int $points`; `LedgerType::Exam = 'exam'`; `PointsLedger::exam(User $child, int $points, Model $ref): PointTransaction`.
- Config: `exam_grades` = list `[min, letter, points]` urut menurun: `[90,'A',5],[80,'B',4],[70,'C',3],[0,'D',0]`. Nilai < 70 mendapat huruf `D`, 0 poin (huruf bukan kebutuhan Kris, hanya label).

- [ ] **Step 1: Test gagal** — `ExamGradeTest`: data provider `0→D/0, 69→D/0, 70→C/3, 79→C/3, 80→B/4, 89→B/4, 90→A/5, 100→A/5`; `for(101)` dan `for(-1)` melempar `InvalidArgumentException`. Tambah di `PointsLedgerTest`: `exam()` menulis `+points` bertipe `exam`, dan `exam(..., 0, ...)` melempar `InvalidArgumentException` (aturan `positive`).
- [ ] **Step 2:** Run `php vendor/bin/phpunit tests/Unit/ExamGradeTest.php tests/Unit/PointsLedgerTest.php` → Expected: FAIL (class/method missing).
- [ ] **Step 3:** Implement `ExamGrade::for` (loop config, final readonly class, validasi 0-100), `LedgerType::Exam`, `PointsLedger::exam` (mirip `earn`), config `exam_grades`.
- [ ] **Step 4:** Run full `php vendor/bin/phpunit` → Expected: all PASS.
- [ ] **Step 5:** Commit `feat: exam grade bands and exam ledger type`.

### Task 2: Kirim nilai oleh anak

**Files:**
- Create: `database/migrations/2026_11_25_000006_create_exam_results_table.php`, `app/Models/ExamResult.php`, `database/factories/ExamResultFactory.php`, `app/Services/ExamService.php`, `app/Http/Requests/StoreExamResultRequest.php`, `app/Http/Controllers/Child/ExamController.php`, `app/Policies/ExamResultPolicy.php`, `resources/views/child/exams.blade.php`, `tests/Feature/ExamSubmissionTest.php`
- Modify: `routes/web.php`, `app/Http/Controllers/PhotoController.php`, `resources/views/layouts/child.blade.php` (menu bawah: tambah "Nilai" 📝)

**Interfaces:**
- Table `exam_results`: id, `child_id` (FK users), `subject` string(60), `claimed_score` unsignedTinyInteger, `final_score` unsignedTinyInteger nullable, `photo_path` string, `status` string(16) default pending index, `reject_note` nullable, `points_awarded` unsignedTinyInteger nullable, `reviewed_at` nullable, timestamps.
- `ExamResult` model: `#[Fillable]` semua kolom di atas kecuali id; casts `status => ReviewStatus`, `reviewed_at => datetime`; `child(): BelongsTo` (withTrashed); `grade(): ?ExamGrade` memakai `final_score ?? claimed_score`.
- `ExamService::submit(User $child, string $subject, int $score, UploadedFile $photo): ExamResult` — transaksi + lock baris anak (pola `MissionService::submit`), simpan foto lewat `PhotoStore`, hapus foto bila gagal, `afterCommit` → `Notifier::toParent('Nilai dikirim 📝', "{$child->name} mengirim nilai {$subject}.", route('parent.dashboard', absolute: false))`. Melempar `AlreadySubmitted` bila child+subject+score sama sudah ada <10 detik.
- Route (grup child): `GET /nilai` `child.exams` (index: form + riwayat nilai anak dengan huruf & poin), `POST /nilai` `child.exams.store`; `GET /foto-ulangan/{exam}` `photos.exam` (auth, `PhotoController@exam`, `Gate::authorize('view', $exam)`; policy: parent atau pemilik; refactor respons file jadi helper privat yang dipakai `__invoke` juga).
- `StoreExamResultRequest`: `subject` required string max:60, `score` required integer between:0,100, `photo` required file image max:10240; pesan Indonesia seperti `StoreSubmissionRequest`.

- [ ] **Step 1: Test gagal** `ExamSubmissionTest`: (a) anak kirim valid → baris pending, `claimed_score` benar, foto tersimpan di disk fake, redirect dengan status; (b) skor 101, -1, 85.5, "abc" → error validasi `score`, tidak ada baris; (c) tanpa foto → error `photo`; (d) kirim sama dua kali berturut-turut → satu baris, error; (e) anak B GET `photos.exam` foto anak A → 403, anak A & parent → 200, guest → redirect login; (f) `child.exams` hanya menampilkan nilai anak itu.
- [ ] **Step 2:** Run `php vendor/bin/phpunit tests/Feature/ExamSubmissionTest.php` → Expected: FAIL.
- [ ] **Step 3:** Implement semua file di atas; view mengikuti gaya `child/missions.blade.php` (font besar, `photo-upload` seperti form misi — baca view itu dan pakai ulang komponen/JS-nya). Daftarkan policy di `AppServiceProvider` mengikuti `SubmissionPolicy`.
- [ ] **Step 4:** Run full suite → Expected: PASS.
- [ ] **Step 5:** Commit `feat: children submit exam scores with photo`.

### Task 3: Verifikasi parent + poin

**Files:**
- Create: `tests/Feature/ReviewExamTest.php`
- Modify: `app/Services/ReviewService.php`, `app/Http/Controllers/Parents/ReviewController.php`, `app/Http/Controllers/Parents/DashboardController.php`, `app/Http/Controllers/Parents/HistoryController.php`, `routes/web.php`, `resources/views/components/review-item.blade.php`, `resources/views/parent/dashboard.blade.php`, `resources/views/parent/history.blade.php`, `CHANGELOG.md`

**Interfaces:**
- Consumes: Task 1 (`ExamGrade::for`, `PointsLedger::exam`), Task 2 (`ExamResult`, route `photos.exam`).
- Produces: `ReviewService::approveExam(ExamResult $exam, ?int $score = null): void` — di transaksi: `lockPending`, cek foto ada (`MissingPhoto`), `final = $score ?? claimed_score`, `grade = ExamGrade::for($final)`, update `status Approved`, `final_score`, `points_awarded`, `reviewed_at`; bila `points > 0` → `ledger->exam(...)`; push anak `"Nilai disetujui 📝"` "{subject}: {letter}, +{points} poin." (tanpa "+" bila 0), route `child.exams`. `ReviewService::rejectExam(ExamResult $exam, ?string $note = null): void` (status Rejected + note + push "Coba lagi 🔁"). `lockPending` sudah generik.
- Routes: `parent.exams.approve` `POST periksa/nilai/{exam}/setuju` (validasi `score` nullable integer between:0,100), `parent.exams.reject` `POST periksa/nilai/{exam}/tolak`. Route binding param `exam` → `ExamResult`.
- `review-item`: tambah slot opsional `$fields` di dalam form approve (input angka `score` bernilai klaim anak, `min=0 max=100`); dashboard tampilkan `$exams` pending (`ExamResult::with('child')`) di antrean + hitungan "Perlu diperiksa" ikut menjumlahkan; history menampilkan ulangan non-pending (nilai akhir, huruf, poin) dan label tipe `exam` ("Nilai ulangan") untuk transaksi ledger.

- [ ] **Step 1: Test gagal** `ReviewExamTest`: approve 95 → saldo +5, status approved, `points_awarded=5`, `final_score=95`; approve dengan `score=75` pada klaim 95 → +3, `final_score=75`; approve nilai 60 → approved, saldo 0, tidak ada baris ledger; approve dua kali → `assertSessionHasErrors('review')` dan satu entri ledger; `score=101` → error validasi, tetap pending; foto dihapus lalu approve → error, tetap pending, tanpa poin; reject → rejected + note, saldo tetap, anak bisa kirim ulang (cek lewat `ExamService::submit` setelah >10 detik / data berbeda); anak yang mencoba route parent → 403; dashboard parent menampilkan ulangan pending dengan foto.
- [ ] **Step 2:** Run `php vendor/bin/phpunit tests/Feature/ReviewExamTest.php` → Expected: FAIL.
- [ ] **Step 3:** Implement. Tangkapan error di controller memakai `decide()` yang ada.
- [ ] **Step 4:** Run full suite + `graphify update .` → Expected: PASS.
- [ ] **Step 5:** Tambah entri CHANGELOG [Unreleased] ("Nilai ulangan: anak kirim, parent verifikasi, poin A/B/C"); commit `feat: parents verify exam scores and award points`; buka PR, review, merge, deploy (`php artisan migrate --force`, cache clear, cek `/` 200, kepemilikan `.env` dan `storage/framework/views` tetap benar).
