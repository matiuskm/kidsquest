# KidsQuest Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** PWA misi harian untuk anak (foto bukti → verifikasi parent → poin → tukar hadiah), live di https://kidsquest.zarvenio.id.

**Architecture:** Satu aplikasi Laravel server-rendered (Blade + Alpine.js + Tailwind), MySQL, satu guard `web` dengan kolom `role` (`parent|child`). Logika bisnis di service kecil (`PointsLedger`, `TaskSchedule`, `PhotoStore`, `MissionService`, `ReviewService`, `BirthdayBonus`, `Notifier`); controller tipis, FormRequest + Policy. Poin hanya dari ledger append-only.

**Tech Stack:** Laravel (versi stabil terbaru yang mendukung PHP 8.3), PHP 8.3 + GD, MySQL, Vite + Tailwind + Alpine.js, `laravel-notification-channels/webpush`, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-25-kidsquest-design.md`

## Global Constraints
- Satu parent saja, tanpa registrasi publik; anak dibuat parent. Bahasa UI: Indonesia. Timezone `Asia/Jakarta`.
- PIN anak 4 digit, di-hash; throttle 5 gagal → kunci 5 menit **per anak**. Sesi anak 30 hari; tombol "Ganti anak / Keluar" selalu ada.
- Foto: `accept="image/*" capture="environment"`, server auto-orient EXIF, resize sisi terpanjang max 1280px, WebP q75 via GD, batas upload 10 MB, disimpan di disk privat dan disajikan lewat route terautentikasi.
- Tugas: `once|daily|weekly|monthly`; weekly = tiap hari di `weekdays` satu kejadian; monthly = `month_day`, dijepit ke hari terakhir bulan pendek; anak hanya kirim untuk hari ini; selesai dihitung per anak.
- Ledger types: `earn|redeem|refund|adjust|birthday`. Saldo = SUM(amount), tidak boleh minus (klaim maupun adjust), dicek dalam transaksi dengan lock.
- Bonus ulang tahun: `BIRTHDAY_BONUS_POINTS` default 50, sekali per anak per tahun, tanggal+bulan lahir tidak bisa diubah setelah dibuat, 29 Feb dirayakan 28 Feb di tahun non-kabisat, tanpa cron.
- Soft delete untuk anak, tugas, hadiah; hapus anak juga hapus foto + push subscription.
- Tanpa queue worker, tanpa cron, tanpa library animasi; RAM server 2 GB dibagi dengan Ministerio. DB `kidsquest`, nginx vhost sendiri, tidak mengganggu vhost Ministerio.
- Git: repo `https://github.com/matiuskm/kidsquest.git`; satu task = satu branch + satu PR, di-merge (squash) oleh Claude setelah test hijau; SemVer + tag `vX.Y.Z` + `CHANGELOG.md`.
- Commit message diakhiri `Co-Authored-By: Claude Sonnet 5.5 <noreply@anthropic.com>`; deskripsi PR diakhiri `🤖 Generated with [Claude Code](https://claude.com/claude-code)`.
- Setelah mengubah kode jalankan `graphify update .` (aturan `CLAUDE.md` proyek).

## Review Focus
1. **Upload bukan gambar / korup / sangat besar** (mis. `.txt` dinamai `.jpg`, 15 MB): ditolak dengan pesan jelas, tidak ada file tersisa, tidak ada submission dibuat → Task 5.
2. **Dobel tap kirim/klaim/approve** (request ganda bersamaan): hanya satu submission, satu klaim, satu entri ledger → Task 5, 6, 7.
3. **Anak membuka foto, submission, atau klaim anak lain lewat URL/ID**: 403/404 → Task 5, 6.
4. **Tepi ulang tahun**: 29 Feb di tahun non-kabisat, jam 23:59 vs 00:01 WIB, buka app berkali-kali di hari yang sama → satu bonus → Task 8.
5. **Hapus/nonaktifkan tugas, hadiah, atau anak yang masih punya submission/klaim pending**: riwayat utuh, tidak ada error 500, klaim pending dikembalikan poinnya → Task 4, 7.

---

