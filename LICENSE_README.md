# Sistem Validasi License Key

Aplikasi ini dilengkapi dengan sistem validasi license key yang memastikan aplikasi hanya dapat dijalankan jika memiliki license key yang valid dan belum kadaluarsa.

## Fitur

- ✅ Validasi license key di setiap request HTTP
- ✅ Validasi license key di setiap command console
- ✅ Pengecekan tanggal kadaluarsa
- ✅ Peringatan jika license akan kadaluarsa dalam 7 hari
- ✅ Halaman error khusus untuk license yang tidak valid
- ✅ Command artisan untuk generate dan validate license

## Cara Menggunakan

### 1. Generate License Key Baru

Untuk membuat license key baru, jalankan command berikut:

```bash
php artisan license:generate
```

Atau dengan opsi tambahan:

```bash
# Generate dengan key custom
php artisan license:generate --key=YOUR_CUSTOM_KEY

# Generate dengan masa berlaku 30 hari
php artisan license:generate --days=30

# Generate dengan tanggal kadaluarsa spesifik
php artisan license:generate --date=2025-12-31
```

### 2. Validasi License Key

Untuk mengecek status license key saat ini:

```bash
php artisan license:validate
```

## Lokasi File License

License key disimpan di: `storage/app/license.key`

File ini berisi informasi:
- License key
- Tanggal kadaluarsa
- Tanggal pembuatan
- Domain aplikasi

## Perilaku Aplikasi

### Jika License Tidak Ada
- Aplikasi web akan menampilkan halaman error 403
- Command console akan menampilkan pesan error dan exit
- **Pengecualian**: Command `license:generate` dan `license:validate` tetap dapat dijalankan

### Jika License Kadaluarsa
- Aplikasi web akan menampilkan halaman error dengan informasi tanggal kadaluarsa
- Command console akan menampilkan pesan error dan exit
- **Pengecualian**: Command `license:generate` dan `license:validate` tetap dapat dijalankan

### Jika License Valid
- Aplikasi berjalan normal
- Jika license akan kadaluarsa dalam 7 hari, akan ada peringatan (untuk console commands)

## Keamanan

- File license disimpan dengan permission 0600 (hanya owner yang bisa read/write)
- License key divalidasi di setiap request HTTP melalui middleware
- License key divalidasi sebelum menjalankan command console

## Troubleshooting

### Error: "License key tidak ditemukan"
**Solusi**: Jalankan `php artisan license:generate` untuk membuat license key baru

### Error: "License key telah kadaluarsa"
**Solusi**: Buat license key baru dengan masa berlaku yang lebih lama:
```bash
php artisan license:generate --days=365
```

### Error: "Format tanggal kadaluarsa tidak valid"
**Solusi**: Pastikan format tanggal menggunakan Y-m-d (contoh: 2025-12-31)

## Catatan Penting

⚠️ **PENTING**: 
- Simpan license key dengan aman
- Backup file `storage/app/license.key` secara berkala
- Jangan hapus file license.key jika tidak ingin aplikasi berhenti berjalan
- License key harus di-generate ulang setelah kadaluarsa

