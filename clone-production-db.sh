#!/bin/bash

# Clone Production Database to Local (Simple Method)
# Uses Cloud SQL Proxy to connect and mysqldump to copy

set -e

echo "======================================"
echo "Clone Production DB → Local"
echo "======================================"
echo ""

# Check if Cloud SQL Proxy is installed
if ! command -v cloud-sql-proxy &> /dev/null; then
  echo "Installing Cloud SQL Proxy..."
  brew install cloud-sql-proxy
fi

# Check if local DB is running
if ! docker-compose ps db 2>/dev/null | grep -q "Up"; then
  echo "Starting local database..."
  docker-compose up -d db
  sleep 5
fi

echo "Step 1: Starting Cloud SQL Proxy..."
cloud-sql-proxy dashboard-254616:northamerica-northeast1:wordpress-test-db --port 3307 &
PROXY_PID=$!
sleep 3

echo "Step 2: Dumping production database..."
mysqldump \
  --host=127.0.0.1 \
  --port=3307 \
  --user=wordpress \
  --password='D~=X"?Ug5%e5P^uR' \
  --single-transaction \
  --quick \
  wordpress > production_dump.sql

echo "Step 3: Importing to local database..."
docker-compose exec -T db mysql -uwordpress -pwordpress wordpress < production_dump.sql

echo "Step 4: Cleanup..."
kill $PROXY_PID
rm production_dump.sql

echo ""
echo "======================================"
echo "✅ Production DB cloned to local!"
echo "======================================"
echo ""
echo "Start WordPress: docker-compose up"

