#!/usr/bin/env bash
# Reset database e2e (buku_c_e2e) ke kondisi seed final untuk tes E2E.
# Jalankan: npm run e2e:reset
set -euo pipefail

APP=laravel_app_e2e
PG=postgis_local

echo "==> migrate:fresh"
docker exec "$APP" php artisan migrate:fresh --force

echo "==> UserSeeder (3 user)"
docker exec "$APP" php artisan db:seed --class=UserSeeder --force

echo "==> storage:link"
docker exec "$APP" php artisan storage:link --force || true

echo "==> restore fixture SQL (map_blok, tanah, dhr_desa_temurejo, gis_bidang_tanah)"
docker exec -i "$PG" psql -U postgres -d buku_c_e2e -v ON_ERROR_STOP=1 < "$(dirname "$0")/fixtures/seed-data.sql"

echo "==> seed PDF peta blok"
docker exec "$APP" mkdir -p /var/www/storage/app/public/peta-blok
docker cp "$(dirname "$0")/fixtures/peta-blok-fixture.pdf" "$APP:/var/www/storage/app/public/peta-blok/seed-peta-blok.pdf"

echo "==> fixture xlsx tanah import dgn nomor persil unik per run"
RAND_SUFFIX=$((RANDOM % 90000 + 10000))
docker cp "$(dirname "$0")/fixtures/tanah-import-fixture.xlsx" "$APP:/tmp/tanah-import-template.xlsx"
docker cp "$(dirname "$0")/fixtures/regenerate-tanah-import.php" "$APP:/tmp/regenerate-import.php"
docker exec -e E2E_RAND_SUFFIX="$RAND_SUFFIX" "$APP" php /tmp/regenerate-import.php
docker cp "$APP:/tmp/tanah-import.xlsx" "$(dirname "$0")/fixtures/tanah-import-run.xlsx"

echo "==> Selesai. State DB e2e sudah di-reset."