#!/bin/bash
# Cancel stuck WP Offload Media download tool

set -e

PROJECT_ID="dashboard-254616"
CLOUD_SQL_INSTANCE="dashboard-254616:northamerica-northeast1:wordpress-test-db"

echo "==================================================="
echo "Cancelling Stuck WP Offload Media Download Tool"
echo "==================================================="

# Fetch production DB credentials from Secret Manager
echo "Fetching database credentials from Secret Manager..."
PROD_DB_USER=$(gcloud secrets versions access latest --secret=WORDPRESS_DB_USER --project=$PROJECT_ID)
PROD_DB_PASSWORD=$(gcloud secrets versions access latest --secret=WORDPRESS_DB_PASSWORD --project=$PROJECT_ID)
PROD_DB_NAME=$(gcloud secrets versions access latest --secret=WORDPRESS_DB_NAME --project=$PROJECT_ID)

echo "Cleaning up any existing proxy containers..."
docker stop cloud-sql-proxy-cancel 2>/dev/null || true
docker rm cloud-sql-proxy-cancel 2>/dev/null || true

echo "Starting Cloud SQL Proxy..."
docker network create wp-cancel-network 2>/dev/null || true

# Start Cloud SQL Proxy with proper authentication
GCLOUD_CONFIG="${HOME}/.config/gcloud"
docker run -d \
  --name cloud-sql-proxy-cancel \
  --network wp-cancel-network \
  -v "${GCLOUD_CONFIG}:/gcloud:ro" \
  -e GOOGLE_APPLICATION_CREDENTIALS=/gcloud/application_default_credentials.json \
  gcr.io/cloud-sql-connectors/cloud-sql-proxy:latest \
  --address 0.0.0.0 \
  $CLOUD_SQL_INSTANCE

# Wait for proxy to be ready
echo "Waiting for Cloud SQL Proxy to be ready..."
sleep 3

# Test connection
echo "Testing Cloud SQL Proxy connection..."
for i in {1..30}; do
  if docker run --rm \
    --network wp-cancel-network \
    mariadb:10.11 \
    mysql \
      --host=cloud-sql-proxy-cancel \
      --port=3306 \
      --user="$PROD_DB_USER" \
      --password="$PROD_DB_PASSWORD" \
      --connect-timeout=2 \
      --execute="SELECT 1" \
      "$PROD_DB_NAME" &>/dev/null; then
    echo "✓ Connected to database"
    break
  fi
  
  if [ $i -eq 30 ]; then
    echo "❌ Failed to connect to database after 30 attempts"
    docker stop cloud-sql-proxy-cancel 2>/dev/null || true
    docker network rm wp-cancel-network 2>/dev/null || true
    exit 1
  fi
  
  echo "Waiting for connection... (attempt $i/30)"
  sleep 2
done

# Cancel the download tool by deleting background process options
echo "Cancelling stuck download tool in production database..."
docker run --rm \
  --network wp-cancel-network \
  mariadb:10.11 \
  mysql \
    --host=cloud-sql-proxy-cancel \
    --port=3306 \
    --user="$PROD_DB_USER" \
    --password="$PROD_DB_PASSWORD" \
    "$PROD_DB_NAME" \
    --execute="
DELETE FROM wp_options WHERE option_name LIKE '%as3cf%downloader%';
DELETE FROM wp_options WHERE option_name LIKE '%as3cf_background%downloader%';
DELETE FROM wp_options WHERE option_name LIKE '%as3cf%tool%';
SELECT CONCAT('Deleted ', ROW_COUNT(), ' stuck background task options') as Result;
"

# Cleanup
echo "Cleaning up..."
docker stop cloud-sql-proxy-cancel 2>/dev/null || true
docker network rm wp-cancel-network 2>/dev/null || true

echo ""
echo "✅ Download tool cancelled!"
echo "Refresh your WordPress admin page - settings should now be unlocked"
echo "==================================================="

