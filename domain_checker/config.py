"""
Configuration file for Domain Checker
"""
import os
from pathlib import Path
from dotenv import load_dotenv

# Try to load .env from parent directory (Laravel root) or current directory
env_path = Path(__file__).parent.parent / '.env'
if not env_path.exists():
    env_path = Path(__file__).parent / '.env'

load_dotenv(env_path)

# Database configuration
DB_CONFIG = {
    'host': os.getenv('DB_HOST', '127.0.0.1'),
    'port': int(os.getenv('DB_PORT', 3306)),
    'user': os.getenv('DB_USERNAME', 'root'),
    'password': os.getenv('DB_PASSWORD', ''),
    'db': os.getenv('DB_DATABASE', 'shortlink'),
    'charset': 'utf8mb4'
}

# API Configuration
API_URL = 'https://check.skiddle.id/'
BATCH_SIZE = 50  # Maximum domains per API request
MAX_CONCURRENT_REQUESTS = 10  # Number of concurrent API requests
REQUEST_TIMEOUT = 30  # Timeout in seconds

# Logging
LOG_LEVEL = os.getenv('LOG_LEVEL', 'INFO')

