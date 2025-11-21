#!/bin/bash

# Alternative script to sync local WordPress database to Cloud SQL using Cloud SQL Proxy
# This method doesn't require GCS bucket

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== WordPress Database Sync: Local → Cloud SQL (via Proxy) ===${NC}\n"

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

echo -e "\n${YELLOW}Step 3: Connecting to Cloud SQL via Proxy...${NC}"
echo -e "${RED}WARNING: This will REPLACE the Cloud SQL database!${NC}"
echo -e "Cloud SQL Instance: ${CLOUD_SQL_INSTANCE}"
echo -e "Database: ${CLOUD_SQL_DATABASE}"
echo -n "Type 'yes' to continue: "
read CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Sync cancelled${NC}"
    exit 0
fi

# Check if cloud-sql-proxy is installed
if ! command -v cloud-sql-proxy &> /dev/null; then
    echo -e "${RED}Error: cloud-sql-proxy not found${NC}"
    echo -e "Install it with: brew install cloud-sql-proxy"
    echo -e "Or download from: https://cloud.google.com/sql/docs/mysql/connect-admin-proxy"
    exit 1
fi

# Start Cloud SQL Proxy in background
echo "Starting Cloud SQL Proxy..."
cloud-sql-proxy "$CLOUD_SQL_INSTANCE" --port=3307 &
PROXY_PID=$!

# Wait for proxy to be ready
sleep 5

# Import using mysql client
echo -e "\n${YELLOW}Step 4: Importing database to Cloud SQL...${NC}"

# Get Cloud SQL password from Secret Manager
CLOUD_SQL_PASSWORD=$(gcloud secrets versions access latest --secret="CLOUDSQL_PASSWORD" 2>/dev/null || echo "$CLOUDSQL_PASSWORD")

if [ -z "$CLOUD_SQL_PASSWORD" ]; then
    echo -e "${RED}Error: Could not get Cloud SQL password${NC}"
    kill $PROXY_PID
    exit 1
fi

# Import the database
mysql -h 127.0.0.1 -P 3307 -u "$CLOUD_SQL_USER" -p"$CLOUD_SQL_PASSWORD" "$CLOUD_SQL_DATABASE" < "$MODIFIED_BACKUP"

if [ $? -eq 0 ]; then
    echo -e "\n${GREEN}✓ Database successfully synced to Cloud SQL!${NC}"
else
    echo -e "${RED}✗ Failed to import database to Cloud SQL${NC}"
    kill $PROXY_PID
    exit 1
fi

# Stop Cloud SQL Proxy
kill $PROXY_PID
echo "Cloud SQL Proxy stopped"

echo -e "\n${GREEN}=== Sync Complete ===${NC}"
echo -e "\nBackup files saved:"
echo -e "  Original: $BACKUP_FILE"
echo -e "  Modified: $MODIFIED_BACKUP"
echo -e "\n${YELLOW}Keep these files safe in case you need to restore!${NC}"

