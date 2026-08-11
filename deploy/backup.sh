#!/usr/bin/env bash
# Backup data produksi: PostGIS (dump) + storage (files PDF/upload).
# Jalankan di mesin yang menjalankan stack produksi: bash deploy/backup.sh
set -euo pipefail

STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="backups/$STAMP"
mkdir -p "$OUT"

echo "==> Dump PostGIS (buku_c_temurejo_prod)"
docker exec postgis_prod pg_dump -U postgres -d buku_c_temurejo_prod \
    --format=custom --no-owner --file=/tmp/buku-c.dump
docker cp "postgis_prod:/tmp/buku-c.dump" "$OUT/buku-c.dump"
docker exec postgis_prod rm -f /tmp/buku-c.dump

echo "==> Backup storage (upload, PDF, dll)"
docker run --rm \
    -v laravel_app_prod_storage:/data \
    -v "$PWD/$OUT":/backup \
    alpine sh -c "tar czf /backup/storage.tar.gz -C /data ."

echo "==> Selesai -> $OUT"
ls -lh "$OUT"
echo
echo "Pindahkan folder '$OUT' ke VPS (scp/rsync), lalu jalankan deploy/restore-vps.sh"
