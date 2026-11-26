# Pengerasan keamanan — desain (disetujui Kris, 2026-11-26)

Asal: pertanyaan Kris + audit baca-kode.

## Keputusan
1. HP keluarga (jaga-jaga): daftar anak dan form PIN hanya di perangkat yang pernah dipakai parent login (cookie terenkripsi `kq_family`).
2. Sesi parent: centang "Ini HP saya" saat login → idle 2 jam; tanpa centang 10 menit. Polling chat tidak dihitung aktivitas. Berlaku di semua rute web.
3. Login PIN anak mencabut langganan push milik parent di perangkat itu.
4. 5x PIN salah berturut-turut mengunci akun (kolom DB, bukan cache). Parent diberi push, membuka dari Beranda/Anak; anak wajib ganti PIN.
5. PIN baru dari anak harus beda dan bukan pola mudah (1111, 1234, 4321).
6. Reset PIN / buka kunci mengeluarkan sesi anak.
7. Nilai ulangan: foto identik ditolak (SHA-256 file tersimpan, kecuali yang pernah ditolak), maks 5 menunggu per anak, nama pelajaran dirapikan, peringatan di kartu periksa bila pelajaran+nilai sama disetujui ≤30 hari, tautan foto di Riwayat.
8. Alamat push dibatasi ke layanan resmi; throttle upload; password parent min 10; update Symfony.

## Pendekatan
Kolom di `users`/`exam_results` + middleware kecil. Ditolak: paket 2FA/lock pihak ketiga (berlebihan); membandingkan foto secara visual (mahal di server 2 GB, mudah diakali).

## Batas
Foto ulang dari kertas yang sama lolos hash (ditangani peringatan). Cookie perangkat tidak bisa dicabut satu per satu. CSP ditunda.