## File Structure
- `app/Enums/`: `Role`, `Recurrence`, `ReviewStatus` (`pending|approved|rejected`), `LedgerType`, `MissionStatus` (`todo|pending|rejected|done`)
- `app/Models/`: `User`, `Task`, `Submission`, `Reward`, `Redemption`, `PointTransaction`
- `app/Services/`: `PointsLedger`, `TaskSchedule`, `PhotoStore`, `MissionService`, `RewardService`, `ReviewService`, `BirthdayBonus`, `Notifier`
- `app/Http/Controllers/Parent/`: `DashboardController`, `ChildController`, `TaskController`, `RewardController`, `ReviewController`, `HistoryController`
- `app/Http/Controllers/Child/`: `MissionController`, `RewardController`, `ProfileController`
- `app/Http/Controllers/`: `AuthController` (parent login), `ChildLoginController`, `PhotoController`, `PushSubscriptionController`
- `app/Http/Middleware/`: `EnsureRole`, `AwardBirthdayBonus`
- `resources/views/`: `layouts/{parent,child}.blade.php`, `components/` (kartu tugas, kartu hadiah, item antrean), `parent/*`, `child/*`
- `public/`: `manifest.webmanifest`, `sw.js`, `icons/`, `sounds/`
- `tests/Feature/`, `tests/Unit/`
- Root: `CHANGELOG.md`, `VERSION` (dibaca ke `config('app.version')`)

---

### Task 1: Repo, scaffold, workflow, versioning (PR #1, tag v0.1.0)

**Files:**
- Create: Laravel skeleton di `/var/www/kidsquest`, `CHANGELOG.md`, `VERSION` (`0.1.0`), `config/app.php` baca version, `phpunit.xml` pakai MySQL `kidsquest_test`
- Keep: `docs/`, `CLAUDE.md`, `.claude/`

**Interfaces:** Produces `config('app.version'): string`; `.env.example` berisi `BIRTHDAY_BONUS_POINTS=50`, `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`.

- [ ] **Step 1:** Verifikasi akses: `gh auth status` dan `git ls-remote https://github.com/matiuskm/kidsquest.git` berhasil; `sudo mysql -e "select 1"` berhasil. Bila salah satu gagal, **berhenti dan minta Kris** (login `gh`, token, atau akses MySQL).
- [ ] **Step 2:** Buat database dan user MySQL `kidsquest` + `kidsquest_test` (user terpisah, hanya dari localhost). Simpan kredensial di `.env` (tidak di-commit).
- [ ] **Step 3:** `composer create-project laravel/laravel` ke direktori sementara, pindahkan isi ke `/var/www/kidsquest` tanpa menimpa `docs/`, `CLAUDE.md`, `.claude/`. Tambah Tailwind + Alpine via npm, set timezone/locale `id`.
- [ ] **Step 4:** Test: `tests/Feature/VersionTest::test_version_matches_file` — `config('app.version') === trim(file_get_contents(base_path('VERSION')))`. Jalankan → FAIL; implement `config/app.php` `'version' => trim(@file_get_contents(base_path('VERSION')) ?: 'dev')`; jalankan `php artisan test` → PASS.
- [ ] **Step 5:** `git init`, remote `origin`, branch `main`, commit awal (pastikan `.gitignore` mengecualikan `.env`, `vendor`, `node_modules`, `storage` privat, `graphify-out/cache`), push `main`.
- [ ] **Step 6:** Branch `feat/scaffold` bila ada perubahan sisa; buka PR dengan `gh pr create`, merge `gh pr merge --squash --delete-branch`, tag `v0.1.0` + `gh release create v0.1.0` dari isi `CHANGELOG.md`.

> Alur ini berulang di setiap task berikut: `git switch -c feat/<nama>` → test → implement → `php artisan test` → `graphify update .` → commit → `git push -u` → `gh pr create` → `gh pr merge --squash --delete-branch` → `git switch main && git pull`.

---

### Task 2: Auth, role, dan layout dasar (`feat/auth`)

**Files:**
- Create: `Enums/Role.php`, migration kolom `users` (`role`, `avatar`, `pin_hash` nullable, `birthday_month`, `birthday_day`, soft deletes; `email`/`password` nullable untuk anak), `Http/Middleware/EnsureRole.php`, `AuthController`, `ChildLoginController`, `console command kidsquest:make-parent {email} {name}`, `layouts/parent.blade.php`, `layouts/child.blade.php`, view login
- Test: `tests/Feature/AuthTest.php`

**Interfaces:**
- Produces: `User::isParent(): bool`, `User::isChild(): bool`; middleware alias `role:parent|child`; route names `login`, `child.pick`, `child.pin`, `logout`, `parent.dashboard`, `child.missions`.
- `ChildLoginController@store(Request)`: validasi `child_id` + `pin` (`digits:4`), `RateLimiter` key `pin:{child_id}` max 5 / 5 menit, login dengan remember 30 hari.

