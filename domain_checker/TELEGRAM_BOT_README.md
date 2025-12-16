# Telegram Bot untuk Domain Checker

Bot Telegram untuk mengecek status domain secara real-time.

## Fitur

- ✅ `/start` - Mendapatkan Chat ID Telegram Anda
- ✅ `/check <domain>` - Mengecek status domain (blocked/active)

## Instalasi

1. Install dependencies:
```bash
cd domain_checker
pip install -r requirements.txt
```

Atau jika menggunakan virtual environment:
```bash
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
```

## Menjalankan Bot

### Windows:
```bash
cd domain_checker
python telegram_bot.py
```

Atau gunakan batch file:
```bash
run_telegram_bot.bat
```

### Linux/Mac:
```bash
cd domain_checker
python3 telegram_bot.py
```

Atau gunakan shell script:
```bash
chmod +x run_telegram_bot.sh
./run_telegram_bot.sh
```

## Menjalankan sebagai Service (Background)

### Linux (systemd):

Buat file `/etc/systemd/system/telegram-bot.service`:

```ini
[Unit]
Description=Telegram Bot for Domain Checker
After=network.target

[Service]
Type=simple
User=your-user
WorkingDirectory=/path/to/shortlink/domain_checker
ExecStart=/usr/bin/python3 /path/to/shortlink/domain_checker/telegram_bot.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Kemudian:
```bash
sudo systemctl daemon-reload
sudo systemctl enable telegram-bot
sudo systemctl start telegram-bot
sudo systemctl status telegram-bot
```

### Windows (Task Scheduler):

1. Buka Task Scheduler
2. Create Basic Task
3. Trigger: At startup atau When I log on
4. Action: Start a program
   - Program: `python.exe`
   - Arguments: `C:\path\to\shortlink\domain_checker\telegram_bot.py`
   - Start in: `C:\path\to\shortlink\domain_checker`

## Penggunaan

1. Buka Telegram dan cari bot Anda (gunakan username bot yang sudah dibuat di BotFather)
2. Kirim `/start` untuk mendapatkan Chat ID Anda
3. Gunakan Chat ID tersebut di halaman Profile di admin panel
4. Kirim `/check google.com` untuk mengecek status domain

## Contoh Penggunaan

```
User: /start
Bot: Hello User! 👋
     Your Telegram Chat ID is:
     123456789
     ...

User: /check google.com
Bot: 🔍 Checking domain: google.com...
     ✅ Domain Check Result
     
     Domain: google.com
     Status: ACTIVE
     
     ✅ This domain is active and accessible.

User: /check xnxx.com
Bot: 🔍 Checking domain: xnxx.com...
     🚫 Domain Check Result
     
     Domain: xnxx.com
     Status: BLOCKED
     
     ⚠️ This domain is currently blocked.
```

## Logging

Log akan ditulis ke:
- File: `telegram_bot.log`
- Console: stdout

Level logging bisa diubah melalui environment variable `LOG_LEVEL` di `.env`:
- `DEBUG`: Detail informasi
- `INFO`: Informasi umum (default)
- `WARNING`: Peringatan
- `ERROR`: Error saja

## Troubleshooting

### Bot tidak merespons
- Pastikan bot token benar
- Pastikan bot sudah di-start (tidak di-block)
- Cek log file `telegram_bot.log`

### Error "Module not found"
- Pastikan dependencies sudah diinstall: `pip install -r requirements.txt`
- Pastikan virtual environment aktif jika menggunakan

### Domain check gagal
- Pastikan API URL benar di `config.py`
- Cek koneksi internet
- Cek log untuk detail error

## Catatan

- Bot menggunakan API yang sama dengan domain checker (`https://check.skiddle.id/`)
- Bot berjalan secara async untuk performa optimal
- Bot akan terus berjalan sampai dihentikan (Ctrl+C)

