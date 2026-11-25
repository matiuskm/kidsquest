# KidsQuest — Design

Tanggal: 2026-11-25. Pemilik: Kris (satu-satunya parent). Target: https://kidsquest.zarvenio.id

## Tujuan & alasan
Web app mini (PWA) untuk anak-anak Kris. Setiap hari anak dapat "misi", mengirim foto bukti, dan setelah diverifikasi parent mereka dapat poin. Poin ditukar hadiah yang disiapkan parent. Tujuannya memotivasi anak mengerjakan tugas rumah dan kebiasaan harian dengan cara yang menyenangkan, sambil parent tetap memegang kontrol penuh.

## Goals
- Parent: CRUD tugas, CRUD hadiah, CRUD anak (tambah/edit/hapus/reset PIN), lihat progres, approve/reject penyelesaian tugas dan klaim hadiah.
- Anak: lihat misi hari ini, kirim foto bukti, lihat saldo poin, tukar hadiah, ganti PIN sendiri.
- Tugas: sekali, harian, mingguan, bulanan. Bisa ditugaskan ke satu atau beberapa anak.
- PWA installable di Android, notifikasi push, suara dan animasi seperlunya.
- Foto dikompres di server sebelum disimpan.

## Non-goals
- Tidak ada registrasi publik; hanya parent yang membuat anak.
- Tidak ada multi-parent, multi-keluarga, atau multi-tenant.
- Tidak ada leaderboard antar anak atau fitur sosial lain. (Chat ditambahkan kemudian: lihat `2026-11-25-chat-design.md`.)
- Bukan app native; tidak ada iOS push (anak semua Android).
- Tidak ada fitur offline-first; service worker hanya untuk install, cache aset, dan push.

## Anti-goals (gagal walau "jalan")
- Parent dashboard rumit. Harus bisa dipakai dengan sedikit tap.
- Anak bisa curang: poin tanpa verifikasi, atau melihat/mengubah data anak lain.
- Kode spaghetti: logika bisnis di controller/Blade, duplikasi aturan poin.

## Keputusan yang sudah disetujui Kris
- Tiap anak punya HP Android sendiri; tablet dan laptop dipakai bersama. Parent membuka dari device apa pun.
- Stack gampang dijalankan dan dimaintain: **Laravel + Blade + Alpine.js + Tailwind + MySQL** (approach A; B Livewire dan C SPA ditolak karena lebih berat/ribet).
- Foto dikompres jadi WebP sebelum disimpan.
- Deploy ke kidsquest.zarvenio.id; Kris setup DNS Cloudflare, Claude setup nginx dan SSL di server.
- Tugas ditolak: anak bisa kirim ulang. Tugas harian terlewat hangus. Poin yang sudah masuk tidak dibatalkan kecuali koreksi manual parent. Klaim hadiah memotong poin saat diklaim, dikembalikan bila ditolak.
- Hanya satu akun parent.

## Approaches (ringkas)
| | Trade-off | Hasil |
|---|---|---|
| A. Laravel + Blade/Alpine/Tailwind | server-rendered, satu app, sama seperti Ministerio; animasi ditulis manual | **Dipilih** |
| B. Laravel + Livewire | JS lebih sedikit, tapi lapisan "magic" tambahan dan lebih berat di RAM 2GB | Ditolak |
| C. SPA + API | fleksibel, tapi dua project, build step, auth token | Ditolak |

## Desain (calls Claude, bisa dikoreksi)

### Login
- Parent: email + password (Laravel session). Akun dibuat lewat seeder/artisan command, tidak ada halaman daftar.
- Anak: layar awal menampilkan kartu avatar semua anak, tap avatar lalu masukkan PIN 4 digit. Berlaku di HP, tablet, dan laptop yang sama. Sesi panjang (30 hari) supaya tidak sering input PIN. Percobaan salah dibatasi (5x lalu kunci 5 menit per anak) lewat rate limiter Laravel.
- PIN awal diset parent saat membuat anak; anak bisa menggantinya di menu Profil. Parent bisa reset PIN. PIN disimpan di-hash.
- Anak hanya bisa mengakses data miliknya (policy per model).

