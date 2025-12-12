#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------
# Load .env files if they exist (for local development)
# ---------------------------------------------
if [ -f /var/www/html/.env ]; then
  echo "📄 Loading environment from .env file..."
  export $(grep -v '^#' /var/www/html/.env | xargs)
fi
if [ -f /var/www/html/.env.secrets ]; then
  echo "📄 Loading production secrets..."
  export $(grep -v '^#' /var/www/html/.env.secrets | xargs)
fi

# ---------------------------------------------
# Configuration Variables
# ---------------------------------------------
PORT="${PORT:-8080}"

# Auto-detect Cloud Run URL if not set
if [ -z "${WP_URL:-}" ]; then
  # Check if we're on Cloud Run (K_SERVICE is set by Cloud Run)
  if [ -n "${K_SERVICE:-}" ]; then
    # Cloud Run service detected - construct URL
    SITE_URL="https://${K_SERVICE}-${K_REVISION:-xxx}.${REGION:-northamerica-northeast1}.run.app"
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
  if [ -n "${CLOUDSQL_SOCKET_PATH:-}" ]; then
    # First check if socket exists (wait for Cloud Run to mount it)
    if [ ! -S "${CLOUDSQL_SOCKET_PATH}" ]; then
      if [ "$i" -eq 1 ]; then
        echo "Waiting for Cloud SQL socket to be mounted at: ${CLOUDSQL_SOCKET_PATH}"
      fi
    else
      # Socket exists, try to connect
      if mysql --socket="${CLOUDSQL_SOCKET_PATH}" \
              --user="${WORDPRESS_DB_USER}" \
              --password="${WORDPRESS_DB_PASSWORD}" \
              "${WORDPRESS_DB_NAME}" \
              -e "SELECT 1;" >/dev/null 2>&1; then
        echo "✓ Database connection successful via Cloud SQL socket!"
        break
      fi
    fi
  else
    # Fallback to wp db check for non-Cloud SQL setups (local development)
    if wp db check --path="$DOCROOT" --allow-root >/dev/null 2>&1; then
      echo "✓ Database connection successful!"
      break
    fi
  fi
  
  if [ "$i" -ge 30 ]; then
    echo "⚠️  Database check failed after 60s."
    echo "Cloud SQL socket: ${CLOUDSQL_SOCKET_PATH:-not set}"
    if [ -n "${CLOUDSQL_SOCKET_PATH:-}" ]; then
      echo "Socket exists: $([ -S "${CLOUDSQL_SOCKET_PATH}" ] && echo 'YES' || echo 'NO')"
      ls -la /cloudsql/ 2>&1 || echo "/cloudsql directory not found"
      echo "⚠️  Continuing anyway - WordPress will handle DB connection retries"
      break
    else
      echo "⚠️  Continuing anyway (local development mode)"
      break
    fi
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
else
  echo "WordPress already installed, updating site URL..."
  # Always update the site URL to match current environment
  CURRENT_URL=$(wp option get siteurl --path="$DOCROOT" --allow-root 2>/dev/null || echo "")
  if [ "$CURRENT_URL" != "$SITE_URL" ]; then
    echo "Updating WordPress URL from '$CURRENT_URL' to '$SITE_URL'"
    wp option update home "$SITE_URL" --path="$DOCROOT" --allow-root
    wp option update siteurl "$SITE_URL" --path="$DOCROOT" --allow-root
    echo "✓ WordPress URLs updated successfully!"
  else
    echo "✓ WordPress URL already set to $SITE_URL"
  fi
fi

# WP Offload Media Pro is configured via must-use plugins:
# - wp-content/mu-plugins/wp-offload-media-config.php (bucket settings)
# - wp-content/mu-plugins/wp-offload-media-pro-license.php (Pro license)
echo "✓ WP Offload Media configuration handled by must-use plugins"

# Deactivate and remove Lite version if it exists (to avoid conflicts with Pro)
if wp plugin is-installed amazon-s3-and-cloudfront --path="$DOCROOT" --allow-root; then
  echo "Removing WP Offload Media Lite (conflicts with Pro)..."
  wp plugin deactivate amazon-s3-and-cloudfront --path="$DOCROOT" --allow-root 2>/dev/null || true
  wp plugin delete amazon-s3-and-cloudfront --path="$DOCROOT" --allow-root 2>/dev/null || true
fi

# Activate WP Offload Media Pro
if wp plugin is-installed amazon-s3-and-cloudfront-pro --path="$DOCROOT" --allow-root; then
  wp plugin activate amazon-s3-and-cloudfront-pro --path="$DOCROOT" --allow-root || true
  echo "✓ WP Offload Media Pro activated"
else
  echo "⚠️ WP Offload Media Pro not found (should be pre-installed in image)"
fi

# Activate CTF Custom Plugin
if wp plugin is-installed ctf-custom-plugin --path="$DOCROOT" --allow-root; then
  echo "Activating CTF Custom Plugin..."
  wp plugin activate ctf-custom-plugin --path="$DOCROOT" --allow-root || true
else
  echo "⚠️ CTF Custom Plugin not found"
fi


