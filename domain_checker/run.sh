#!/bin/bash
# Linux/Mac script to run domain checker
cd "$(dirname "$0")"

# Use virtual environment if exists
if [ -d "venv" ]; then
    source venv/bin/activate
    python main.py
    deactivate
else
    # Fallback to system python3
    python3 main.py
fi

