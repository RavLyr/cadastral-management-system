# Perbaikan Storage Peta Blok

## Masalah

URL seperti `http://134.122.125.198:8080/storage/peta-blok/blok_1.pdf` mengarah ke file upload di Laravel disk `public`. Di container production, `public/storage` belum dibuat otomatis dan upload di `/var/www/storage/app` belum dipersist, sehingga file peta bisa 404 setelah container dibuat ulang.

## Perbaikan

- `entrypoint.sh` sekarang membuat `/var/www/storage/app/public`.
- `entrypoint.sh` menjalankan `php artisan storage:link` saat container start.
- `docker-compose.prod.yml` sekarang memakai volume `app_storage:/var/www/storage/app` agar upload tidak hilang saat rebuild/recreate.

## Deploy

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Jika data database sudah menunjuk ke `peta-blok/blok_1.pdf` tetapi file fisiknya sudah hilang, upload ulang file peta untuk Blok 1. Perbaikan ini mencegah file baru hilang lagi, tetapi tidak bisa memulihkan PDF lama yang tidak ada di storage.

## Verifikasi

```bash
docker exec laravel_app test -L /var/www/public/storage
docker exec laravel_app test -d /var/www/storage/app/public
curl -I http://127.0.0.1:8080/storage/peta-blok/blok_1.pdf
```

Respons `curl` harus `200 OK` setelah file `storage/app/public/peta-blok/blok_1.pdf` benar-benar ada.