# Update WordPress options
wp option update siteurl "$SITE_URL" --path="$DOCROOT" --allow-root
wp option update home "$SITE_URL" --path="$DOCROOT" --allow-root
wp option update blogname "$SITE_TITLE" --path="$DOCROOT" --allow-root

# Update admin user credentials (optimized to skip if user exists with correct username)
echo "Checking admin user credentials..."

# Check if user with correct username AND email already exists
EXISTING_USER_LOGIN=$(wp user get "$ADMIN_USER" --field=user_login --path="$DOCROOT" --allow-root 2>/dev/null || echo "")
EXISTING_USER_EMAIL=$(wp user get "$ADMIN_USER" --field=user_email --path="$DOCROOT" --allow-root 2>/dev/null || echo "")

if [ "$EXISTING_USER_LOGIN" = "$ADMIN_USER" ] && [ "$EXISTING_USER_EMAIL" = "$ADMIN_EMAIL" ]; then
  # User exists with correct username and email - just update password
  echo "✓ Admin user '$ADMIN_USER' exists, updating password..."
  wp user update "$ADMIN_USER" --user_pass="$ADMIN_PASS" --path="$DOCROOT" --allow-root 2>&1 | grep -v "Warning: User logins can't be changed" || true
  echo "✓ Admin password updated!"
else
  # Need to delete old user and create fresh
  EXISTING_USER_ID=$(wp user list --field=ID --user_email="$ADMIN_EMAIL" --path="$DOCROOT" --allow-root 2>/dev/null | head -1)
  
  if [ -n "$EXISTING_USER_ID" ]; then
    echo "✓ Found user (ID: $EXISTING_USER_ID) with email $ADMIN_EMAIL, recreating with username '$ADMIN_USER'..."
    wp user delete "$EXISTING_USER_ID" --yes --path="$DOCROOT" --allow-root 2>&1
  fi
  
  # Create new user
  wp user create "$ADMIN_USER" "$ADMIN_EMAIL" \
  --user_pass="$ADMIN_PASS" \
    --role=administrator \
  --path="$DOCROOT" \
    --allow-root 2>&1 && echo "✓ Admin user created successfully!" || echo "❌ Failed to create admin user"
fi

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

# Install ACF Pro if license key is provided
# ACF Pro is pre-installed in Docker image, just activate it
echo "Activating ACF Pro..."
  if wp plugin is-installed advanced-custom-fields-pro --path="$DOCROOT" --allow-root; then
    wp plugin activate advanced-custom-fields-pro --path="$DOCROOT" --allow-root || true
    echo "✓ ACF Pro activated!"
else
  echo "⚠️ ACF Pro not found (should be pre-installed in image)"
fi


# ---------------------------------------------
# WP Offload Media Lite Configuration
# ---------------------------------------------
echo "==================================================="
echo "Setting up WP Offload Media Lite"
echo "==================================================="

UPLOADS_PATH="/var/www/html/wp-content/uploads"
mkdir -p "$UPLOADS_PATH"
chown -R www-data:www-data "$UPLOADS_PATH"

# --- Handle GCS key file for both local + Cloud Run ---
if [ -n "${WP_MEDIA_SA_KEY:-}" ]; then
  echo "🔑 Writing GCS key from environment variable..."
  # Decode base64 if it's encoded (for Cloud Run)
  if echo "${WP_MEDIA_SA_KEY}" | base64 -d &>/dev/null; then
    echo "${WP_MEDIA_SA_KEY}" | base64 -d > "${UPLOADS_PATH}/gcs-key.json"
  else
    # Direct JSON (for local development)
    echo "${WP_MEDIA_SA_KEY}" > "${UPLOADS_PATH}/gcs-key.json"
  fi
elif [ -f "/run/secrets/gcs-key.json" ]; then
  echo "🔑 Using mounted gcs-key.json from /run/secrets"
  cp /run/secrets/gcs-key.json "${UPLOADS_PATH}/gcs-key.json"
elif [ -f "${UPLOADS_PATH}/gcs-key.json" ]; then
  echo "✅ Found existing gcs-key.json in uploads directory"
else
  echo "⚠️  No GCS key file found – media uploads may fail"
fi

# Secure permissions
chown www-data:www-data "${UPLOADS_PATH}/gcs-key.json" 2>/dev/null || true

# # --- Auto-configure WP Offload Media bucket ---
# if [ -n "${GCS_BUCKET_NAME:-}" ]; then
#   echo "🪣 Configuring Offload Media bucket: ${GCS_BUCKET_NAME}"
#   wp option update as3cf_settings "{\"provider\":\"gcp\",\"bucket\":\"${GCS_BUCKET_NAME}\"}" \
#     --path=/var/www/html --allow-root || true
# fi

echo "✓ WP Offload Media Lite setup complete"
echo "==================================================="

# Remove default plugins
for plugin in akismet hello; do
  if wp plugin is-installed "$plugin" --path="$DOCROOT" --allow-root; then
    wp plugin delete "$plugin" --path="$DOCROOT" --allow-root || true
  fi
done

# Remove default themes
for theme in twentytwentyfive twentytwentyfour twentytwentythree twentytwentytwo twentytwentyone twentytwenty twentynineteen twentyseventeen twentysixteen twentyfifteen twentyfourteen twentythirteen twentytwelve twentyeleven twentyten; do
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

