# Configuration Summary

## Deployment Settings

This WordPress app is configured for:

### Region
- **Region (Cloud Run & Artifact Registry):** `northamerica-northeast1`

### Artifact Registry
- **Repository Name:** `wordpress_test`
- **Format:** Docker
- **Full Path:** `northamerica-northeast1-docker.pkg.dev/PROJECT_ID/wordpress_test/wordpress-hello-world`

### Service
- **Service Name:** `wordpress-hello-world`
- **Platform:** Cloud Run (fully managed)
- **Container Port:** 8080
- **Resources:**
  - Memory: 1Gi
  - CPU: 1
  - Timeout: 300s
  - Max Instances: 10
  - Min Instances: 0

## Image Tags

The GitHub Actions workflow pushes two tags:
1. `$GITHUB_SHA` - Specific commit hash (deployed)
2. `latest` - Latest build

## Auto-Detection Features

✅ **URL Auto-Detection:** The app automatically detects its Cloud Run URL from headers  
✅ **Database Socket:** Automatically configured for Cloud SQL Unix socket  
✅ **WordPress Configuration:** Auto-setup on first run

## What You Need

**Before deploying, ensure:**
1. ✅ Artifact Registry repository `wordpress_test` exists in `northamerica-northeast1`
2. ✅ Cloud SQL instance created in `northamerica-northeast1` (or nearby region)
3. ✅ Service account has `artifactregistry.writer` permission
4. ✅ All 10 required GitHub secrets are set

## Quick Check Commands

```bash
# Check if Artifact Registry repo exists
gcloud artifacts repositories describe wordpress_test \
  --location=northamerica-northeast1

# List Cloud SQL instances
gcloud sql instances list

# Test local build
docker build -t test-wordpress .
```

## Migration from GCR

If you were using Google Container Registry (GCR) before:
- ❌ Old: `gcr.io/PROJECT_ID/IMAGE_NAME`
- ✅ New: `northamerica-northeast1-docker.pkg.dev/PROJECT_ID/wordpress_test/IMAGE_NAME`

Artifact Registry is the modern replacement for the deprecated GCR.

