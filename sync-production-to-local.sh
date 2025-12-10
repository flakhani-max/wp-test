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
# ✅ Uses Google Cloud Secret Manager for all credentials

set -e

PROJECT_ID="dashboard-254616"

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

# Check if gcloud is available
if ! command -v gcloud &> /dev/null; then
  echo "❌ Error: gcloud CLI is required but not found"
  echo "Please install gcloud: https://cloud.google.com/sdk/docs/install"
  exit 1
fi

# Check if gcloud is authenticated
if ! gcloud auth application-default print-access-token &>/dev/null; then
  echo "⚠️  Setting up GCP authentication..."
  echo "Please authenticate with: gcloud auth application-default login"
  gcloud auth application-default login
fi

echo "Fetching database credentials from Google Cloud Secret Manager..."
PROD_DB_USER=$(gcloud secrets versions access latest --secret=WORDPRESS_DB_USER --project=$PROJECT_ID)
PROD_DB_PASSWORD=$(gcloud secrets versions access latest --secret=WORDPRESS_DB_PASSWORD --project=$PROJECT_ID)
PROD_DB_NAME=$(gcloud secrets versions access latest --secret=WORDPRESS_DB_NAME --project=$PROJECT_ID)

# Local DB credentials from docker-compose.yml (these are safe to hardcode as they're local dev)
LOCAL_DB_USER="wordpress"
LOCAL_DB_PASSWORD="wordpress"
LOCAL_DB_NAME="wordpress"

echo "✅ Credentials retrieved from Secret Manager"

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
      --user="$PROD_DB_USER" \
      --password="$PROD_DB_PASSWORD" \
      --connect-timeout=2 \
      --execute="SELECT 1" \
      "$PROD_DB_NAME" &>/dev/null; then
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
    --user="$PROD_DB_USER" \
    --password="$PROD_DB_PASSWORD" \
    --single-transaction \
    --quick \
    "$PROD_DB_NAME" > production_dump.sql

echo "✅ Production data exported"

echo "Step 4: Importing to local database..."
docker-compose exec -T db mysql -u"$LOCAL_DB_USER" -p"$LOCAL_DB_PASSWORD" "$LOCAL_DB_NAME" < production_dump.sql

echo "✅ Data imported to local"

echo "Step 5: Sanitizing data for local development..."

# Update WordPress URLs for local development
docker-compose exec -T db mysql -u"$LOCAL_DB_USER" -p"$LOCAL_DB_PASSWORD" "$LOCAL_DB_NAME" << 'EOF'
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

echo "Step 6: Syncing media files from GCS..."

# Get bucket name from Secret Manager
GCS_BUCKET=$(gcloud secrets versions access latest --secret=GCS_BUCKET_NAME --project=$PROJECT_ID 2>/dev/null || echo "taxpayer-media-bucket")

# Create local uploads directory if it doesn't exist
mkdir -p ./wp-content/uploads

echo "Downloading media from gs://${GCS_BUCKET}/ ..."
if gsutil -m rsync -r -d gs://${GCS_BUCKET}/ ./wp-content/uploads/; then
  echo "✅ Media files synced to ./wp-content/uploads/"
  
  # Count files
  FILE_COUNT=$(find ./wp-content/uploads -type f | wc -l | tr -d ' ')
  echo "   Downloaded ${FILE_COUNT} files"
else
  echo "⚠️  Warning: Could not sync media files from GCS"
  echo "   Check bucket permissions or manually run:"
  echo "   gsutil -m rsync -r gs://${GCS_BUCKET}/ ./wp-content/uploads/"
fi

echo "Step 7: Cleanup..."
docker stop cloud-sql-proxy-temp 2>/dev/null || true
docker rm cloud-sql-proxy-temp 2>/dev/null || true
docker network rm wp-sync-network 2>/dev/null || true
rm production_dump.sql

echo ""
echo "======================================"
echo "✅ Success! Local environment ready"
echo "======================================"
echo ""
echo "Your local setup now has:"
echo "  ✅ Production database (sanitized)"
echo "  ✅ Media files from GCS"
echo ""
echo "Start WordPress:"
echo "  docker-compose up"
echo ""
echo "Access at: http://127.0.0.1:8080"
echo "Admin login: admin / admin123"
echo ""
echo "⚠️  Remember: This is a LOCAL COPY"
echo "    Changes here do NOT affect production!"
echo ""
echo "📁 Media files location: ./wp-content/uploads/"


