# WP Offload Media - Cloud Run Configuration Fix

## Problem
WP Offload Media works locally but fails on Cloud Run with "Media cannot be offloaded due to an invalid key file path."

## Root Cause
The GCS service account key JSON file is not being passed to the Cloud Run container. The key needs to be stored in Secret Manager and passed as an environment variable.

## Solution

### Step 1: Store GCS Key in Secret Manager

```bash
# Set your project ID
export PROJECT_ID="dashboard-254616"

# Create a secret for the GCS key file (store the entire JSON file)
gcloud secrets create gcs-media-key \
  --project=${PROJECT_ID} \
  --data-file=gcs-key.json

# Verify the secret was created
gcloud secrets describe gcs-media-key --project=${PROJECT_ID}
```

### Step 2: Grant Service Account Access to the Secret

Your Cloud Run service account needs permission to access this secret:

```bash
# Get your runtime service account email (check GitHub secrets or Cloud Run service)
export RUNTIME_SA="YOUR_SERVICE_ACCOUNT_EMAIL"  # e.g., ctf-wordpress-sa@dashboard-254616.iam.gserviceaccount.com

# Grant access to the GCS key secret
gcloud secrets add-iam-policy-binding gcs-media-key \
  --project=${PROJECT_ID} \
  --member="serviceAccount:${RUNTIME_SA}" \
  --role="roles/secretmanager.secretAccessor"
```

### Step 3: Update GitHub Secrets

Add a new GitHub repository secret:

1. Go to your GitHub repository
2. Settings → Secrets and variables → Actions
3. Click "New repository secret"
4. Name: `GCS_KEY_FILE_SECRET`
5. Value: `gcs-media-key` (the secret name in Secret Manager)

### Step 4: Update GitHub Actions Deploy Workflow

Modify `.github/workflows/deploy.yml` to pass the GCS key from Secret Manager:

```yaml
# In the "Deploy to Cloud Run" step, add to the ENV_VARS section:

- name: Deploy to Cloud Run
  env:
    # ... existing env vars ...
    GCS_BUCKET_NAME: ${{ secrets.GCS_BUCKET_NAME }}
  
  run: |
    # Build environment variables safely
    ENV_VARS="WORDPRESS_DB_HOST=${WORDPRESS_DB_HOST}"
    # ... existing ENV_VARS lines ...
    ENV_VARS="${ENV_VARS},GCS_BUCKET_NAME=${GCS_BUCKET_NAME}"
    
    # Deploy with secrets from Secret Manager
    gcloud run deploy ${SERVICE_NAME} \
      --image ${IMAGE_BASE}:${GITHUB_SHA} \
      --region ${REGION} \
      --platform managed \
      --allow-unauthenticated \
      --service-account "${RUNTIME_SA}" \
      --set-env-vars "${ENV_VARS}" \
      --update-secrets GCS_KEY_FILE=gcs-media-key:latest \
      --add-cloudsql-instances "${CLOUD_SQL_CONNECTION_NAME}" \
      --memory 1Gi \
      --cpu 1 \
      --timeout 300 \
      --max-instances 10 \
      --min-instances 0
```

The key addition is: `--update-secrets GCS_KEY_FILE=gcs-media-key:latest`

This mounts the secret as an environment variable `GCS_KEY_FILE` that your `custom-entrypoint.sh` already handles!

### Step 5: Verify GCS_BUCKET_NAME GitHub Secret

Make sure you have the GCS bucket name set in GitHub secrets:

- Secret name: `GCS_BUCKET_NAME`
- Secret value: Your GCS bucket name (e.g., `taxpayer-media-bucket`)

### Step 6: Deploy

```bash
git add .github/workflows/deploy.yml
git commit -m "Fix WP Offload Media on Cloud Run - add GCS key from Secret Manager"
git push
```

## How It Works

1. **Secret Manager** stores your GCS key JSON securely
2. **Cloud Run** mounts it as environment variable `GCS_KEY_FILE`
3. **custom-entrypoint.sh** (line 263-265) writes it to `/var/www/html/wp-content/uploads/gcs-key.json`
4. **wp-config.php** (line 302) configures WP Offload Media to use that path
5. **WP Offload Media** uploads to GCS successfully!

## Verification

After deployment, check the logs:

```bash
gcloud run services logs read wordpress-hello-world \
  --region=northamerica-northeast1 \
  --limit=100 \
  --project=dashboard-254616
```

Look for:
- `🔑 Writing GCS key from environment variable...`
- `✓ WP Offload Media Lite setup complete`
- `🔧 Adding AS3CF_SETTINGS to wp-config.php...` or `✅ AS3CF_SETTINGS already defined`

## Alternative: Quick Manual Fix (For Testing)

If you want to test immediately without Secret Manager:

```bash
# Get the base64-encoded key
export GCS_KEY_BASE64=$(base64 -i gcs-key.json)

# Deploy with the key directly as env var (NOT RECOMMENDED for production)
gcloud run services update wordpress-hello-world \
  --region=northamerica-northeast1 \
  --update-env-vars GCS_KEY_FILE="${GCS_KEY_BASE64}" \
  --project=dashboard-254616
```

⚠️ **Warning**: This approach stores the key in plain environment variables. Use Secret Manager for production!

## Troubleshooting

### Secret not found
```bash
# List all secrets
gcloud secrets list --project=dashboard-254616

# If missing, create it
gcloud secrets create gcs-media-key --data-file=gcs-key.json --project=dashboard-254616
```

### Permission denied
```bash
# Check if service account has access
gcloud secrets get-iam-policy gcs-media-key --project=dashboard-254616
```

### Key file not written
Check Cloud Run logs for the entrypoint script output. You should see:
```
🔑 Writing GCS key from environment variable...
```

If you see:
```
⚠️  No GCS key file found – media uploads may fail
```

Then the `GCS_KEY_FILE` environment variable is not set correctly.

## Clean Up Old Key Files (Optional)

If you previously tried other methods, you can remove the local `gcs-key.json` from the repo:

```bash
# Add to .gitignore if not already there
echo "gcs-key.json" >> .gitignore

# Remove from git tracking (keeps local file)
git rm --cached gcs-key.json
git commit -m "Remove GCS key from repo (now in Secret Manager)"
```

