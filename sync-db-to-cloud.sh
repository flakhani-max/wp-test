#!/bin/bash

# Script to sync local WordPress database to Cloud SQL
# This will export the local database and import it to Cloud SQL

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== WordPress Database Sync: Local → Cloud SQL ===${NC}\n"

# Load environment variables
if [ -f .env ]; then
    source .env
else
    echo -e "${RED}Error: .env file not found${NC}"
    exit 1
fi

# Configuration
LOCAL_CONTAINER="wp-test-wordpress-1"
BACKUP_DIR="./db-backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/local_backup_$TIMESTAMP.sql"

# Cloud SQL Configuration
CLOUD_SQL_INSTANCE="${CLOUDSQL_INSTANCE_NAME:-wordpress-hello-world-349612720555:northamerica-northeast1:ctf-wordpress-db}"
CLOUD_SQL_DATABASE="${CLOUDSQL_DATABASE_NAME:-wordpress}"
CLOUD_SQL_USER="${CLOUDSQL_USERNAME:-wordpress}"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo -e "${YELLOW}Step 1: Exporting local database...${NC}"
docker-compose exec -T wordpress wp db export - --allow-root > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Local database exported to $BACKUP_FILE${NC}"
    FILE_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    echo -e "  File size: $FILE_SIZE"
else
    echo -e "${RED}✗ Failed to export local database${NC}"
    exit 1
fi

echo -e "\n${YELLOW}Step 2: Performing search-replace for domain migration...${NC}"
# Create a modified version with domain replacement
MODIFIED_BACKUP="$BACKUP_DIR/local_backup_${TIMESTAMP}_modified.sql"

# Replace localhost URLs with production domain
sed -e "s|http://localhost:8080|https://dev.taxpayer.com|g" \
    -e "s|https://localhost:8080|https://dev.taxpayer.com|g" \
    "$BACKUP_FILE" > "$MODIFIED_BACKUP"

echo -e "${GREEN}✓ Domain URLs updated${NC}"

echo -e "\n${YELLOW}Step 3: Backing up Cloud SQL database (safety backup)...${NC}"
CLOUD_BACKUP_NAME="backup-before-sync-$TIMESTAMP"
gcloud sql backups create \
    --instance="$CLOUD_SQL_INSTANCE" \
    --description="Backup before local sync at $TIMESTAMP" \
    2>/dev/null || echo -e "${YELLOW}Note: On-demand backup might be rate-limited. Continuing...${NC}"

echo -e "\n${YELLOW}Step 4: Uploading to Cloud SQL...${NC}"
echo -e "${RED}WARNING: This will REPLACE the Cloud SQL database!${NC}"
echo -e "Cloud SQL Instance: ${CLOUD_SQL_INSTANCE}"
echo -e "Database: ${CLOUD_SQL_DATABASE}"
echo -n "Type 'yes' to continue: "
read CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Sync cancelled${NC}"
    exit 0
fi

# Import to Cloud SQL using gcloud sql import
echo -e "\n${YELLOW}Importing database to Cloud SQL...${NC}"

# First, we need to upload to GCS bucket temporarily
GCS_BUCKET="${GCP_PROJECT_ID}-db-imports"
GCS_PATH="gs://$GCS_BUCKET/temp_import_$TIMESTAMP.sql"

# Create bucket if it doesn't exist
gsutil mb -p "$GCP_PROJECT_ID" "gs://$GCS_BUCKET" 2>/dev/null || true

# Upload to GCS
echo "Uploading to Google Cloud Storage..."
gsutil cp "$MODIFIED_BACKUP" "$GCS_PATH"

# Import from GCS to Cloud SQL
echo "Importing to Cloud SQL..."
gcloud sql import sql "$CLOUD_SQL_INSTANCE" "$GCS_PATH" \
    --database="$CLOUD_SQL_DATABASE" \
    --quiet

if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✓ Database successfully synced to Cloud SQL!${NC}"
    
    # Clean up GCS file
    echo "Cleaning up temporary files..."
    gsutil rm "$GCS_PATH"
    
    echo -e "\n${YELLOW}Step 5: Flushing Cloud Run cache...${NC}"
    # Trigger a new deployment or just note that cache should be cleared
    echo -e "${YELLOW}Note: You may need to clear the CDN cache or restart Cloud Run to see changes${NC}"
    echo -e "Run: gcloud run services update-traffic wordpress-hello-world --to-latest --region=northamerica-northeast1"
    
else
    echo -e "${RED}✗ Failed to import database to Cloud SQL${NC}"
    gsutil rm "$GCS_PATH" 2>/dev/null
    exit 1
fi

echo -e "\n${GREEN}=== Sync Complete ===${NC}"
echo -e "\nBackup files saved:"
echo -e "  Original: $BACKUP_FILE"
echo -e "  Modified: $MODIFIED_BACKUP"
echo -e "\n${YELLOW}Keep these files safe in case you need to restore!${NC}"