- [ ] **Step 1: Tests** — `test_parent_logs_in_with_email_password`; `test_child_logs_in_with_correct_pin`; `test_wrong_pin_rejected`; `test_sixth_attempt_is_throttled_even_with_correct_pin`; `test_throttle_is_per_child` (anak B tetap bisa login saat anak A terkunci); `test_pin_must_be_exactly_4_digits` (`12a4`, `123`, `12345` ditolak); `test_child_cannot_open_parent_routes` (403) dan sebaliknya; `test_no_register_route_exists` (404 pada `/register`); `test_command_creates_parent`.
- [ ] **Step 2:** Jalankan → FAIL.
- [ ] **Step 3:** Implement sesuai Interfaces. Layar awal `/` menampilkan kartu avatar semua anak (non-soft-deleted) lalu form PIN; link kecil "Masuk sebagai orang tua". Layout child: font besar, warna-warni, nav bawah (Misi, Hadiah, Saya) + tombol Keluar; layout parent: sederhana, nav atas.
- [ ] **Step 4:** `php artisan test` → PASS. PR + merge.

---

### Task 3: PointsLedger (`feat/ledger`)

**Files:**
- Create: `Enums/LedgerType.php`, `Models/PointTransaction.php`, migration `point_transactions` (`user_id`, `amount` int, `type`, `ref_type`/`ref_id` nullable, `note` nullable, `year` nullable, timestamps; unik `(user_id, type, year)` hanya efektif untuk `birthday` karena `year` null untuk tipe lain), `Services/PointsLedger.php`
- Test: `tests/Unit/PointsLedgerTest.php` (memakai DB)

**Interfaces — Produces** (`final class PointsLedger`, semua mutasi dalam `DB::transaction` dengan `lockForUpdate` pada baris anak):
- `balance(User $child): int`
- `earn(User $child, int $points, Model $ref): PointTransaction`
- `redeem(User $child, int $cost, Model $ref): PointTransaction` — throws `InsufficientPoints`
- `refund(User $child, int $cost, Model $ref): PointTransaction`
- `adjust(User $child, int $amount, string $note): PointTransaction` — throws `InsufficientPoints` bila saldo < 0
- `birthday(User $child, int $year, int $points): ?PointTransaction` — `null` bila sudah ada untuk tahun itu

- [ ] **Step 1: Tests** — `test_balance_is_sum_of_ledger`; `test_redeem_reduces_balance`; `test_redeem_more_than_balance_throws_and_writes_nothing`; `test_adjust_cannot_go_negative`; `test_adjust_negative_within_balance_ok`; `test_birthday_twice_same_year_returns_null_and_single_row`; `test_refund_restores_points`.
- [ ] **Step 2–4:** FAIL → implement → PASS. PR + merge.

---

### Task 4: Tugas + jadwal (`feat/tasks`)

**Files:**
- Create: `Enums/Recurrence.php`, `Models/Task.php` (soft deletes, pivot `task_child`), migrations, `Services/TaskSchedule.php`, `Parent/TaskController` (CRUD), `StoreTaskRequest`, view parent tugas (daftar, form, pilih anak, pilih recurrence)
- Test: `tests/Unit/TaskScheduleTest.php`, `tests/Feature/ParentTaskTest.php`

**Interfaces — Produces:**
- `TaskSchedule::occursOn(Task $task, CarbonImmutable $date): bool`
- `TaskSchedule::dueFor(User $child, CarbonImmutable $date): Collection` (tugas aktif, ditugaskan ke anak, jatuh pada `$date`, dalam `start_date`..`end_date`; tugas `once` tetap muncul sampai anak itu menyelesaikannya atau lewat `end_date`)
- `Task` fields: `title`, `description`, `points` (int ≥ 1), `recurrence`, `weekdays` (array 0–6 | null), `month_day` (1–31 | null), `start_date`, `end_date`, `active`.

- [ ] **Step 1: Tests** — `daily` jatuh tiap hari; `weekly` `[1,3]` hanya Senin & Rabu; `monthly` 31 jatuh 28 Feb 2027 dan 29 Feb 2028 dan 30 Apr; `once` tetap muncul hari-hari setelah `start_date` sampai selesai oleh anak itu tapi **tetap muncul untuk anak lain**; tugas di luar `start_date/end_date` tidak muncul; `active=false` tidak muncul; parent create/update/delete lewat HTTP; validasi (`points` 0 ditolak, weekly tanpa `weekdays` ditolak); **soft delete tugas dengan submission pending tidak error dan submission tetap bisa di-review**.
- [ ] **Step 2–4:** FAIL → implement → PASS. PR + merge.

