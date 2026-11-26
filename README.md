<p align="center"><img src="public/icons/icon-192.png" width="96" alt="KidsQuest"></p>

# KidsQuest

PWA keluarga: anak mengerjakan misi harian, kirim foto bukti, orang tua memeriksa, poin masuk, poin ditukar hadiah. Dibuat untuk anak-anak di keluarga sendiri, jadi sengaja kecil dan hemat memori (berjalan di server 2 GB bersama aplikasi lain).

Live: https://kidsquest.zarvenio.id · Bahasa tampilan: Indonesia dan English (dipilih per pengguna).

## Fitur

| Untuk | Fitur |
|---|---|
| Anak | Misi harian dengan foto bukti, nilai ulangan (angka atau huruf A-E), saldo dan mutasi poin, tukar poin dengan hadiah, chat 1-lawan-1, notifikasi push |
| Orang tua | Antrean "Perlu diperiksa" (setujui, tolak dengan catatan, koreksi nilai), batalkan persetujuan dari Riwayat, kelola anak, tugas, dan hadiah, poin gratis, buka kunci akun anak, chat |
| Otomatis | Bonus ulang tahun (50 poin, sekali setahun), jadwal tugas harian / mingguan (pilih hari) / sekali |

Aturan poin:
- Poin disimpan sebagai buku besar yang hanya bertambah (`earn`, `redeem`, `refund`, `adjust`, `birthday`, `exam`). Saldo = jumlah baris, tidak pernah disunting.
- Nilai ulangan: A (90-100) = 5, B (80-89) = 4, C (70-79) = 3, di bawah itu 0. Rentang ada di `config/kidsquest.php`.
- Membatalkan persetujuan menarik poinnya lagi dan ditolak bila poin sudah terpakai.

## Keamanan

- **HP keluarga:** daftar anak dan form PIN hanya muncul di perangkat yang pernah dipakai orang tua login.
- **Kunci otomatis:** 5x PIN salah berturut-turut mengunci akun. Orang tua dapat notifikasi, membuka dari Beranda atau halaman Anak, lalu anak wajib ganti PIN (tidak boleh sama dengan yang lama atau pola seperti 1111 dan 1234).
- **Sesi orang tua:** keluar otomatis setelah diam 10 menit, atau 2 jam bila "Ini HP saya" dicentang saat login. Polling chat tidak dihitung aktivitas.
- **Nilai ulangan dobel:** foto yang persis sama ditolak, maksimal 5 menunggu per anak, kartu periksa memberi peringatan bila pelajaran dan nilai yang sama sudah disetujui dalam 30 hari.
- **Lainnya:** reset PIN mengeluarkan sesi anak, foto disimpan privat, alamat push dibatasi ke layanan resmi, upload dibatasi frekuensinya.

Batasan: foto ulang dari kertas yang sama tidak terdeteksi otomatis, belum ada header CSP, dan perangkat keluarga tidak bisa dicabut satu per satu.

## Teknologi

Laravel 13 (PHP 8.3), MySQL (SQLite untuk dev), Blade + Alpine.js + Tailwind 4 + Vite, PHPUnit 12, web push (`laravel-notification-channels/webpush`) dan service worker. Tanpa websocket: chat memakai polling 5 detik.

## Menjalankan di lokal

```bash
composer setup          # install, .env, key, migrate, build aset
php artisan kidsquest:make-parent email@contoh.id "Nama Orang Tua"   # password minimal 10 karakter
php artisan serve       # terminal 1
npm run dev             # terminal 2 (vite)
```

Anak dibuat oleh orang tua lewat menu **Anak**. Orang tua login di `/masuk`. Sebelum anak bisa masuk di suatu perangkat, orang tua harus login sekali di perangkat itu lalu Keluar; sesudahnya anak memilih nama dan memasukkan PIN di `/masuk-anak`.

Notifikasi push butuh kunci VAPID: `php artisan webpush:vapid`, lalu isi `VAPID_*` di `.env`.

## Konfigurasi

| `.env` | Fungsi | Default |
|---|---|---|
| `BIRTHDAY_BONUS_POINTS` | Poin bonus ulang tahun | `50` |
| `SESSION_LIFETIME` | Sesi dalam menit (`43200` = 30 hari) | `43200` |
| `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` | Kunci push | kosong |

## Test

```bash
composer test           # atau: php artisan test
```

## Menambah bahasa atau teks

Teks Indonesia adalah kuncinya. Bungkus teks baru dengan `__('...')` dan tambahkan terjemahannya ke `lang/en.json`. Kalau lupa, teks tampil dalam bahasa Indonesia. Judul tugas, nama hadiah, dan isi chat tidak diterjemahkan.

## Deploy dan operasional

Lihat [`deploy/README.md`](deploy/README.md): nginx, SSL, dua folder (produksi dan kerja), `deploy/deploy.sh`, dan backup harian.

## Dokumen lain

- [`CHANGELOG.md`](CHANGELOG.md): riwayat rilis (SemVer)
- [`docs/superpowers/specs/`](docs/superpowers/specs/): desain tiap fitur
