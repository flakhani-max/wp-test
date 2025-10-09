#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------
# Configuration Variables
# ---------------------------------------------
PORT="${PORT:-8080}"

# Auto-detect Cloud Run URL if not set
if [ -z "${WP_URL:-}" ]; then
  # Check if we're on Cloud Run (K_SERVICE is set by Cloud Run)
  if [ -n "${K_SERVICE:-}" ]; then
    # Cloud Run service detected - construct URL
    SITE_URL="https://${K_SERVICE}-${K_REVISION:-xxx}.${REGION:-us-central1}.run.app"
    echo "Auto-detected Cloud Run URL: $SITE_URL"
    echo "Note: This is an approximation. WordPress will auto-correct to the actual URL on first request."
  else
    # Local development
    SITE_URL="http://127.0.0.1:${PORT}"
  fi
else
  SITE_URL="${WP_URL}"
fi

SITE_TITLE="${WP_TITLE:-Hello World WordPress}"
ADMIN_USER="${WP_ADMIN_USER:-admin}"
ADMIN_PASS="${WP_ADMIN_PASS:-admin123}"
ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@example.com}"
DOCROOT="/var/www/html"

# ---------------------------------------------
# Cloud SQL Socket Configuration
# ---------------------------------------------
echo "DEBUG: Checking Cloud SQL configuration..."
echo "CLOUD_SQL_CONNECTION_NAME=${CLOUD_SQL_CONNECTION_NAME:-NOT SET}"

if [ -n "${CLOUD_SQL_CONNECTION_NAME:-}" ]; then
  _cloudsql_socket="/cloudsql/${CLOUD_SQL_CONNECTION_NAME}"
  echo "DEBUG: Cloud SQL socket path will be: ${_cloudsql_socket}"
  
  # Always set CLOUDSQL_SOCKET_PATH when using Cloud SQL
  export CLOUDSQL_SOCKET_PATH="${_cloudsql_socket}"
  
  # Ensure WORDPRESS_DB_HOST is localhost for socket connection
  if [ "${WORDPRESS_DB_HOST:-}" != "localhost" ]; then
    echo "DEBUG: Changing WORDPRESS_DB_HOST from '${WORDPRESS_DB_HOST}' to 'localhost'"
  fi
  export WORDPRESS_DB_HOST="localhost"
  
  echo "DEBUG: Set WORDPRESS_DB_HOST=localhost and CLOUDSQL_SOCKET_PATH=${_cloudsql_socket}"
else
  echo "DEBUG: CLOUD_SQL_CONNECTION_NAME is not set - will use default socket"
fi

# Fallback for alternative Cloud SQL configuration methods
if [ -z "${CLOUDSQL_SOCKET_PATH:-}" ]; then
  if [ -n "${INSTANCE_UNIX_SOCKET:-}" ]; then
    export WORDPRESS_DB_HOST="localhost"
    export CLOUDSQL_SOCKET_PATH="${INSTANCE_UNIX_SOCKET}"
    echo "DEBUG: Using INSTANCE_UNIX_SOCKET: ${INSTANCE_UNIX_SOCKET}"
  elif [[ "${WORDPRESS_DB_HOST:-}" == /cloudsql/* ]]; then
    export CLOUDSQL_SOCKET_PATH="${WORDPRESS_DB_HOST}"
    export WORDPRESS_DB_HOST="localhost"
    echo "DEBUG: Detected /cloudsql/* in WORDPRESS_DB_HOST"
  fi
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
echo "==================================================="
echo "Database Connection Configuration"
echo "==================================================="
echo "WORDPRESS_DB_HOST=${WORDPRESS_DB_HOST:-not set}"
echo "WORDPRESS_DB_USER=${WORDPRESS_DB_USER:-not set}"
echo "WORDPRESS_DB_NAME=${WORDPRESS_DB_NAME:-not set}"
echo "CLOUD_SQL_CONNECTION_NAME=${CLOUD_SQL_CONNECTION_NAME:-not set}"
echo "CLOUDSQL_SOCKET_PATH=${CLOUDSQL_SOCKET_PATH:-not set}"
echo "==================================================="

i=0
until [ "$i" -gt 30 ]; do
  i=$((i+1))
  
  # Use mysql client with explicit socket path if Cloud SQL is configured
  if [ -n "${CLOUDSQL_SOCKET_PATH:-}" ] && [ -S "${CLOUDSQL_SOCKET_PATH}" ]; then
    if mysql --socket="${CLOUDSQL_SOCKET_PATH}" \
            --user="${WORDPRESS_DB_USER}" \
            --password="${WORDPRESS_DB_PASSWORD}" \
            "${WORDPRESS_DB_NAME}" \
            -e "SELECT 1;" >/dev/null 2>&1; then
      echo "✓ Database connection successful via Cloud SQL socket!"
      break
    fi
  else
    # Fallback to wp db check for non-Cloud SQL setups
    if wp db check --path="$DOCROOT" --allow-root 2>&1; then
      echo "✓ Database connection successful!"
      break
    fi
  fi
  
  if [ "$i" -ge 30 ]; then
    echo "❌ Database not reachable after 60s."
    echo "Cloud SQL socket: ${CLOUDSQL_SOCKET_PATH:-not set}"
    if [ -n "${CLOUDSQL_SOCKET_PATH:-}" ]; then
      echo "Socket exists: $([ -S "${CLOUDSQL_SOCKET_PATH}" ] && echo 'YES' || echo 'NO')"
      ls -la /cloudsql/ 2>&1 || echo "/cloudsql directory not found"
    fi
    exit 1
  fi
  
  echo "Waiting for DB... (attempt $i/30)"
  sleep 2
done

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

# Activate CTF Landing Pages theme
echo "Checking for ctf-landing-pages theme..."
wp theme list --path="$DOCROOT" --allow-root || echo "Could not list themes"

if wp theme is-installed ctf-landing-pages --path="$DOCROOT" --allow-root; then
  echo "✓ ctf-landing-pages theme is installed, activating..."
  wp theme activate ctf-landing-pages --path="$DOCROOT" --allow-root
  echo "✓ CTF Landing Pages theme activated!"
  wp theme list --path="$DOCROOT" --allow-root --status=active
else
  echo "❌ ctf-landing-pages theme NOT found!"
  echo "Available themes:"
  wp theme list --path="$DOCROOT" --allow-root || echo "Could not list themes"
  echo "Theme directory contents:"
  ls -la "$DOCROOT/wp-content/themes/" || echo "Could not list theme directory"
fi

# Remove default plugins
for plugin in akismet hello; do
  if wp plugin is-installed "$plugin" --path="$DOCROOT" --allow-root; then
    wp plugin delete "$plugin" --path="$DOCROOT" --allow-root || true
  fi
done

# Remove default themes
for theme in twentytwentyfour twentytwentythree twentytwentytwo twentytwentyone twentytwenty twentynineteen twentyseventeen twentysixteen twentyfifteen twentyfourteen twentythirteen twentytwelve twentyeleven twentyten; do
  if wp theme is-installed "$theme" --path="$DOCROOT" --allow-root; then
    wp theme delete "$theme" --path="$DOCROOT" --allow-root || true
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

