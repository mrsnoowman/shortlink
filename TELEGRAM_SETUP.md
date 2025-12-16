# Telegram Notification Setup

## Setup Telegram Bot

1. **Buat Telegram Bot:**
   - Buka Telegram dan cari `@BotFather`
   - Kirim command `/newbot`
   - Ikuti instruksi untuk membuat bot baru
   - Simpan **Bot Token** yang diberikan (contoh: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

2. **Dapatkan Chat ID:**
   - Buka Telegram dan cari `@userinfobot`
   - Kirim pesan apapun ke bot tersebut
   - Bot akan membalas dengan Chat ID Anda (contoh: `123456789`)

3. **Konfigurasi di .env (opsional):**
   ```env
   TELEGRAM_BOT_TOKEN=7817290791:AAHjBpDzEpxrFPrrHJuoh4FNyCfwA7OubqI
   ```
   
   **Note:** Token sudah di-hardcode di command sebagai default, jadi tidak wajib menambahkannya ke .env. Tapi disarankan untuk menambahkannya ke .env untuk keamanan.

## Cara Menggunakan

1. **Aktifkan Notifikasi:**
   - Buka halaman Profile di admin panel
   - Enable toggle "Telegram Notification"
   - Masukkan Telegram Chat ID Anda
   - Set interval notifikasi (dalam menit, contoh: 1 = setiap 1 menit, 5 = setiap 5 menit)

2. **Setup Scheduler:**
   - Pastikan Laravel scheduler berjalan:
   ```bash
   # Tambahkan ke crontab (Linux/Mac)
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   
   # Atau jalankan manual untuk testing
   php artisan schedule:run
   ```

3. **Test Command:**
   ```bash
   php artisan telegram:send-notifications
   ```

## Cara Kerja

1. **Python Domain Checker:**
   - Saat domain checker Python mendeteksi perubahan status (active → blocked atau blocked → active)
   - Perubahan dicatat di tabel `domain_status_changes` dengan `notified = false`

2. **Laravel Scheduler:**
   - Command `telegram:send-notifications` berjalan setiap menit
   - Untuk setiap user dengan Telegram enabled:
     - Cek apakah sudah waktunya kirim notifikasi (berdasarkan `telegram_interval_minutes`)
     - Ambil semua perubahan status yang belum di-notify
     - Kirim pesan gabungan ke Telegram
     - Update `notified = true` dan `last_telegram_notified_at`

3. **Interval Per User:**
   - Setiap user bisa set interval sendiri (1 menit, 5 menit, dll)
   - Notifikasi hanya dikirim jika sudah lewat interval yang ditentukan

## Format Notifikasi

Notifikasi akan berisi:
- Daftar domain/shortlink yang berubah status
- Status baru (Active/Blocked)
- Waktu perubahan
- Summary (jumlah blocked, active, total)

## Troubleshooting

1. **Notifikasi tidak terkirim:**
   - Pastikan `TELEGRAM_BOT_TOKEN` sudah di-set di `.env`
   - Pastikan Chat ID benar
   - Cek log Laravel untuk error

2. **Scheduler tidak jalan:**
   - Pastikan crontab sudah di-setup
   - Test dengan `php artisan schedule:run` manual

3. **Interval tidak bekerja:**
   - Pastikan `last_telegram_notified_at` di-update setelah notifikasi terkirim
   - Cek apakah ada perubahan status yang tercatat di `domain_status_changes`

