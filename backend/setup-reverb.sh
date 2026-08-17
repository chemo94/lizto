#!/bin/bash
# Laravel Reverb Setup Script for Production VPS
# Run: chmod +x setup-reverb.sh && sudo ./setup-reverb.sh

set -e

# Configuration - UPDATE THESE VALUES
LARAVEL_PATH="core"
PHP_BIN="/usr/bin/php8.3"
SERVER_USER="www-data"
LOG_DIR="/var/log/laravel-reverb"

echo "=== Laravel Reverb Production Setup ==="

# 1. Create log directory
echo "Creating log directory..."
sudo mkdir -p $LOG_DIR
sudo chown $SERVER_USER:$SERVER_USER $LOG_DIR

# 2. Update .env for production
echo "Configuring .env for production..."
cd $LARAVEL_PATH

# Update Reverb settings
sed -i 's/REVERB_HOST="0.0.0.0"/REVERB_HOST="0.0.0.0"/' .env
sed -i 's/REVERB_PORT=8080/REVERB_PORT=8080/' .env
sed -i 's/REVERB_SCHEME=http/REVERB_SCHEME=https/' .env
sed -i 's/BROADCAST_CONNECTION=pusher/BROADCAST_CONNECTION=reverb/' .env

# 3. Install supervisor config
echo "Installing Supervisor configuration..."
sudo cp /var/www/liztogo/supervisor/laravel-reverb.conf /etc/supervisor/conf.d/laravel-reverb.conf

# Update paths in supervisor config
sudo sed -i "s|{{ARTISAN_PATH}}|$PHP_BIN $LARAVEL_PATH/artisan|g" /etc/supervisor/conf.d/laravel-reverb.conf
sudo sed -i "s|{{LARAVEL_BASE_PATH}}|$LARAVEL_PATH|g" /etc/supervisor/conf.d/laravel-reverb.conf
sudo sed -i "s|{{SERVER_USER}}|$SERVER_USER|g" /etc/supervisor/conf.d/laravel-reverb.conf
sudo sed -i "s|{{LOG_DIR}}|$LOG_DIR|g" /etc/supervisor/conf.d/laravel-reverb.conf

# 4. Restart supervisor
echo "Starting Reverb via Supervisor..."
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-reverb:*

# 5. Verify
echo "=== Checking Reverb status ==="
sudo supervisorctl status laravel-reverb:*
echo ""
echo "=== Setup Complete ==="
echo "Reverb is now running on port 8080 (internal)"
echo ""
echo "Next steps:"
echo "1. Configure Nginx reverse proxy for WSS (port 443)"
echo "2. Add this to your Nginx site config:"
echo ""
echo "   location /app/ {"
echo "       proxy_pass http://localhost:8080;"
echo "       proxy_http_version 1.1;"
echo "       proxy_set_header Upgrade \$http_upgrade;"
echo "       proxy_set_header Connection \"upgrade\";"
echo "       proxy_set_header Host \$host;"
echo "       proxy_set_header X-Real-IP \$remote_addr;"
echo "       proxy_read_timeout 86400;"
echo "   }"
echo ""
echo "3. Update Flutter apps with your domain in .env"
echo "   REVERB_HOST=\"your-domain.com\""
echo "   REVERB_PORT=443"
echo "   REVERB_SCHEME=https"
