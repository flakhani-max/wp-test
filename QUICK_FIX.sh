#!/bin/bash
# Quick diagnosis and fix for database connection issues

set -e

PROJECT_ID=$(gcloud config get-value project 2>/dev/null)

echo "==================================================="
echo "WordPress Cloud SQL Connection Diagnostics"
echo "==================================================="
echo ""
echo "Project: $PROJECT_ID"
echo ""

echo "Step 1: Checking Cloud SQL instances..."
echo "---------------------------------------------------"
INSTANCES=$(gcloud sql instances list --format="value(name)")

if [ -z "$INSTANCES" ]; then
  echo "❌ No Cloud SQL instances found!"
  echo ""
  echo "Create one with:"
  echo "gcloud sql instances create wordpress-db \\"
  echo "  --database-version=MYSQL_8_0 \\"
  echo "  --tier=db-f1-micro \\"
  echo "  --region=northamerica-northeast1"
  exit 1
else
  echo "✅ Found Cloud SQL instances:"
  gcloud sql instances list --format="table(name,region,connectionName,state)"
  echo ""
fi

# Get the first instance for checking
INSTANCE_NAME=$(echo "$INSTANCES" | head -1)
CONNECTION_NAME=$(gcloud sql instances describe $INSTANCE_NAME --format="value(connectionName)")

echo "Step 2: Checking for 'wordpress' database..."
echo "---------------------------------------------------"
DATABASES=$(gcloud sql databases list --instance=$INSTANCE_NAME --format="value(name)")

if echo "$DATABASES" | grep -q "^wordpress$"; then
  echo "✅ Database 'wordpress' exists"
else
  echo "❌ Database 'wordpress' does NOT exist"
  echo ""
  read -p "Create database 'wordpress'? [y/N]: " -n 1 -r
  echo
  if [[ $REPLY =~ ^[Yy]$ ]]; then
    gcloud sql databases create wordpress --instance=$INSTANCE_NAME
    echo "✅ Database created"
  fi
fi
echo ""

echo "Step 3: Checking for 'wordpress' user..."
echo "---------------------------------------------------"
USERS=$(gcloud sql users list --instance=$INSTANCE_NAME --format="value(name)")

if echo "$USERS" | grep -q "^wordpress$"; then
  echo "✅ User 'wordpress' exists"
else
  echo "❌ User 'wordpress' does NOT exist"
  echo ""
  read -p "Create user 'wordpress' with a secure password? [y/N]: " -n 1 -r
  echo
  if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Enter password for 'wordpress' user:"
    read -s DB_PASSWORD
    gcloud sql users create wordpress \
      --instance=$INSTANCE_NAME \
      --password="$DB_PASSWORD"
    echo "✅ User created"
    echo ""
    echo "IMPORTANT: Save this password for your GitHub secret WORDPRESS_DB_PASSWORD"
  fi
fi
echo ""

echo "Step 4: Checking service account permissions..."
echo "---------------------------------------------------"
SA_EMAIL="github-actions-deploy@${PROJECT_ID}.iam.gserviceaccount.com"

if gcloud projects get-iam-policy $PROJECT_ID \
  --flatten="bindings[].members" \
  --filter="bindings.members:serviceAccount:${SA_EMAIL}" \
  --format="value(bindings.role)" | grep -q "cloudsql.client"; then
  echo "✅ Service account has Cloud SQL Client role"
else
  echo "❌ Service account missing Cloud SQL Client role"
  echo ""
  read -p "Grant Cloud SQL Client role? [y/N]: " -n 1 -r
  echo
  if [[ $REPLY =~ ^[Yy]$ ]]; then
    gcloud projects add-iam-policy-binding $PROJECT_ID \
      --member="serviceAccount:${SA_EMAIL}" \
      --role="roles/cloudsql.client"
    echo "✅ Permission granted"
  fi
fi
echo ""

echo "==================================================="
echo "GitHub Secrets Configuration"
echo "==================================================="
echo ""
echo "Set these secrets in GitHub:"
echo ""
echo "WORDPRESS_DB_HOST: localhost"
echo "WORDPRESS_DB_USER: wordpress"
echo "WORDPRESS_DB_PASSWORD: (the password you set for the user)"
echo "WORDPRESS_DB_NAME: wordpress"
echo "CLOUD_SQL_CONNECTION_NAME: $CONNECTION_NAME"
echo ""
echo "Full connection string: $CONNECTION_NAME"
echo ""
echo "==================================================="
echo "✅ Diagnostic complete!"
echo "==================================================="

