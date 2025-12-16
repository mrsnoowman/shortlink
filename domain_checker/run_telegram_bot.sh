#!/bin/bash
# Linux/Mac script to run Telegram bot
cd "$(dirname "$0")"

# Use virtual environment if exists
if [ -d "venv" ]; then
    source venv/bin/activate
    python telegram_bot.py
    deactivate
else
    # Fallback to system python3
    python3 telegram_bot.py
fi

