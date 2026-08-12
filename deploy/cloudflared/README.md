# Cloudflared — Setup Lokal (Testing) & Migrasi VPS

Semua langkah memakai subdomain: **`app.ravlyr.my.id`** (arahkan ke tunnel, bukan ke IP).

## 1. Install binary (lokal WSL & VPS sama)

```bash
# Ubuntu/Debian (termasuk WSL)
curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared.deb
rm cloudflared.deb
cloudflared --version
```

## 2. Login & buat tunnel (sekali, di mesin mana pun)

```bash
cloudflared tunnel login                      # buka link, pilih domain ravlyr.my.id
cloudflared tunnel create buku-c              # sudah dibuat: ID aabf1aa4-3391-4cc6-b9bd-709891031c58
cloudflared tunnel route dns buku-c app.ravlyr.my.id
```

Copy `buku-c.json` (dan `config.yml`) ke `/etc/cloudflared/`:

```bash
sudo mkdir -p /etc/cloudflared
sudo cp ~/.cloudflared/aabf1aa4-3391-4cc6-b9bd-709891031c58.json /etc/cloudflared/buku-c.json
sudo cp deploy/cloudflared/config.yml /etc/cloudflared/
```

## 3. Jalankan di lokal (testing)

Stack produksi lokal harus jalan di port 8080 dulu:

```bash
docker compose -f docker-compose.prod.yml up -d --build
sudo cloudflared tunnel run buku-c
```

Cek: `curl https://app.ravlyr.my.id` -> 200, login jalan. Semua HTTPS/WAF otomatis dari Cloudflare.

## 4. Migrasi ke VPS (gampang karena tunnel ini)

1. **Data**: `bash deploy/backup.sh` (dump PostGIS + storage) di mesin lokal.
2. **Ke VPS**: clone repo, `deploy/restore-vps.sh` (restore dump + storage ke volume prod).
3. **Tunnel**: di VPS install cloudflared, copy `/etc/cloudflared/` (config + credential yang sama).
4. **DNS tidak berubah** — tunnel ID sama, Cloudflare ngarahin ke VPS.
5. Matikan cloudflared lokal dulu, start di VPS:

```bash
sudo cp deploy/cloudflared/cloudflared.service /etc/systemd/system/
sudo systemctl daemon-reload && sudo systemctl enable --now cloudflared
```

> Jangan hidupkan lokal + VPS barengan: Cloudflare load-balance ke dua arah (double-write).

## Catatan
- `buku-c.json` = credential rahasia -> JANGAN di-commit (sudah di .gitignore).
- SSL mode di dashboard Cloudflare: **Full (strict)**.
- `.env.prod` di VPS ditulis ulang (APP_ENV=production, DB_PASSWORD baru) — jangan copy dari lokal.