### Data
- `users`: role `parent|child`, name, avatar (emoji/warna), email+password (parent) atau pin_hash (anak).
- `tasks`: title, description, points, recurrence (`once|daily|weekly|monthly`), weekdays (untuk weekly), month_day (untuk monthly), start_date, end_date (nullable), active. Pivot `task_child` untuk penugasan.
- `submissions`: task_id, child_id, period_date (tanggal periode yang dikerjakan), photo_path, status `pending|approved|rejected`, reject_note, reviewed_at. Unik aktif per (task, child, period_date) selain yang rejected.
- `rewards`: name, cost, image (opsional), active.
- `redemptions`: reward_id, child_id, cost (snapshot), status `pending|approved|rejected`.
- `point_transactions` (ledger, append-only): child_id, amount (+/-), type `earn|redeem|refund|adjust`, ref polimorfik. Saldo = SUM(amount); satu-satunya sumber kebenaran poin.
- `push_subscriptions` (dari paket webpush).

### Aturan inti
- Tugas "hari ini" dihitung dari aturan recurrence, tanpa cron/scheduler. Tugas once muncul sampai selesai (atau end_date). Daily hangus esok harinya karena period_date berbeda.
- Approve submission: tulis ledger `earn`. Reject: status rejected + catatan, anak bisa kirim ulang untuk periode sama.
- Klaim hadiah: validasi saldo cukup, tulis ledger `redeem` (negatif) saat klaim. Reject: tulis `refund`. Approve: hanya ubah status.
- Parent dapat `adjust` manual (+/-) dengan catatan.
- Zona waktu aplikasi: Asia/Jakarta (asumsi; ganti di config bila WITA).

### Foto
- Input `accept="image/*" capture="environment"`. Server: auto-orient via EXIF, resize sisi terpanjang max 1280px, simpan WebP q≈75 dengan GD bawaan PHP (WebP support sudah terverifikasi di server). Batas upload 10 MB (nginx + PHP). Disimpan di storage privat dan disajikan lewat route terautentikasi (parent, atau anak pemilik), bukan folder publik.

### Notifikasi & efek
- Web Push (VAPID): ke parent saat anak submit atau klaim; ke anak saat disetujui/ditolak dan saat poin masuk. Kirim sinkron dengan try/catch (cukup untuk skala keluarga; upgrade ke queue bila perlu).
- Suara (file audio kecil via Alpine helper tunggal): poin masuk, hadiah berhasil ditukar, ditolak, submit terkirim. Autoplay perlu gesture pengguna, jadi suara diaktifkan setelah interaksi pertama.
- Animasi: CSS saja (konfeti saat poin masuk, bounce pada kartu, progress). Tanpa library animasi.

### UI
- Anak: warna-warni, font besar, tombol besar, navigasi bawah (Misi, Hadiah, Saya), saldo poin selalu terlihat.
- Parent: sederhana. Beranda = antrean "Perlu diperiksa" (submission + klaim) dengan foto, tombol Setuju/Tolak. Menu: Anak, Tugas, Hadiah, Riwayat. Bahasa UI: Indonesia.

### Struktur kode (DRY/SOLID, tanpa over-engineering)
- Controller tipis + FormRequest untuk validasi + Policy untuk otorisasi.
- Service kecil, satu tanggung jawab masing-masing: `PointsLedger` (earn/redeem/refund/adjust/balance), `TaskSchedule` (apakah tugas jatuh pada tanggal X), `PhotoStore` (kompres+simpan), `ReviewService` (approve/reject submission & redemption), `Notifier` (push).
- Satu komponen Blade untuk kartu tugas/hadiah/antrean yang dipakai ulang di sisi anak dan parent.

### Deploy
- `/var/www/kidsquest`, nginx vhost `kidsquest.zarvenio.id` → php-fpm 8.3, database MySQL `kidsquest` dengan user sendiri, SSL via certbot (butuh DNS Cloudflare menunjuk ke server; Cloudflare mode Full (strict)). Tidak mengganggu vhost Ministerio. Server RAM 2 GB dibagi dengan Ministerio, jadi tanpa queue worker dan tanpa proses tambahan.

