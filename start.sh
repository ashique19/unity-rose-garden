#!/bin/bash

echo "🚀 Initializing Unity Rose Garden Development Stack..."

# 1. Start MySQL Service if not running
echo "💾 Checking MySQL database status..."
sudo service mysql status > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "   [⚙️] Starting MySQL service..."
    sudo service mysql start
else
    echo "   [✓] MySQL is already running."
fi

# 2. Clear application caches for local development clarity
echo "🧹 Clearing Laravel cache framework..."
php artisan optimize:clear

# 3. Run pending database migrations (optional but useful)
echo "🗄️ checking schema updates..."
php artisan migrate --force

# 4. Start Laravel Development Server in the background
echo "🌐 Launching Laravel application server..."
php artisan serve --host=0.0.0.0 --port=8000 &
SERVER_PID=$!

echo "--------------------------------------------------------"
echo "✅ Stack successfully initialized!"
echo "   • App URL:        http://127.0.0.1:8000"
echo "   • phpMyAdmin URL: http://127.0.0.1:8000/phpmyadmin/"
echo "--------------------------------------------------------"
echo "💡 Press CTRL+C to stop the Laravel server session."

# Keep the script foregrounded to the Laravel development server process
wait $SERVER_PID