# Installation Guide

## Quick Start

### 1. Install Dependencies

```bash
cd domain_checker
pip install -r requirements.txt
```

Atau jika menggunakan virtual environment (recommended):

```bash
# Create virtual environment
python -m venv venv

# Activate virtual environment
# Windows:
venv\Scripts\activate
# Linux/Mac:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

### 2. Configure Database

Script akan otomatis membaca konfigurasi dari file `.env` di root project Laravel. Pastikan file `.env` sudah ada dan berisi:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=your_password
DB_DATABASE=shortlink
```

Atau jika ingin menggunakan konfigurasi terpisah, buat file `.env` di folder `domain_checker/` dengan isi yang sama.

### 3. Test Run

Jalankan script untuk test:

```bash
# Windows
python main.py
# atau
run.bat

# Linux/Mac
python3 main.py
# atau
chmod +x run.sh
./run.sh
```

### 4. Setup Cron/Scheduler (Optional)

Untuk menjalankan otomatis secara berkala:

#### Windows Task Scheduler

1. Buka Task Scheduler
2. Create Basic Task
3. Set trigger (misalnya setiap jam)
4. Action: Start a program
5. Program: `python.exe`
6. Arguments: `C:\path\to\shortlink\domain_checker\main.py`
7. Start in: `C:\path\to\shortlink\domain_checker`

#### Linux Cron

```bash
# Edit crontab
crontab -e

# Tambahkan untuk setiap jam
0 * * * * cd /path/to/shortlink/domain_checker && /usr/bin/python3 main.py >> /path/to/shortlink/domain_checker/cron.log 2>&1
```

## Troubleshooting

### Python not found
- Pastikan Python 3.7+ terinstall
- Gunakan `python3` jika `python` tidak tersedia
- Windows: Install dari python.org atau Microsoft Store

### Module not found
- Pastikan dependencies sudah diinstall: `pip install -r requirements.txt`
- Pastikan virtual environment aktif jika menggunakan

### Database connection error
- Pastikan MySQL server running
- Check credentials di `.env`
- Test koneksi manual dengan MySQL client

### API timeout
- Increase `REQUEST_TIMEOUT` di `config.py`
- Kurangi `BATCH_SIZE` atau `MAX_CONCURRENT_REQUESTS`
- Check koneksi internet

