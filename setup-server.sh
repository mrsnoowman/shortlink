#!/bin/bash

# Server Setup Script untuk Laravel Shortlink App
# Jalankan script ini di server setelah upload project

set -e

echo "========================================"
echo "  Server Setup Script - Shortlink App"
echo "========================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
APP_PATH="/var/www/shortlink"
APP_USER="www-data"

echo -e "${CYAN}Checking requirements...${NC}"

# Check PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ PHP not found. Please install PHP 8.1+${NC}"
    exit 1
fi
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
echo -e "${GREEN}✓ PHP $PHP_VERSION found${NC}"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo -e "${YELLOW}Composer not found. Installing...${NC}"
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi
echo -e "${GREEN}✓ Composer found${NC}"

# Check MySQL
if ! command -v mysql &> /dev/null; then
    echo -e "${YELLOW}MySQL client not found. Please install MySQL/MariaDB${NC}"
fi

echo ""
echo -e "${CYAN}Setting up application...${NC}"

# Navigate to app directory
cd "$APP_PATH" || exit 1

# Set permissions
echo -e "${YELLOW}Setting permissions...${NC}"
chown -R $APP_USER:$APP_USER "$APP_PATH"
chmod -R 755 "$APP_PATH"
chmod -R 775 storage bootstrap/cache

# Install/Update dependencies
echo -e "${YELLOW}Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader

# Create .env if not exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}Creating .env file...${NC}"
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        echo "APP_NAME=Shortlink" > .env
        echo "APP_ENV=production" >> .env
        echo "APP_KEY=" >> .env
        echo "APP_DEBUG=false" >> .env
        echo "APP_URL=http://localhost" >> .env
        echo "" >> .env
        echo "DB_CONNECTION=mysql" >> .env
        echo "DB_HOST=127.0.0.1" >> .env
        echo "DB_PORT=3306" >> .env
        echo "DB_DATABASE=shortlink" >> .env
        echo "DB_USERNAME=root" >> .env
        echo "DB_PASSWORD=" >> .env
    fi
    echo -e "${GREEN}✓ .env file created${NC}"
    echo -e "${YELLOW}⚠ Please edit .env file with your database credentials!${NC}"
else
    echo -e "${GREEN}✓ .env file exists${NC}"
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo -e "${YELLOW}Generating application key...${NC}"
    php artisan key:generate --force
    echo -e "${GREEN}✓ Application key generated${NC}"
fi

# Run migrations
echo -e "${YELLOW}Running database migrations...${NC}"
read -p "Do you want to run migrations? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo -e "${GREEN}✓ Migrations completed${NC}"
fi

# Create storage links
echo -e "${YELLOW}Creating storage links...${NC}"
php artisan storage:link || true

# Clear and cache config
echo -e "${YELLOW}Optimizing application...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Application optimized${NC}"

# Setup Python domain checker (if needed)
if [ -d "domain_checker" ]; then
    echo ""
    echo -e "${CYAN}Setting up Python domain checker...${NC}"
    
    # Check Python
    if command -v python3 &> /dev/null; then
        echo -e "${GREEN}✓ Python3 found${NC}"
        
        cd domain_checker
        
        # Install python3-full and venv if needed
        if ! dpkg -l | grep -q python3-full; then
            echo -e "${YELLOW}Installing python3-full...${NC}"
            apt-get install -y python3-full python3-venv 2>/dev/null || true
        fi
        
        if [ -f "requirements.txt" ]; then
            echo -e "${YELLOW}Setting up Python virtual environment...${NC}"
            
            # Create virtual environment if not exists
            if [ ! -d "venv" ]; then
                python3 -m venv venv
                echo -e "${GREEN}✓ Virtual environment created${NC}"
            fi
            
            # Activate and install packages
            source venv/bin/activate
            pip install --upgrade pip
            pip install -r requirements.txt
            deactivate
            
            echo -e "${GREEN}✓ Python dependencies installed${NC}"
        fi
        cd ..
    else
        echo -e "${YELLOW}Python3 not found. Skipping domain checker setup.${NC}"
    fi
fi

# Setup cron job for scheduler
echo ""
echo -e "${CYAN}Setting up cron job...${NC}"
CRON_ENTRY="* * * * * cd $APP_PATH && php artisan schedule:run >> /dev/null 2>&1"
(crontab -l 2>/dev/null | grep -v "artisan schedule:run"; echo "$CRON_ENTRY") | crontab -
echo -e "${GREEN}✓ Cron job added${NC}"

# Generate license key (if not exists)
echo ""
echo -e "${CYAN}License Key Setup${NC}"
if [ ! -f "storage/app/license.key" ]; then
    echo -e "${YELLOW}No license key found. Generating...${NC}"
    php artisan license:generate --days=365
    echo -e "${GREEN}✓ License key generated${NC}"
else
    echo -e "${GREEN}✓ License key exists${NC}"
    php artisan license:validate
fi

# Final permissions
echo ""
echo -e "${YELLOW}Setting final permissions...${NC}"
chown -R $APP_USER:$APP_USER "$APP_PATH"
chmod -R 755 "$APP_PATH"
chmod -R 775 storage bootstrap/cache
chmod 600 storage/app/license.key 2>/dev/null || true

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Setup completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${CYAN}Next steps:${NC}"
echo -e "${YELLOW}1. Edit .env file with your database credentials${NC}"
echo -e "${YELLOW}2. Configure web server (Nginx/Apache)${NC}"
echo -e "${YELLOW}3. Point document root to: $APP_PATH/public${NC}"
echo -e "${YELLOW}4. Test the application${NC}"
echo ""

