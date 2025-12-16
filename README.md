# Shortlink Application dengan Filament

Aplikasi shortlink sederhana menggunakan Laravel dan Filament untuk manajemen CRUD.

## Fitur

- ✅ CRUD (Create, Read, Update, Delete) untuk Shortlinks
- ✅ Setiap user memiliki satu shortlink
- ✅ Setiap shortlink memiliki target URL masing-masing
- ✅ Admin panel menggunakan Filament

## Instalasi

1. Clone atau download project ini
2. Install dependencies:
```bash
composer install
```

3. Copy file `.env.example` menjadi `.env`:
```bash
copy .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Setup database di file `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shortlink
DB_USERNAME=root
DB_PASSWORD=
```

6. Jalankan migration:
```bash
php artisan migrate
```

7. Buat user admin pertama:
```bash
php artisan make:filament-user
```

8. Jalankan server:
```bash
php artisan serve
```

9. Akses admin panel di: `http://localhost:8000/admin`

## Struktur Database

### Tabel `shortlinks`
- `id` - Primary key
- `user_id` - Foreign key ke users
- `short_code` - Kode pendek untuk URL (unique)
- `target_url` - URL lengkap yang akan diarahkan
- `created_at` - Timestamp
- `updated_at` - Timestamp

### Tabel `users`
- `id` - Primary key
- `name` - Nama user
- `email` - Email user (unique)
- `password` - Password (hashed)
- `email_verified_at` - Timestamp verifikasi email
- `remember_token` - Token remember
- `created_at` - Timestamp
- `updated_at` - Timestamp

## Penggunaan

1. Login ke admin panel di `/admin`
2. Klik menu "Shortlinks" di sidebar
3. Klik "New Shortlink" untuk membuat shortlink baru
4. Isi form:
   - Pilih User
   - Masukkan Short Code (contoh: abc123)
   - Masukkan Target URL (contoh: https://example.com)
5. Klik "Create" untuk menyimpan
6. Gunakan tombol Edit atau Delete untuk mengelola shortlink

## Catatan

- Setiap user hanya bisa memiliki satu shortlink
- Short code harus unique
- Target URL harus valid URL format

