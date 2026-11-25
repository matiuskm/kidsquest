# Deploy KidsQuest

Server: nginx + php-fpm 8.3 + MySQL, folder `/var/www/kidsquest`, situs `https://kidsquest.zarvenio.id`.
Perintah CLI PHP di server ini dikunci (`disable_functions`, `open_basedir`); untuk composer/phpunit gunakan wrapper CLI-only (`php -d open_basedir= -d disable_functions= ...`). Jangan jalankan `php artisan optimize`/`config:cache` di folder ini: test akan membaca config produksi (ada pengaman di `tests/TestCase.php` dan user DB test terpisah).

## Pertama kali
1. `.env` produksi (`APP_ENV=production`, `APP_DEBUG=false`, DB, `VAPID_*` lewat `php artisan webpush:vapid`), `php artisan key:generate`.
2. `composer install --no-dev -o` dan `npm ci && npm run build`, `php artisan migrate --force`.
3. Izin: `sudo chown -R ubuntu:www-data storage bootstrap/cache .env && sudo chmod 640 .env`, folder `storage`/`bootstrap/cache` mode `2775`.
4. Akun orang tua: `php artisan kidsquest:make-parent <email> "<Nama>"` (password ditanya interaktif).
5. nginx tahap 1: salin `nginx/kidsquest.zarvenio.id.bootstrap.conf` ke `/etc/nginx/sites-available/kidsquest.zarvenio.id`, symlink ke `sites-enabled`, `sudo nginx -t && sudo systemctl reload nginx`.
6. DNS `kidsquest.zarvenio.id` harus mengarah ke IP server ini. Lalu `sudo certbot certonly --webroot -w /var/www/kidsquest/public -d kidsquest.zarvenio.id`.
7. nginx tahap 2: ganti dengan `nginx/kidsquest.zarvenio.id.conf`, `nginx -t`, reload. Set `SESSION_SECURE_COOKIE=true` di `.env`. Cloudflare SSL mode: Full (strict).

## Rilis berikutnya
`git pull`, `composer install --no-dev -o`, `npm ci && npm run build`, `php artisan migrate --force`. Versi: ubah `VERSION` + `CHANGELOG.md`, tag `vX.Y.Z`, `gh release create`.

## Catatan izin
Cache view yang dikompilasi (`storage/framework/views`) harus milik `www-data`, kalau tidak PHP-FPM gagal `touch()` (500). Test memakai folder terpisah (`VIEW_COMPILED_PATH=/tmp/kidsquest-test-views` di `phpunit.xml`). Bila 500 muncul setelah menjalankan `php artisan` sebagai `ubuntu`: `sudo rm -f storage/framework/views/*.php`. Setelah mengedit `.env` dengan `sed -i`: `sudo chown ubuntu:www-data .env && sudo chmod 640 .env`.
