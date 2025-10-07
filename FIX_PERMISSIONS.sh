#!/bin/bash
# Fix Artifact Registry permissions for GitHub Actions service account

set -e

# Get current project
PROJECT_ID=$(gcloud config get-value project 2>/dev/null)

if [ -z "$PROJECT_ID" ]; then
  echo "Error: No GCP project set. Run: gcloud config set project YOUR_PROJECT_ID"
  exit 1
fi

echo "Working with project: $PROJECT_ID"
echo ""

# Service account email
SA_EMAIL="github-actions-deploy@${PROJECT_ID}.iam.gserviceaccount.com"

echo "Setting up permissions for: $SA_EMAIL"
echo ""

# Grant Artifact Registry Writer permission
echo "1. Granting Artifact Registry Writer role..."
gcloud projects add-iam-policy-binding $PROJECT_ID \
  --member="serviceAccount:$SA_EMAIL" \
  --role="roles/artifactregistry.writer" \
  --condition=None

# Grant Cloud Run Admin permission (if not already set)
echo ""
echo "2. Granting Cloud Run Admin role..."
gcloud projects add-iam-policy-binding $PROJECT_ID \
  --member="serviceAccount:$SA_EMAIL" \
  --role="roles/run.admin" \
  --condition=None

# Grant Service Account User permission
echo ""
echo "3. Granting Service Account User role..."
gcloud projects add-iam-policy-binding $PROJECT_ID \
  --member="serviceAccount:$SA_EMAIL" \
  --role="roles/iam.serviceAccountUser" \
  --condition=None

# Grant Cloud SQL Client permission (for Cloud Run to connect)
echo ""
echo "4. Granting Cloud SQL Client role..."
gcloud projects add-iam-policy-binding $PROJECT_ID \
  --member="serviceAccount:$SA_EMAIL" \
  --role="roles/cloudsql.client" \
  --condition=None

echo ""
echo "✅ Permissions set successfully!"
echo ""
echo "Verify permissions with:"
echo "gcloud projects get-iam-policy $PROJECT_ID --flatten=\"bindings[].members\" --filter=\"bindings.members:$SA_EMAIL\" --format=\"table(bindings.role)\""