---

### Task 5: Misi anak, upload foto, PhotoStore (`feat/missions`)

**Files:**
- Create: `Models/Submission.php`, migration `submissions` (`task_id`, `child_id`, `period_date`, `photo_path`, `status`, `reject_note`, `reviewed_at`), `Enums/ReviewStatus.php`, `Enums/MissionStatus.php`, `Services/PhotoStore.php`, `Services/MissionService.php`, `Child/MissionController`, `PhotoController`, `StoreSubmissionRequest`, view `child/missions`, komponen kartu tugas
- Modify: `config/filesystems.php` (disk `private`), `php.ini`/nginx limit dicatat untuk Task 10
- Test: `tests/Unit/PhotoStoreTest.php`, `tests/Feature/MissionTest.php`

**Interfaces:**
- Produces: `PhotoStore::store(UploadedFile $file): string` (path di disk `private`, `.webp`), `PhotoStore::delete(string $path): void`; throws `InvalidImage` bila bukan gambar.
- Produces: `MissionService::todayFor(User $child, CarbonImmutable $date): Collection` (item: `Task` + `MissionStatus`); `MissionService::submit(User $child, Task $task, UploadedFile $photo, CarbonImmutable $date): Submission` — throws `NotDueToday`, `AlreadySubmitted`; dalam transaksi dengan lock pada baris anak; submission `rejected` boleh dikirim ulang.
- Route `GET /photos/{submission}` → `PhotoController@show` (policy: parent atau anak pemilik).

- [ ] **Step 1: Tests** — foto JPEG 4000×3000 jadi WebP sisi terpanjang ≤ 1280 dan ukuran jauh lebih kecil; foto EXIF rotated 90° keluar dalam orientasi benar; `.txt` dinamai `.jpg` → `InvalidImage`, tidak ada file ditulis; upload >10 MB ditolak validasi; kirim untuk tugas yang tidak jatuh hari ini → ditolak; kirim dua kali (juga dua request berurutan cepat) → hanya satu submission; setelah rejected boleh kirim ulang; anak A tidak bisa `GET /photos/{submission anak B}` (403); status misi `todo→pending→done`; tugas anak lain tidak muncul di daftar.
- [ ] **Step 2–4:** FAIL → implement (GD: `imagecreatefromstring`, `exif_read_data` orientasi, `imagescale`, `imagewebp($im, $path, 75)`) → PASS.
- [ ] **Step 5:** Form kirim: input file kamera, pratinjau, Alpine disable tombol setelah klik pertama. PR + merge.

---

### Task 6: Review parent + antrean (`feat/review`)

**Files:**
- Create: `Services/ReviewService.php` (bagian submission), `Parent/ReviewController`, `Parent/DashboardController`, view dashboard (antrean "Perlu diperiksa"), komponen item antrean, `Parent/HistoryController` + view riwayat
- Test: `tests/Feature/ReviewSubmissionTest.php`

**Interfaces — Produces:**
- `ReviewService::approveSubmission(Submission $s, User $parent): void` — hanya dari `pending`; membutuhkan foto ada; menulis `PointsLedger::earn` dalam transaksi; idempotent (approve kedua = error `AlreadyReviewed`, ledger tetap satu).
- `ReviewService::rejectSubmission(Submission $s, User $parent, string $note): void`

- [ ] **Step 1: Tests** — approve menambah saldo sebesar `points` tugas; approve dua kali → satu entri ledger; reject menyimpan catatan dan tidak menambah poin; anak tidak bisa memanggil route review (403); approve submission tanpa file foto ditolak; riwayat per anak menampilkan keputusan dan mutasi poin; dashboard menampilkan hitungan pending.
- [ ] **Step 2–4:** FAIL → implement → PASS. PR + merge.

---

### Task 7: Hadiah + klaim (`feat/rewards`)

**Files:**
- Create: `Models/Reward.php` (soft deletes), `Models/Redemption.php`, migrations, `Parent/RewardController` (CRUD), `Child/RewardController` (daftar + klaim), `StoreRewardRequest`, view parent/anak; perluas `ReviewService`
- Test: `tests/Feature/RewardTest.php`

