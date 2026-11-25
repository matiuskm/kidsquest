# Changelog

Format mengikuti [Keep a Changelog](https://keepachangelog.com/), versi mengikuti [SemVer](https://semver.org/).

## [Unreleased]
### Added
- Chat 1-lawan-1 antar anak dan anak-parent (teks maks 500 karakter, notifikasi push, penanda belum dibaca, polling 5 detik). Parent hanya melihat chat miliknya sendiri; chat antar anak privat. Parent bisa mematikan chat antar anak per anak. Foto di chat belum ada.
- Parent bisa membatalkan persetujuan tugas atau nilai ulangan dari Riwayat: poin ditarik lagi (ditolak bila poin sudah terpakai) dan item kembali ke antrean periksa.
### Changed
- Menu bawah anak dirapikan: ikon di atas teks pendek (Misi, Nilai, Hadiah, Chat, Saya, Keluar), tab aktif ditandai.
### Changed
- Parent otomatis keluar bila diam 2 jam (sesi anak tetap 30 hari). Tugas yang sudah pernah dikerjakan tidak bisa diubah jadwal/tanggal mulainya. Kartu periksa ulangan menampilkan nilai terakhir yang disetujui untuk pelajaran yang sama.
- Dua folder: produksi (`/var/www/kidsquest`, selalu `main`) dan kerja (`/var/www/kidsquest-dev`), skrip `deploy/deploy.sh`; backup harian database dan foto (`deploy/backup.sh`).
- `deploy.sh` ikut membangun aset bila view Blade berubah (kelas Tailwind baru).
- Hapus halaman welcome bawaan, `laravel/tinker` pindah ke dev, cache service worker v2, poin dibaca sekali per halaman anak, test tidak menulis ke log produksi.

## [1.1.0] - 2026-11-25
### Added
- Nilai ulangan bisa berupa huruf (A-E, boleh + atau -; plus/minus tidak mengubah poin) untuk pelajaran yang dinilai dengan huruf, mis. bahasa Inggris. Poin: A 5, B 4, C 3, D dan E tidak dapat poin; parent bisa mengoreksi huruf.

## [1.0.0] - 2026-11-25
Rilis pertama yang live di https://kidsquest.zarvenio.id.
### Added
- PWA (manifest, service worker, ikon), notifikasi Web Push (parent: kiriman dan klaim; anak: keputusan), suara (poin masuk, klaim, ditolak, kirim), konfeti dan animasi kartu.
- Pengaman test (menolak database selain `kidsquest_test`), konfigurasi nginx dan dokumen deploy.
- Perbaikan hasil review: dobel-tap klaim hadiah, batas tebak PIN harian (20x/24 jam, dibuka saat orang tua reset PIN), unggahan >10 MB mendapat pesan ramah, cookie sesi `secure`, nginx HTTPS (http2).
- Landing page di `/` (tanpa nama anak); pilih anak + PIN pindah ke `/masuk-anak`. Tampilan parent diperbarui: header gradasi, navigasi aktif, kartu ringkasan, progres per anak.
- Nilai ulangan: anak kirim mata pelajaran, nilai, dan foto; parent memeriksa (boleh koreksi nilai). Poin A (90-100) 5, B (80-89) 4, C (70-79) 3, di bawah 70 tidak dapat poin; rentang di `config/kidsquest.php`.
- Hasil review nilai ulangan: satu ulangan per mata pelajaran yang menunggu keputusan (mencegah kiriman berulang), pesan error koreksi nilai tampil di dashboard, riwayat memakai label "Nilai ulangan" dan tidak menulis "+0".

## [0.2.0] - 2026-11-25
### Added
- Login orang tua (email+password) dan anak (avatar + PIN 4 digit, throttle 5x/5 menit per anak), middleware role, perintah `kidsquest:make-parent`.
- Ledger poin append-only (`PointsLedger`): earn, redeem, refund, adjust, birthday; saldo tidak bisa minus.
- Tugas (sekali/harian/mingguan/bulanan) dengan penjadwalan tanpa cron dan CRUD parent.
- Misi anak, upload foto bukti (dikompres WebP max 1280px, disimpan privat), status todo/pending/rejected/done.
- Review parent: antrean "Perlu diperiksa" di beranda, setuju/tolak foto bukti (idempotent), riwayat keputusan dan mutasi poin.
- Hadiah (CRUD parent), klaim anak (poin dipotong saat klaim, dikembalikan bila ditolak), penyesuaian poin manual.
- Kelola anak (tambah/ubah/hapus, reset PIN, tanggal lahir terkunci), profil anak (nama, avatar, PIN), bonus ulang tahun (sekali per tahun, 29 Feb dirayakan 28 Feb di tahun non-kabisat).

## [0.1.0] - 2026-11-25
### Added
- Scaffold Laravel (MySQL, Tailwind, Vite), versi aplikasi dari file `VERSION`.
