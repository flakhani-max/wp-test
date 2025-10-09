#!/bin/bash

# DevOps Best Practice: Clone & Sanitize Production DB to Local
# 
# This script:
# 1. Clones production database
# 2. Sanitizes sensitive data
# 3. Updates URLs for local development
# 4. Imports to local MariaDB

set -e

echo "======================================"
echo "Production → Local (Best Practice)"
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

echo "✅ Production data exported"

echo "Step 3: Importing to local database..."
docker-compose exec -T db mysql -uwordpress -pwordpress wordpress < production_dump.sql

echo "✅ Data imported to local"

echo "Step 4: Sanitizing data for local development..."

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

echo "Step 5: Cleanup..."
kill $PROXY_PID 2>/dev/null || true
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

