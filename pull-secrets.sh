#!/bin/bash
# Pull secrets from Google Cloud Secret Manager to .env.local.secrets
# Keeps .env.local clean and safe to commit
# Usage: ./pull-secrets.sh

PROJECT_ID="dashboard-254616"

echo "Fetching secrets from Google Cloud Secret Manager..."

# Fetch production secrets
GCS_BUCKET_NAME=$(gcloud secrets versions access latest --secret=GCS_BUCKET_NAME --project=$PROJECT_ID)
WP_MEDIA_SA_KEY=$(gcloud secrets versions access latest --secret=WP_MEDIA_SA_KEY --project=$PROJECT_ID)
ACF_PRO_KEY=$(gcloud secrets versions access latest --secret=ACF_PRO_KEY --project=$PROJECT_ID)
STRIPE_PUBLISHABLE_KEY=$(gcloud secrets versions access latest --secret=STRIPE_PUBLISHABLE_KEY --project=$PROJECT_ID)
STRIPE_SECRET_KEY=$(gcloud secrets versions access latest --secret=STRIPE_SECRET_KEY --project=$PROJECT_ID)
WP_ADMIN_USER=$(gcloud secrets versions access latest --secret=WP_ADMIN_USER --project=$PROJECT_ID)
WP_ADMIN_PASS=$(gcloud secrets versions access latest --secret=WP_ADMIN_PASS --project=$PROJECT_ID)
WP_ADMIN_EMAIL=$(gcloud secrets versions access latest --secret=WP_ADMIN_EMAIL --project=$PROJECT_ID)
# Write to .env.local.secrets (gitignored)
cat > .env.local.secrets << EOF
# Production secrets from Secret Manager
# Generated: $(date)
# This file is gitignored - safe to have real secrets here

GCS_BUCKET_NAME=$GCS_BUCKET_NAME
WP_MEDIA_SA_KEY='$WP_MEDIA_SA_KEY'
ACF_PRO_KEY=$ACF_PRO_KEY
STRIPE_PUBLISHABLE_KEY=$STRIPE_PUBLISHABLE_KEY
STRIPE_SECRET_KEY=$STRIPE_SECRET_KEY
WP_ADMIN_USER=$WP_ADMIN_USER
WP_ADMIN_PASS=$WP_ADMIN_PASS
WP_ADMIN_EMAIL=$WP_ADMIN_EMAIL
EOF

echo "✅ Production secrets saved to .env.local.secrets"
echo "This file is gitignored and will not be committed"
echo "You can now run: docker-compose up"

