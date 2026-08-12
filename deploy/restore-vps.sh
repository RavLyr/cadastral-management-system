#!/usr/bin/env bash
# Restore backup ke VPS. Jalankan DI VPS setelah stack produksi up.
# Usage: bash deploy/restore-vps.sh /path/ke/folder-backup
set -euo pipefail

BACKUP_DIR="${1:-}"
if [[ -z "$BACKUP_DIR" || ! -f "$BACKUP_DIR/buku-c.dump" ]]; then
    echo "Usage: bash deploy/restore-vps.sh /path/ke/folder-backup (harus berisi buku-c.dump + storage.tar.gz)"
    exit 1
fi

echo "==> Pastikan stack produksi jalan"
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d
until docker exec postgres_prod pg_isready -U postgres -d buku_c_temurejo_prod >/dev/null 2>&1; do sleep 2; done

echo "==> Restore dump PostGIS"
docker cp "$BACKUP_DIR/buku-c.dump" postgres_prod:/tmp/buku-c.dump
docker exec postgres_prod pg_restore -U postgres -d buku_c_temurejo_prod \
    --no-owner --clean --if-exists /tmp/buku-c.dump
docker exec postgres_prod rm -f /tmp/buku-c.dump

echo "==> Restore storage"
docker run --rm \
    -v laravel_app_prod_storage:/data \
    -v "$PWD/$BACKUP_DIR":/backup:ro \
    alpine sh -c "rm -rf /data/* && tar xzf /backup/storage.tar.gz -C /data && chown -R www-data:www-data /data"

echo "==> Selesai. Restart app:"
echo "    docker compose --env-file .env.prod -f docker-compose.prod.yml restart app"
