# Changelog

Format mengikuti [Keep a Changelog](https://keepachangelog.com/), versi mengikuti [SemVer](https://semver.org/).

## [Unreleased]
### Added
- Login orang tua (email+password) dan anak (avatar + PIN 4 digit, throttle 5x/5 menit per anak), middleware role, perintah `kidsquest:make-parent`.
- Ledger poin append-only (`PointsLedger`): earn, redeem, refund, adjust, birthday; saldo tidak bisa minus.
- Tugas (sekali/harian/mingguan/bulanan) dengan penjadwalan tanpa cron dan CRUD parent.
- Misi anak, upload foto bukti (dikompres WebP max 1280px, disimpan privat), status todo/pending/rejected/done.

## [0.1.0] - 2026-11-25
### Added
- Scaffold Laravel (MySQL, Tailwind, Vite), versi aplikasi dari file `VERSION`.
