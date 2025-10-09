#!/bin/bash

# Sync Cloud SQL Database to Local MariaDB
# This script exports from Cloud SQL and imports to your local Docker database

set -e

echo "======================================"
echo "Cloud SQL → Local Database Sync"
echo "======================================"

# Configuration
CLOUD_SQL_INSTANCE="wordpress-test-db"
DATABASE_NAME="wordpress"
BACKUP_FILE="wordpress_backup_$(date +%Y%m%d_%H%M%S).sql"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo ""
echo "Step 1: Exporting from Cloud SQL..."
gcloud sql export sql ${CLOUD_SQL_INSTANCE} \
  gs://your-bucket-name/${BACKUP_FILE} \
  --database=${DATABASE_NAME} \
  2>&1 || {
    echo -e "${RED}❌ Export failed!${NC}"
    echo ""
    echo "You need to create a Cloud Storage bucket first:"
    echo "  gsutil mb gs://your-project-wordpress-backups"
    echo ""
    echo "Or export manually:"
    echo "  gcloud sql export sql ${CLOUD_SQL_INSTANCE} gs://BUCKET_NAME/${BACKUP_FILE} --database=${DATABASE_NAME}"
    exit 1
  }

echo -e "${GREEN}✅ Exported to Cloud Storage${NC}"
echo ""

echo "Step 2: Downloading backup file..."
gsutil cp gs://your-bucket-name/${BACKUP_FILE} ./

echo -e "${GREEN}✅ Downloaded${NC}"
echo ""

echo "Step 3: Importing to local database..."

# Make sure local database is running
if ! docker-compose ps db | grep -q "Up"; then
  echo "Starting local database..."
  docker-compose up -d db
  sleep 5
fi

# Import the database
docker-compose exec -T db mysql -uwordpress -pwordpress wordpress < ${BACKUP_FILE}

echo -e "${GREEN}✅ Database imported successfully!${NC}"
echo ""

# Clean up
echo "Cleaning up..."
rm ${BACKUP_FILE}
gsutil rm gs://your-bucket-name/${BACKUP_FILE}

echo ""
echo "======================================"
echo -e "${GREEN}✅ Sync Complete!${NC}"
echo "======================================"
echo ""
echo "Your local database now mirrors production!"
echo "Start WordPress with: docker-compose up"