### Pengujian
Feature test untuk: ledger/saldo, recurrence, alur approve/reject submission, alur klaim+refund, otorisasi antar anak, throttle PIN, kompresi foto.

## Keputusan tambahan setelah builder check (calls Claude)
- **Multi-anak per tugas:** selesai dihitung per anak. Tiap anak punya submission dan poin sendiri; tugas "sekali" tetap terbuka untuk anak lain sampai masing-masing selesai.
- **Keunikan submission:** MySQL tanpa partial unique index, jadi dicek di kode dalam transaksi (lock baris anak). Submission rejected tidak dihitung, sehingga anak bisa kirim ulang.
- **Periode:** `period_date` = tanggal kejadian tugas. Weekly = tiap hari yang dipilih di `weekdays` adalah satu kejadian sendiri (mis. Sen+Rab = dua kejadian per minggu). Monthly = pada `month_day`, bila bulan lebih pendek dipakai hari terakhir bulan itu. Anak hanya bisa mengirim untuk hari ini, tidak untuk hari kemarin.
- **Sesi anak:** tombol "Ganti anak / Keluar" selalu ada. Layar awal menampilkan semua anak, PIN satu-satunya pengaman antar saudara. Itu diterima untuk konteks keluarga.
- **Throttle PIN:** per anak saja. Risiko saudara sengaja mengunci diterima; parent bisa reset PIN.
- **Klaim hadiah:** pengecekan saldo dan penulisan ledger dalam satu transaksi dengan lock, jadi tidak bisa minus. `adjust` manual juga tidak boleh membuat saldo di bawah 0. Hadiah bisa diklaim berulang selama poin cukup; tanpa stok.
- **Hapus data:** soft delete untuk anak, tugas, dan hadiah supaya riwayat dan ledger utuh. Menghapus anak juga menghapus foto dan push subscription-nya.
- **Push:** parent menerima di semua device yang berlangganan. Subscription anak terikat ke anak yang login dan dihapus saat keluar. VAPID key dibuat saat deploy dan disimpan di `.env`.
- **Foto:** disimpan tanpa batas waktu (25 GB kosong, ~150 KB per foto); parent bisa hapus manual. Foto tidak bisa diganti selama masih pending. Submission tanpa file foto tidak bisa di-approve.
- **Riwayat parent:** daftar submission dan klaim yang sudah diputuskan plus mutasi poin, per anak.

## Tambahan setelah approval (permintaan Kris, 2026-11-25)
- **Profil anak:** anak bisa mengubah profilnya sendiri: nama panggilan, avatar/warna, dan PIN. Tanggal lahir tidak bisa diubah anak.
- **Bonus ulang tahun:** parent mengisi tanggal dan bulan lahir (tanpa tahun) saat membuat akun anak; setelah itu tidak bisa diubah, termasuk oleh parent lewat UI. Pada hari ulang tahun (Asia/Jakarta) anak otomatis dapat bonus poin lewat ledger bertipe `birthday`, sekali per tahun (unik per anak+tahun, idempotent), diberikan saat anak pertama kali membuka app hari itu dan tanpa cron. Jumlah bonus = nilai config `BIRTHDAY_BONUS_POINTS`, default 50 (call Claude, bisa diubah). Lahir 29 Februari dirayakan 28 Februari di tahun non-kabisat. Anak mendapat suara dan konfeti saat bonus masuk.
- **Repository:** https://github.com/matiuskm/kidsquest.git. Semua perubahan lewat branch + pull request; Claude boleh merge PR-nya sendiri (diizinkan Kris untuk repo ini) supaya setiap perubahan tercatat.
- **Versioning:** SemVer. Rilis ditandai git tag `vMAJOR.MINOR.PATCH` + GitHub Release, `CHANGELOG.md` dijaga, versi tampil di footer dashboard parent. Mulai 0.1.0; 1.0.0 saat pertama live di kidsquest.zarvenio.id.

## Diserahkan ke builder
Nama tabel/kolom persis, warna dan ilustrasi, pilihan emoji avatar, file suara (royalty-free), wording UI, ukuran ikon PWA, urutan implementasi.
