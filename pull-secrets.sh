#!/bin/bash
# Pull secrets from Google Cloud Secret Manager and update .env.local
# Keeps local database settings, adds production secrets
# Usage: ./pull-secrets.sh

PROJECT_ID="dashboard-254616"

echo "Fetching secrets from Google Cloud Secret Manager..."
echo "Updating .env.local with production secrets..."

# Backup existing .env.local
cp .env.local .env.local.backup

# Create temporary file with production secrets
TEMP_FILE=$(mktemp)

# Fetch production secrets
GCS_BUCKET_NAME=$(gcloud secrets versions access latest --secret=GCS_BUCKET_NAME --project=$PROJECT_ID)
WP_MEDIA_SA_KEY=$(gcloud secrets versions access latest --secret=WP_MEDIA_SA_KEY --project=$PROJECT_ID)
ACF_PRO_KEY=$(gcloud secrets versions access latest --secret=ACF_PRO_KEY --project=$PROJECT_ID)

# Update .env.local while preserving local database settings
# Remove old secrets if they exist
sed -i.bak '/^GCS_BUCKET_NAME=/d' .env.local
sed -i.bak '/^WP_MEDIA_SA_KEY=/d' .env.local
sed -i.bak '/^ACF_PRO_KEY=/d' .env.local

# Append production secrets
echo "" >> .env.local
echo "# Production secrets (updated $(date))" >> .env.local
echo "GCS_BUCKET_NAME=$GCS_BUCKET_NAME" >> .env.local
echo "WP_MEDIA_SA_KEY='$WP_MEDIA_SA_KEY'" >> .env.local
echo "ACF_PRO_KEY=$ACF_PRO_KEY" >> .env.local

# Clean up backup files
rm -f .env.local.bak .env.local.backup

echo "✅ .env.local updated with production secrets!"
echo "Local database settings preserved (WORDPRESS_DB_HOST=db)"
echo "You can now run: docker-compose up"

