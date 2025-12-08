#!/bin/bash
# Pull secrets from Google Cloud Secret Manager
# Usage:
#   ./pull-secrets.sh              # write .env (current behavior)
#   ./pull-secrets.sh --export     # print `export KEY=...` to stdout (no file)
#   ./pull-secrets.sh --env-file   # print KEY=VALUE lines for `docker compose --env-file <(./pull-secrets.sh --env-file)`

PROJECT_ID="dashboard-254616"
MODE="${1:-file}"

echo "Fetching secrets from Google Cloud Secret Manager..."

# Fetch production secrets
GCS_BUCKET_NAME=$(gcloud secrets versions access latest --secret=GCS_BUCKET_NAME --project=$PROJECT_ID)
WP_MEDIA_SA_KEY_BASE64=$(gcloud secrets versions access latest --secret=WP_MEDIA_SA_KEY --project=$PROJECT_ID | base64 | tr -d '\\n')
ACF_PRO_KEY=$(gcloud secrets versions access latest --secret=ACF_PRO_KEY --project=$PROJECT_ID)
STRIPE_PUBLISHABLE_KEY=$(gcloud secrets versions access latest --secret=STRIPE_PUBLISHABLE_KEY --project=$PROJECT_ID)
STRIPE_SECRET_KEY=$(gcloud secrets versions access latest --secret=STRIPE_SECRET_KEY --project=$PROJECT_ID)
WP_ADMIN_USER=$(gcloud secrets versions access latest --secret=WP_ADMIN_USER --project=$PROJECT_ID)
WP_ADMIN_PASS=$(gcloud secrets versions access latest --secret=WP_ADMIN_PASS --project=$PROJECT_ID)
WP_ADMIN_EMAIL=$(gcloud secrets versions access latest --secret=WP_ADMIN_EMAIL --project=$PROJECT_ID)
PAYPAL_CLIENT_ID=$(gcloud secrets versions access latest --secret=PAYPAL_CLIENT_ID --project=$PROJECT_ID)
PAYPAL_SECRET=$(gcloud secrets versions access latest --secret=PAYPAL_SECRET --project=$PROJECT_ID)
CTF_MAILCHIMP_API_KEY=$(gcloud secrets versions access latest --secret=ctf_mailchimp_api_key --project=$PROJECT_ID)

render_env() {
  local prefix="$1"
  cat << EOF
${prefix}GCS_BUCKET_NAME=$GCS_BUCKET_NAME
${prefix}WP_MEDIA_SA_KEY=$WP_MEDIA_SA_KEY_BASE64
${prefix}ACF_PRO_KEY=$ACF_PRO_KEY
${prefix}STRIPE_PUBLISHABLE_KEY=$STRIPE_PUBLISHABLE_KEY
${prefix}STRIPE_SECRET_KEY=$STRIPE_SECRET_KEY
${prefix}WP_ADMIN_USER=$WP_ADMIN_USER
${prefix}WP_ADMIN_PASS=$WP_ADMIN_PASS
${prefix}WP_ADMIN_EMAIL=$WP_ADMIN_EMAIL
${prefix}PAYPAL_CLIENT_ID=$PAYPAL_CLIENT_ID
${prefix}PAYPAL_SECRET=$PAYPAL_SECRET
${prefix}CTF_MAILCHIMP_API_KEY=$CTF_MAILCHIMP_API_KEY
EOF
}

case "$MODE" in
  --export)
    render_env "export "
    ;;
  --env-file)
    render_env ""
    ;;
  *)
    # Write to .env (gitignored)
    {
      cat << EOF
# Production secrets from Secret Manager
# Generated: $(date)
# This file is gitignored - safe to have real secrets here

EOF
      render_env ""
    } > .env

    echo "✅ All secrets saved to .env"
    echo "This file is gitignored and will not be committed"
    echo ""
    echo "You can now run: docker-compose build && docker-compose up"
    ;;
esac
