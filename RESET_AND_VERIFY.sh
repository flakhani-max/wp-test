#!/bin/bash
# Reset and verify all database credentials

set -e

PROJECT_ID=$(gcloud config get-value project 2>/dev/null)
INSTANCE_NAME="wordpress-test-db"
DB_NAME="wordpress"
DB_USER="wordpress"

echo "==================================================="
echo "WordPress Database Reset & Verification"
echo "==================================================="
echo ""
echo "Project: $PROJECT_ID"
echo "Instance: $INSTANCE_NAME"
echo ""

# Get connection name
CONNECTION_NAME=$(gcloud sql instances describe $INSTANCE_NAME --format="value(connectionName)")

echo "Step 1: Resetting 'wordpress' user password..."
echo "---------------------------------------------------"
echo ""
echo "We'll reset the password to something you can copy/paste into GitHub."
echo ""
read -p "Enter a NEW password for the 'wordpress' database user: " -s NEW_PASSWORD
echo ""
read -p "Confirm password: " -s CONFIRM_PASSWORD
echo ""

if [ "$NEW_PASSWORD" != "$CONFIRM_PASSWORD" ]; then
  echo "❌ Passwords don't match!"
  exit 1
fi

if [ -z "$NEW_PASSWORD" ]; then
  echo "❌ Password cannot be empty!"
  exit 1
fi

echo ""
echo "Resetting password..."
gcloud sql users set-password $DB_USER \
  --instance=$INSTANCE_NAME \
  --password="$NEW_PASSWORD"

echo "✅ Password reset successfully!"
echo ""

echo "Step 2: Testing database connection..."
echo "---------------------------------------------------"
echo "Checking if database exists and is accessible..."

# List databases to verify it's working
if gcloud sql databases list --instance=$INSTANCE_NAME --format="value(name)" | grep -q "^${DB_NAME}$"; then
  echo "✅ Database '${DB_NAME}' exists and is accessible"
else
  echo "❌ Cannot find database '${DB_NAME}'"
  echo ""
  read -p "Create database '${DB_NAME}'? [y/N]: " -n 1 -r
  echo
  if [[ $REPLY =~ ^[Yy]$ ]]; then
    gcloud sql databases create $DB_NAME --instance=$INSTANCE_NAME
    echo "✅ Database created"
  fi
fi
echo ""

echo "==================================================="
echo "✅ ALL SET! Copy these values to GitHub Secrets"
echo "==================================================="
echo ""
echo "Go to: GitHub Repository → Settings → Secrets and variables → Actions"
echo ""
echo "Then create or update these secrets:"
echo ""
echo "---------------------------------------------------"
echo "Secret Name: GCP_PROJECT_ID"
echo "Value:"
echo "$PROJECT_ID"
echo ""
echo "---------------------------------------------------"
echo "Secret Name: WORDPRESS_DB_HOST"
echo "Value:"
echo "localhost"
echo ""
echo "---------------------------------------------------"
echo "Secret Name: WORDPRESS_DB_USER"
echo "Value:"
echo "$DB_USER"
echo ""
echo "---------------------------------------------------"
echo "Secret Name: WORDPRESS_DB_PASSWORD"
echo "Value:"
echo "$NEW_PASSWORD"
echo ""
echo "---------------------------------------------------"
echo "Secret Name: WORDPRESS_DB_NAME"
echo "Value:"
echo "$DB_NAME"
echo ""
echo "---------------------------------------------------"
echo "Secret Name: CLOUD_SQL_CONNECTION_NAME"
echo "Value:"
echo "$CONNECTION_NAME"
echo ""
echo "---------------------------------------------------"
echo "Secret Name: WP_ADMIN_USER"
echo "Value:"
echo "admin"
echo ""
echo "---------------------------------------------------"
echo "Secret Name: WP_ADMIN_PASS"
echo "Value:"
echo "SecureWordPressPassword123!"
echo "(or choose your own secure password)"
echo ""
echo "---------------------------------------------------"
echo "Secret Name: WP_ADMIN_EMAIL"
echo "Value:"
echo "admin@taxpayer.com"
echo "(or your actual email)"
echo ""
echo "==================================================="
echo ""
echo "⚠️  IMPORTANT: "
echo "- Copy the ENTIRE value (including any special characters)"
echo "- Don't add extra spaces or quotes"
echo "- WORDPRESS_DB_HOST must be exactly: localhost"
echo ""
echo "After updating secrets in GitHub:"
echo "1. Go to Actions tab"
echo "2. Click 'Deploy to Google Cloud Run'"
echo "3. Click 'Run workflow' → 'Run workflow'"
echo ""
echo "==================================================="

