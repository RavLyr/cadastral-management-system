# Buku C Digital - Sistem Manajemen Cadastral

Sistem manajemen data cadastral digital untuk pengelolaan Buku C Tanah di Desa Temurejo. Aplikasi ini memungkinkan pencarian, import, dan pencetakan data tanah dengan peta yang akurat secara fisik.

## 🚀 Fitur Utama

- **Pencarian Data Tanah**: Cari berdasarkan nama, nomor persil, atau blok
- **Import Data SISMIOP**: Impor data tanah dari file Excel SISMIOP
- **Upload Peta**: Upload file peta (PDF/Image) dengan validasi skala
- **Pencetakan PDF Fisik**: Generate PDF dengan ukuran fisik yang akurat (210x315mm)
- **Preview Peta**: Tampilan preview peta dengan opsi fullscreen
- **Manajemen Data**: CRUD lengkap untuk data tanah, blok, dan peta
- **Validasi Backend**: Validasi ketat untuk format skala dan data input

## 🛠️ Teknologi

- **Backend**: Laravel 11 (PHP 8.2+)
- **Frontend**: Vue.js 3 + Inertia.js
- **Database**: MySQL
- **PDF Generation**: TCPDF + FPDI
- **Styling**: Tailwind CSS
- **Build Tool**: Vite
- **Icons**: Lucide Vue

## 📋 Persyaratan Sistem

- PHP 8.1 atau lebih tinggi
- Composer
- Node.js 16+ & npm/pnpm
- MySQL 5.7+
- Git

## 🔧 Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/RavLyr/cadastral-management-system.git
   cd cadastral-management-system
   ```

2. **Install Dependencies PHP**
   ```bash
   composer install
   ```

3. **Install Dependencies JavaScript**
   ```bash
   npm install
   # atau
   pnpm install
   ```

4. **Konfigurasi Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Edit `.env` untuk database dan konfigurasi lainnya:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=buku_c_temurejo
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Jalankan Migrasi**
   ```bash
   php artisan migrate
   ```

6. **Seed Data (Opsional)**
   ```bash
   php artisan db:seed
   ```

7. **Build Assets**
   ```bash
   npm run build
   # atau untuk development
   npm run dev
   ```

8. **Jalankan Server**
   ```bash
   php artisan serve
   ```

   Aplikasi akan berjalan di `http://localhost:8000`

## 📖 Penggunaan

### Import Data SISMIOP
1. Masuk ke menu SISMIOP
2. Upload file Excel (.xlsx) yang berisi data tanah
3. Sistem akan memvalidasi dan mengimpor data secara otomatis

### Upload Peta
1. Masuk ke menu Peta
2. Upload file PDF atau gambar peta
3. Masukkan informasi blok dan skala (format: 1:1000)
4. Sistem akan memvalidasi format skala

### Pencarian Data
1. Masuk ke menu Pencarian
2. Ketik nama, nomor persil, atau blok
3. Klik "Lihat Peta" untuk preview
4. Klik "Cetak PDF" untuk generate dokumen fisik

### Pencetakan PDF
- PDF dihasilkan dengan ukuran fisik 210x315mm
- Termasuk tabel data dan peta yang di-embed
- Mendukung skala bar otomatis

## 📁 Struktur Proyek

```
├── app/
│   ├── Http/Controllers/    # Controller utama
│   ├── Models/             # Model Eloquent
│   └── Imports/            # Import classes
├── resources/
│   ├── js/Pages/           # Komponen Vue
│   └── css/                # Styling
├── routes/
│   └── web.php             # Routing
├── database/
│   ├── migrations/         # Database migrations
│   └── seeders/            # Data seeders
└── storage/
    └── app/public/         # File uploads
```

## 🔒 Keamanan

- Validasi input di frontend dan backend
- File upload dengan validasi tipe dan ukuran
- Error handling untuk operasi PDF
- Confirmation dialog untuk operasi berbahaya

## 🤝 Kontribusi

1. Fork repository
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📄 Lisensi

Proyek ini menggunakan lisensi MIT. Lihat file `LICENSE` untuk detail lebih lanjut.

## 👥 Pengembang

- **RavLyr** - *Developer* - [GitHub](https://github.com/RavLyr)

## 📞 Dukungan

Jika ada pertanyaan atau masalah, silakan buat issue di repository ini.
