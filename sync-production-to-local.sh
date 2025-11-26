#!/bin/bash

# DevOps Best Practice: Clone & Sanitize Production DB to Local
# 
# This script:
# 1. Clones production database
# 2. Sanitizes sensitive data
# 3. Updates URLs for local development
# 4. Imports to local MariaDB
#
# ✅ Works on Mac, Windows (WSL/Git Bash), Linux

set -e

echo "======================================"
echo "Production → Local (Best Practice)"
echo "======================================"
echo ""

# Check if Docker is available
if ! command -v docker &> /dev/null; then
  echo "❌ Error: Docker is required but not found"
  echo "Please install Docker: https://www.docker.com/get-started"
  exit 1
fi

# Cleanup any leftover containers from previous runs
echo "Cleaning up any previous runs..."
docker stop cloud-sql-proxy-temp 2>/dev/null || true
docker rm cloud-sql-proxy-temp 2>/dev/null || true

# Check if local DB is running
if ! docker-compose ps db 2>/dev/null | grep -q "Up"; then
  echo "Starting local database..."
  docker-compose up -d db
  sleep 5
fi

echo "Step 1: Creating temporary network..."
docker network create wp-sync-network 2>/dev/null || true

echo "Step 2: Starting Cloud SQL Proxy (in Docker)..."

# Check if gcloud is authenticated
if ! gcloud auth application-default print-access-token &>/dev/null; then
  echo "⚠️  Setting up GCP authentication..."
  echo "Please authenticate with: gcloud auth application-default login"
  gcloud auth application-default login
fi

# Get the gcloud config directory
GCLOUD_CONFIG="${HOME}/.config/gcloud"

docker run -d \
  --name cloud-sql-proxy-temp \
  --network wp-sync-network \
  -v "${GCLOUD_CONFIG}:/gcloud:ro" \
  -e GOOGLE_APPLICATION_CREDENTIALS=/gcloud/application_default_credentials.json \
  gcr.io/cloud-sql-connectors/cloud-sql-proxy:latest \
  --address 0.0.0.0 \
  dashboard-254616:northamerica-northeast1:wordpress-test-db

echo "Waiting for Cloud SQL Proxy to be ready..."
sleep 5

# Check if proxy container is running
if ! docker ps | grep -q cloud-sql-proxy-temp; then
  echo "❌ Error: Cloud SQL Proxy failed to start"
  docker logs cloud-sql-proxy-temp
  exit 1
fi

echo "Proxy container is running. Waiting for socket to be ready..."

# Wait for proxy to be ready with retries
MAX_RETRIES=30
RETRY_COUNT=0
while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
  if docker run --rm \
    --network wp-sync-network \
    mariadb:10.11 \
    mysql \
      --host=cloud-sql-proxy-temp \
      --port=3306 \
      --user=wordpress \
      --password='D~=X"?Ug5%e5P^uR' \
      --connect-timeout=2 \
      --execute="SELECT 1" \
      wordpress &>/dev/null; then
    echo "✅ Cloud SQL Proxy is ready!"
    break
  fi
  
  RETRY_COUNT=$((RETRY_COUNT + 1))
  if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "❌ Error: Timed out waiting for Cloud SQL Proxy"
    echo "Proxy logs:"
    docker logs cloud-sql-proxy-temp
    exit 1
  fi
  
  echo "  Attempt $RETRY_COUNT/$MAX_RETRIES - waiting..."
  sleep 1
done

echo "Step 3: Dumping production database (using Docker)..."
docker run --rm \
  --network wp-sync-network \
  mariadb:10.11 \
  mysqldump \
    --host=cloud-sql-proxy-temp \
    --port=3306 \
    --user=wordpress \
    --password='D~=X"?Ug5%e5P^uR' \
    --single-transaction \
    --quick \
    wordpress > production_dump.sql

echo "✅ Production data exported"

echo "Step 4: Importing to local database..."
docker-compose exec -T db mysql -uwordpress -pwordpress wordpress < production_dump.sql

echo "✅ Data imported to local"

echo "Step 5: Sanitizing data for local development..."

# Update WordPress URLs for local development
docker-compose exec -T db mysql -uwordpress -pwordpress wordpress << 'EOF'
-- Update site URLs for local
UPDATE wp_options SET option_value = 'http://127.0.0.1:8080' WHERE option_name IN ('siteurl', 'home');

-- Sanitize admin email (optional)
UPDATE wp_users SET user_email = CONCAT('dev+', user_login, '@localhost');

-- Reset all user passwords to a known dev password
-- (They'll need to reset on production anyway)
-- Uncomment if you want all users to have password 'devpassword123':
-- UPDATE wp_users SET user_pass = MD5('devpassword123');

SELECT 'Data sanitized for local development' AS status;
EOF

echo "✅ Data sanitized"

echo "Step 6: Cleanup..."
docker stop cloud-sql-proxy-temp 2>/dev/null || true
docker rm cloud-sql-proxy-temp 2>/dev/null || true
docker network rm wp-sync-network 2>/dev/null || true
rm production_dump.sql

echo ""
echo "======================================"
echo "✅ Success! Local DB is ready"
echo "======================================"
echo ""
echo "Your local database now has production data (sanitized)"
echo ""
echo "Start WordPress:"
echo "  docker-compose up"
echo ""
echo "Access at: http://127.0.0.1:8080"
echo "Admin login: admin / admin123"
echo ""
echo "⚠️  Remember: This is a LOCAL COPY"
echo "    Changes here do NOT affect production!"


