# Poin Nilai Ulangan — Design (kecil)

Tanggal: 2026-11-25. Permintaan Kris: anak dapat poin dari nilai ulangan; anak kirim, parent verifikasi.

## Tujuan
Memotivasi anak belajar. Nilai ulangan menghasilkan poin lewat ledger yang sama dengan misi.

## Keputusan Kris
- Anak mengirim: mata pelajaran, nilai (0-100), foto kertas ulangan. Parent memeriksa foto, boleh mengoreksi nilai, lalu setuju atau tolak (sama seperti alur misi). Ditolak: anak bisa kirim ulang.
- Rentang: A 90-100 = 5 poin, B 80-89 = 4, C 70-79 = 3, di bawah 70 = 0 (D ke bawah tidak dapat poin).

## Non-goals
Tidak ada rata-rata/grafik nilai, tidak ada rapor, tidak ada bonus streak.

## Approaches
| | Hasil |
|---|---|
| A. Entitas baru `exam_results` + tipe ledger `exam`, reuse PhotoStore/ReviewService/Notifier | **Dipilih**: ulangan sewaktu-waktu, tidak terjadwal |
| B. Paksa jadi `Task` dengan recurrence once | Ditolak: Task terjadwal, poinnya tetap, tidak punya nilai |

## Desain (call Claude, bisa dikoreksi)
- Tabel `exam_results`: child_id, subject, claimed_score, final_score (nullable), photo_path, status (`ReviewStatus`), reject_note, points_awarded (nullable), reviewed_at.
- Rentang di `config/kidsquest.php` (`exam_grades`: min score → grade+points). Satu kelas kecil `ExamGrade::for(int $score)` mengembalikan huruf dan poin. Mengubah rentang butuh edit config, bukan menu (call Claude; menu bisa ditambah nanti).
- Poin dihitung saat approve dari `final_score` (default = nilai klaim anak), disimpan di `points_awarded`, ditulis ke ledger tipe `exam` (poin 0 = tidak menulis ledger, tetap status approved).
- Reuse: `PhotoStore` (foto wajib), `ReviewService` (idempotent lock, push), guard klik ganda 10 detik seperti klaim hadiah.
- UI anak: menu "Nilai" (form kirim + riwayat nilai dan huruf). UI parent: item ulangan masuk antrean "Perlu diperiksa" dengan kolom nilai bisa diedit; muncul di Riwayat.
- Tes: rentang batas (69/70/79/80/89/90/100, 0, 101 ditolak), approve menulis ledger sekali (idempotent), koreksi nilai mengubah poin, reject tanpa poin, anak tidak bisa lihat/ubah milik anak lain.
