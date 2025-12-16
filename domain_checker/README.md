# Domain Checker

Python script untuk mengecek status domain (blocked/active) pada setiap target URL di shortlink system.

## Fitur

- ✅ Batch processing - Mengecek banyak domain sekaligus dalam satu request
- ✅ Async/Parallel - Menggunakan asyncio untuk proses cepat
- ✅ Efficient - Mengelompokkan domain unik untuk menghindari duplikasi
- ✅ Bulk Updates - Update database secara bulk untuk performa optimal
- ✅ Error Handling - Robust error handling dan logging
- ✅ Statistics - Menampilkan statistik sebelum dan sesudah checking

## Instalasi

1. Install Python dependencies:
```bash
cd domain_checker
pip install -r requirements.txt
```

2. Setup database configuration di `config.py` atau buat file `.env` di root project Laravel:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=
DB_DATABASE=shortlink
LOG_LEVEL=INFO
```

## Penggunaan

### Menjalankan Script

```bash
python main.py
```

### Menjalankan dengan Schedule (Cron)

Tambahkan ke crontab untuk menjalankan setiap jam:

```bash
# Edit crontab
crontab -e

# Tambahkan baris berikut (sesuaikan path)
0 * * * * cd /path/to/shortlink/domain_checker && /usr/bin/python3 main.py
```

Atau untuk Windows Task Scheduler, buat batch file:

```batch
@echo off
cd C:\path\to\shortlink\domain_checker
python main.py
```

## Konfigurasi

Edit file `config.py` untuk mengubah:

- `BATCH_SIZE`: Jumlah maksimal domain per API request (default: 50)
- `MAX_CONCURRENT_REQUESTS`: Jumlah request concurrent (default: 10)
- `REQUEST_TIMEOUT`: Timeout untuk setiap request dalam detik (default: 30)

## Cara Kerja

1. **Fetch Data**: Mengambil semua target URLs dari database
2. **Extract Domains**: Mengekstrak domain dari setiap URL
3. **Group Unique**: Mengelompokkan domain unik untuk menghindari duplikasi
4. **Batch Processing**: Membagi domain menjadi batch-batch kecil
5. **Parallel Checking**: Mengecek semua batch secara parallel
6. **Update Database**: Update status `is_blocked` di table `target_urls` secara bulk

## Logging

Log akan ditulis ke:
- File: `domain_checker.log`
- Console: stdout

Level logging bisa diubah melalui environment variable `LOG_LEVEL`:
- `DEBUG`: Detail informasi
- `INFO`: Informasi umum (default)
- `WARNING`: Peringatan
- `ERROR`: Error saja

## Performance

Dengan konfigurasi default:
- **50 domains per batch**
- **10 concurrent requests**
- Untuk 1000 domain: ~2 batch × 10 concurrent = **~20 detik**
- Untuk 5000 domain: ~10 batch × 10 concurrent = **~30-60 detik**

## Troubleshooting

### Database Connection Error
- Pastikan database credentials benar di `config.py` atau `.env`
- Pastikan MySQL server running
- Pastikan user memiliki akses ke database

### API Timeout
- Increase `REQUEST_TIMEOUT` di `config.py`
- Kurangi `BATCH_SIZE` jika terlalu banyak domain per request
- Kurangi `MAX_CONCURRENT_REQUESTS` jika server API lambat

### Domain Not Found in Results
- Domain mungkin tidak valid
- API mungkin tidak merespons untuk domain tertentu
- Check log untuk detail error

## Struktur File

```
domain_checker/
├── main.py          # Main script
├── config.py        # Configuration
├── database.py      # Database operations
├── checker.py       # Domain checking logic
├── requirements.txt # Python dependencies
├── README.md        # Documentation
└── domain_checker.log  # Log file (auto-generated)
```

