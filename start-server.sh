#!/bin/bash
set -e

# Start MariaDB service if not running
if ! pgrep -x "mariadbd" > /dev/null && ! pgrep -x "mysqld" > /dev/null; then
    echo "Starting MariaDB service..."
    service mariadb start || /etc/init.d/mariadb start || true
fi

# Run PHP Built-in Server on port 3000
echo "Starting PHP server on http://0.0.0.0:3000..."
export PHP_CLI_SERVER_WORKERS=4
exec php -S 0.0.0.0:3000 router.php
