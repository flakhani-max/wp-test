#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------
# Configuration Variables
# ---------------------------------------------
PORT="${PORT:-8080}"
SITE_URL="${WP_URL:-http://127.0.0.1:${PORT}}"
SITE_TITLE="${WP_TITLE:-Hello World WordPress}"
ADMIN_USER="${WP_ADMIN_USER:-admin}"
ADMIN_PASS="${WP_ADMIN_PASS:-admin123}"
ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@example.com}"
DOCROOT="/var/www/html"

# ---------------------------------------------
# Cloud SQL Socket Configuration
# ---------------------------------------------
if [ -n "${CLOUD_SQL_CONNECTION_NAME:-}" ]; then
  _cloudsql_socket="/cloudsql/${CLOUD_SQL_CONNECTION_NAME}"
  if [ -z "${WORDPRESS_DB_HOST:-}" ] || [ "${WORDPRESS_DB_HOST}" = "${CLOUD_SQL_CONNECTION_NAME}" ]; then
    export WORDPRESS_DB_HOST="localhost"
    export CLOUDSQL_SOCKET_PATH="${_cloudsql_socket}"
  fi
fi

if [ -n "${INSTANCE_UNIX_SOCKET:-}" ] && [ -z "${WORDPRESS_DB_HOST:-}" ]; then
  export WORDPRESS_DB_HOST="localhost"
  export CLOUDSQL_SOCKET_PATH="${INSTANCE_UNIX_SOCKET}"
fi

if [[ "${WORDPRESS_DB_HOST:-}" == /cloudsql/* ]]; then
  export CLOUDSQL_SOCKET_PATH="${WORDPRESS_DB_HOST}"
  export WORDPRESS_DB_HOST="localhost"
fi

# Configure PHP mysqli to use the Cloud SQL socket
if [ -n "${CLOUDSQL_SOCKET_PATH:-}" ]; then
  echo "Configuring PHP to use Cloud SQL socket: ${CLOUDSQL_SOCKET_PATH}"
  echo "mysqli.default_socket = ${CLOUDSQL_SOCKET_PATH}" > /usr/local/etc/php/conf.d/cloud-sql-socket.ini
  echo "pdo_mysql.default_socket = ${CLOUDSQL_SOCKET_PATH}" >> /usr/local/etc/php/conf.d/cloud-sql-socket.ini
fi

# ---------------------------------------------
# Configure Apache to listen on PORT
# ---------------------------------------------
if grep -qE '^Listen ' /etc/apache2/ports.conf; then
  sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
else
  echo "Listen ${PORT}" >> /etc/apache2/ports.conf
fi

if [ -d /etc/apache2/sites-available ]; then
  sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/*.conf || true
fi

# ---------------------------------------------
# Start Apache in background
# ---------------------------------------------
/usr/local/bin/docker-entrypoint.sh apache2-foreground & pid=$!

# Wait for Apache to start
until nc -z 127.0.0.1 ${PORT} 2>/dev/null; do
  echo "Waiting for Apache to bind to port ${PORT}..."
  sleep 2
done
echo "✓ Apache is listening on port ${PORT}"

# ---------------------------------------------
# Database Connection Check
# ---------------------------------------------
echo "Checking database connection..."
echo "WORDPRESS_DB_HOST=${WORDPRESS_DB_HOST:-not set}"
echo "WORDPRESS_DB_USER=${WORDPRESS_DB_USER:-not set}"
echo "WORDPRESS_DB_NAME=${WORDPRESS_DB_NAME:-not set}"

i=0
until wp db check --path="$DOCROOT" --allow-root 2>&1; do
  i=$((i+1))
  if [ "$i" -gt 30 ]; then
    echo "Database not reachable after 60s. Check WORDPRESS_DB_* environment variables."
    exit 1
  fi
  echo "Waiting for DB... (attempt $i/30)"
  sleep 2
done

echo "✓ Database connection successful!"

# ---------------------------------------------
# WordPress Installation
# ---------------------------------------------
if ! wp core is-installed --path="$DOCROOT" --allow-root; then
  echo "Installing WordPress..."
  wp core install \
    --path="$DOCROOT" \
    --url="$SITE_URL" \
    --title="$SITE_TITLE" \
    --admin_user="$ADMIN_USER" \
    --admin_password="$ADMIN_PASS" \
    --admin_email="$ADMIN_EMAIL" \
    --skip-email \
    --allow-root
  echo "✓ WordPress installed successfully!"
fi

# Update WordPress options
wp option update siteurl "$SITE_URL" --path="$DOCROOT" --allow-root
wp option update home "$SITE_URL" --path="$DOCROOT" --allow-root
wp option update blogname "$SITE_TITLE" --path="$DOCROOT" --allow-root

# Activate Hello World theme
if wp theme is-installed hello-world --path="$DOCROOT" --allow-root; then
  wp theme activate hello-world --path="$DOCROOT" --allow-root
  echo "✓ Hello World theme activated!"
fi

# Remove default plugins
for plugin in akismet hello; do
  if wp plugin is-installed "$plugin" --path="$DOCROOT" --allow-root; then
    wp plugin delete "$plugin" --path="$DOCROOT" --allow-root || true
  fi
done

# Fix permissions
chown -R www-data:www-data "$DOCROOT/wp-content" || true

echo "======================================"
echo "WordPress is ready!"
echo "URL: $SITE_URL"
echo "Admin User: $ADMIN_USER"
echo "Admin Pass: $ADMIN_PASS"
echo "======================================"

# Keep Apache in foreground
wait "$pid"

