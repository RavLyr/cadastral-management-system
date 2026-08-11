# Runbook Deploy VPS — Buku C Digital

Target: VPS Ubuntu 22.04/24.04, Docker + Cloudflare Tunnel, subdomain `app.ravlyr.my.id`.

## 1. Prasyarat di VPS

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER   # logout-login setelah ini
```

## 2. Clone & env produksi

```bash
git clone https://github.com/RavLyr/cadastral-management-system.git /opt/buku-c
cd /opt/buku-c
cp .env.prod .env.prod          # EDIT file ini: APP_KEY, DB_PASSWORD (lihat bawah)
```

`.env.prod` di VPS WAJIB diisi ulang:
- `APP_KEY=base64:...` → `php -r "echo 'base64:'.base64_encode(random_bytes(32));"` (atau dari hasil `docker exec laravel_app_prod php artisan key:generate --show`)
- `DB_PASSWORD=` → password postgres baru (kuat)
- `APP_ENV=production`, `APP_DEBUG=false` (sudah default di template)

## 3. Jalanin stack

```bash
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
docker compose --env-file .env.prod -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose --env-file .env.prod -f docker-compose.prod.yml exec app php artisan db:seed --class=UserSeeder --force
curl -I http://localhost:8080    # -> 200
```

## 4. Restore data dari lokal (kalau migrasi)

```bash
bash deploy/restore-vps.sh /path/ke/folder-backup
```

## 5. Cloudflare Tunnel

```bash
curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared.deb && rm cloudflared.deb
sudo mkdir -p /etc/cloudflared
# copy dari mesin lokal (credential + config TIDAK ada di git):
#   scp ~/.cloudflared/buku-c.json  user@vps:/etc/cloudflared/
#   scp deploy/cloudflared/config.yml user@vps:/etc/cloudflared/  (dari repo)
sudo cp deploy/cloudflared/cloudflared.service /etc/systemd/system/
sudo systemctl daemon-reload && sudo systemctl enable --now cloudflared
systemctl status cloudflared       # active (running)
```

DNS tidak perlu diubah (tunnel ID sama dengan lokal). Pastikan cloudflared LOKAL dimatikan dulu.

## 6. Verifikasi

```bash
curl -sI https://app.ravlyr.my.id | head -3        # 200
curl -s https://app.ravlyr.my.id/login | grep -o "Login" | head -1
```

## 7. Update app di kemudian hari

```bash
cd /opt/buku-c && git pull
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build
docker compose --env-file .env.prod -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose --env-file .env.prod -f docker-compose.prod.yml exec app php artisan optimize:clear
docker compose --env-file .env.prod -f docker-compose.prod.yml exec app php artisan optimize
```

## Troubleshooting cepat

| Gejala | Cek |
|---|---|
| 502 dari Cloudflare | app container down → `docker compose ps`; tunnel ke port 8080 sudah listen? |
| 500 di app | `docker logs laravel_app_prod --tail 50`; `.env.prod` APP_KEY benar? |
| Login selalu gagal | `php artisan migrate` jalan? UserSeeder sudah di-seed? |
| File upload hilang | volume `laravel_app_prod_storage` ada? (`docker volume ls`) |
