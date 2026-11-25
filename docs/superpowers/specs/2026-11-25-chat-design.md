# Chat — Design (kecil)

Tanggal: 2026-11-25. Permintaan Kris: chat antar anak dan anak-parent.

## Keputusan Kris
- Parent TIDAK membaca chat anak-anak (jawaban "gk perlu"). Parent hanya melihat percakapan yang melibatkan dirinya.
- Foto di chat: nanti, bukan sekarang.
- Usulan yang tidak dibantah dan dipakai: 1-lawan-1 tanpa grup, teks/emoji maks 500 karakter, tanpa server realtime (polling + push), badge belum dibaca, parent bisa mematikan chat antar anak per anak.

## Non-goals
Grup, foto/file, pesan hilang/edit/hapus, status mengetik, moderasi oleh parent (sengaja tidak ada).

## Approaches
| | Hasil |
|---|---|
| A. Tabel `messages` (pengirim, penerima, isi, read_at) + polling 5 detik + Web Push yang sudah ada | **Dipilih**: RAM server dibagi dengan Ministerio, tanpa daemon baru |
| B. Websocket (Reverb/Pusher) | Ditolak: proses tambahan, beban server |

## Desain (call Claude, bisa dikoreksi)
- Tabel `messages`: sender_id, recipient_id, body, read_at. Kolom `users.peer_chat` (bool, default true): izin chat dengan anak lain.
- `ChatService`: `contacts`, `canChat` (bukan diri sendiri, bukan parent-parent, anak-anak hanya bila kedua `peer_chat`), `send` (push ke penerima setelah commit), `thread`, `markRead`, `unreadCount`.
- Satu `ChatController` untuk dua peran (index, show, store, poll) di `/chat`; view memakai layout sesuai peran. Poll mengembalikan JSON pesan baru dan menandai terbaca.
- Anak hapus: pesannya ikut dihapus (privasi).
- Nav: tab 💬 Chat (anak, 6 tab), "Chat" (parent), badge belum dibaca. `ParentIdleTimeout` hanya berlaku untuk parent (rute chat dipakai bersama).
- Parent: tombol "Chat antar anak: aktif/mati" per anak di daftar anak.
- Anti-spam: throttle kirim 20/menit.
