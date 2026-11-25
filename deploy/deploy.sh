#!/usr/bin/env bash
# Rilis ke produksi. Jalankan dari /var/www/kidsquest (selalu di branch main); kerjakan perubahan di /var/www/kidsquest-dev.
set -euo pipefail
cd /var/www/kidsquest

[ "$(git branch --show-current)" = main ] || { echo "Folder produksi harus di branch main."; exit 1; }

before=$(git rev-parse HEAD)
git pull --ff-only origin main
composer install --no-dev -o -q
git diff --name-only "$before" HEAD | grep -qE '^(resources/(js|css)|package|vite)' && { npm ci --silent && npm run build --silent; }
php artisan migrate --force
php artisan optimize:clear

sudo chown -R www-data:www-data storage/framework/views storage/framework/cache
sudo chown ubuntu:www-data .env && sudo chmod 640 .env

for u in / /masuk-anak /masuk; do
    printf '%s %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' "https://kidsquest.zarvenio.id$u")"
done