**Interfaces — Produces:**
- `RewardService::claim(User $child, Reward $reward): Redemption` — transaksi + lock, `PointsLedger::redeem` bila saldo cukup, snapshot `cost`; throws `InsufficientPoints`.
- `ReviewService::approveRedemption(Redemption $r, User $parent): void`; `rejectRedemption(Redemption $r, User $parent, ?string $note): void` → `PointsLedger::refund`.

- [ ] **Step 1: Tests** — klaim memotong saldo seketika; klaim dengan poin kurang ditolak; dua klaim bersamaan dengan saldo cukup hanya untuk satu → satu berhasil; reject mengembalikan poin tepat sekali (reject dua kali tidak menggandakan); approve hanya mengubah status; hadiah bisa diklaim berulang selama poin cukup; mengubah `cost` hadiah tidak mengubah `cost` klaim lama; **soft delete hadiah dengan klaim pending tetap bisa di-approve/reject**; hadiah nonaktif tidak muncul untuk anak; parent `adjust` manual lewat form dengan catatan.
- [ ] **Step 2–4:** FAIL → implement → PASS. Antrean dashboard memuat klaim pending. PR + merge.

---

### Task 8: Anak & profil, bonus ulang tahun (`feat/children-birthday`)

**Files:**
- Create: `Parent/ChildController` (CRUD, reset PIN, hapus anak → `PhotoStore::delete` semua foto + hapus push subscription), `Child/ProfileController` (nama panggilan, avatar, ganti PIN), `Services/BirthdayBonus.php`, `Http/Middleware/AwardBirthdayBonus.php` (dipasang di grup route anak), view
- Modify: `Models/User` (boot hook yang melempar bila `birthday_month`/`birthday_day` diubah setelah terisi)
- Test: `tests/Feature/ChildManagementTest.php`, `tests/Feature/BirthdayBonusTest.php`

**Interfaces — Produces:**
- `BirthdayBonus::award(User $child, CarbonImmutable $today): bool` — benar bila bonus baru diberikan; memakai `PointsLedger::birthday($child, $today->year, config('kidsquest.birthday_bonus'))`; 29 Feb → hari rayanya 28 Feb di tahun non-kabisat. Flash session `birthday` memicu konfeti + suara di layout anak.

- [ ] **Step 1: Tests** — parent membuat anak dengan tanggal+bulan lahir; update anak mengabaikan/menolak perubahan tanggal lahir (juga lewat `User::update` langsung → exception); anak ganti nama panggilan/avatar/PIN sendiri; anak tidak bisa ganti anak lain atau tanggal lahir; PIN lama tidak berlaku setelah diganti; reset PIN oleh parent; hari ulang tahun → +`BIRTHDAY_BONUS_POINTS` sekali walau 5× buka halaman; hari berikutnya tidak ada bonus; tahun berikutnya dapat lagi; lahir 29 Feb mendapat bonus 28 Feb 2027 (non-kabisat) dan 29 Feb 2028; `Carbon::setTestNow('2027-03-01 00:01 Asia/Jakarta')` bukan hari ulang tahun 28 Feb; `23:59` tanggal 28 Feb WIB masih dapat; hapus anak menghapus foto dan subscription, ledger utuh.
- [ ] **Step 2–4:** FAIL → implement → PASS. PR + merge. Setelah merge: tag `v0.2.0` (fitur inti lengkap).

---

### Task 9: PWA, push, suara, animasi (`feat/pwa-push`)

**Files:**
- Create: `public/manifest.webmanifest`, `public/sw.js` (cache aset statis + handler `push`/`notificationclick`), ikon 192/512 di `public/icons/`, `public/sounds/{points,redeem,reject,submit}.mp3` (royalty-free), `resources/js/sounds.js` (satu helper Alpine `$store.sound.play(name)`, aktif setelah gestur pertama), `resources/css/animations.css` (konfeti CSS, bounce kartu), `Services/Notifier.php`, `PushSubscriptionController`
- Modify: layout parent dan child (link manifest, registrasi SW, tombol "Aktifkan notifikasi"), `ReviewService`/`MissionService`/`RewardService`/`BirthdayBonus` memanggil `Notifier`
- Test: `tests/Feature/PushTest.php`, `tests/Feature/PwaTest.php`

