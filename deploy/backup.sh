#!/usr/bin/env bash
# Backup harian: database + foto. Dipasang di /usr/local/bin/kidsquest-backup, dijalankan cron (lihat deploy/README.md).
set -euo pipefail
DEST=/var/backups/kidsquest
DAY=$(date +%F)

mkdir -p "$DEST" && chmod 700 "$DEST"
mysqldump --defaults-extra-file=/etc/kidsquest-backup.cnf --single-transaction kidsquest | gzip > "$DEST/db-$DAY.sql.gz"
tar -czf "$DEST/photos-$DAY.tar.gz" -C /var/www/kidsquest/storage/app private
find "$DEST" -type f -mtime +14 -delete