**Interfaces — Produces:**
- `Notifier::toParent(string $title, string $body, string $url): void`, `Notifier::toChild(User $child, string $title, string $body, string $url): void` — kirim ke semua subscription pemilik; kegagalan ditangkap dan di-log, tidak pernah menggagalkan request.
- `POST /push/subscribe`, `DELETE /push/subscribe` (terikat ke user login; logout anak menghapus subscription device itu).

- [ ] **Step 1: Tests** — `/manifest.webmanifest` valid (name, start_url, display `standalone`, ikon 192+512); subscribe menyimpan terikat user; logout anak menghapus subscription; submit anak memanggil `Notifier::toParent` (fake channel); approve memanggil `toChild`; `Notifier` yang melempar exception tidak mengubah respons 200/redirect; anak tidak bisa menghapus subscription user lain.
- [ ] **Step 2–4:** FAIL → implement → PASS. Generate VAPID key dengan `php artisan webpush:vapid` (disimpan di `.env`).
- [ ] **Step 5:** Pasang suara+animasi di momen: poin masuk (suara `points` + konfeti), klaim berhasil (`redeem`), ditolak (`reject`), submit terkirim (`submit`), bonus ulang tahun. PR + merge.

---

### Task 10: Deploy + rilis 1.0.0 (`chore/deploy`)

**Files:**
- Create: `deploy/nginx/kidsquest.zarvenio.id.conf` (disalin ke `/etc/nginx/sites-available`), `deploy/README.md` (langkah rilis berikutnya: `git pull`, `composer install --no-dev -o`, `npm ci && npm run build`, `php artisan migrate --force`, `php artisan optimize`)

**Interfaces:** Consumes semua task sebelumnya. Produces situs live di `https://kidsquest.zarvenio.id`.

- [ ] **Step 1:** Tentukan owner/permission `storage` dan `bootstrap/cache` untuk user php-fpm (`www-data`); `.env` produksi (`APP_ENV=production`, `APP_DEBUG=false`, DB, VAPID, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=sync`).
- [ ] **Step 2:** `composer install --no-dev -o`, `npm ci && npm run build`, `php artisan key:generate`, `migrate --force`, `kidsquest:make-parent <email> <nama>` (minta email/nama Kris), `optimize`.
- [ ] **Step 3:** Vhost nginx: `server_name kidsquest.zarvenio.id`, root `public`, `client_max_body_size 10m`, `fastcgi_pass` ke socket php-fpm 8.3, header keamanan seperti vhost Ministerio, blok `/.well-known` untuk certbot. `upload_max_filesize`/`post_max_size` PHP ≥ 10M. `nginx -t` dulu; **jangan** reload bila `nginx -t` gagal; verifikasi Ministerio tetap 200 setelah reload.
- [ ] **Step 4:** Cek DNS `kidsquest.zarvenio.id` menunjuk ke server; bila belum, **berhenti dan minta Kris** setup record Cloudflare. Lalu `certbot --nginx -d kidsquest.zarvenio.id` (atau `--webroot`), pastikan pembaruan otomatis aktif; minta Kris set Cloudflare SSL mode Full (strict).
- [ ] **Step 5:** Smoke test: `curl -I https://kidsquest.zarvenio.id` → 200; login parent; buat anak+tugas+hadiah; login anak dari HP Android (Kris), kirim foto, approve, tukar hadiah, push masuk; install PWA. Ministerio masih 200.
- [ ] **Step 6:** Update `CHANGELOG.md`, `VERSION` → `1.0.0`; PR + merge; tag `v1.0.0` + `gh release create`.

---

## Self-Review
- **Spec coverage:** login/PIN/throttle (T2), ledger+saldo tak minus (T3), tugas semua recurrence + per-anak (T4), foto/WebP/otorisasi (T5), approve/reject/riwayat (T6), hadiah/klaim/refund/adjust (T7), profil anak + bonus ulang tahun + soft delete anak (T8), PWA/push/suara/animasi (T9), deploy/SSL/versioning/PR (T1, T10). Soft delete tugas (T4), hadiah (T7), anak (T8). Foto tak bisa diganti saat pending: tercakup `AlreadySubmitted` (T5).
- **Konsistensi tipe:** `PointsLedger` (T3) dipakai T6/T7/T8 dengan signature sama; `ReviewService` dibuat di T6 dan diperluas di T7; `Notifier` (T9) dipanggil dari service T5–T8 lewat satu titik panggil yang ditambahkan di T9.
- **Proporsi:** plan berisi keputusan, signature, dan test; tanpa badan fungsi kecuali algoritma foto.
