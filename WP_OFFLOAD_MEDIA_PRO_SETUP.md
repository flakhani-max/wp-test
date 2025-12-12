# WP Offload Media Pro Setup Guide

This guide explains how to configure your WordPress installation to use WP Offload Media Pro instead of the Lite version.

## Prerequisites

1. Purchase a WP Offload Media Pro license from [DeliciousBrains](https://deliciousbrains.com/wp-offload-media/)
2. Have access to Google Cloud Secret Manager

## Setup Steps

### 1. Add License Key to Google Secret Manager

```bash
# Create the secret (first time)
gcloud secrets create WP_OFFLOAD_MEDIA_LICENSE \
  --data-file=- \
  --project=dashboard-254616 <<< "YOUR_LICENSE_KEY_HERE"

# Or update if it already exists
echo "YOUR_LICENSE_KEY_HERE" | gcloud secrets versions add WP_OFFLOAD_MEDIA_LICENSE \
  --data-file=- \
  --project=dashboard-254616
```

Replace `YOUR_LICENSE_KEY_HERE` with your actual license key from DeliciousBrains.

### 2. Pull Secrets for Local Development

```bash
cd /Users/firoz/Documents/CTF/wp-test
./pull-secrets.sh
```

This will fetch all secrets including the new WP Offload Media license and save them to `.env`.

### 3. Rebuild Docker Image

For local development:

```bash
# Clean rebuild to install WP Offload Media Pro
docker-compose down -v
docker-compose build --no-cache
docker-compose up
```

### 4. Deploy to Production

Simply push your changes to trigger the GitHub Actions workflow:

```bash
git add .
git commit -m "Add WP Offload Media Pro support"
git push
```

The GitHub Actions workflow will:
- Fetch the license key from Secret Manager
- Build the Docker image with WP Offload Media Pro
- Deploy to Cloud Run with the license key configured

## How It Works

### Runtime Configuration (Not Build-Time)
- **WP Offload Media Lite** is installed in the Docker image during build
- **WP Offload Media Pro** is configured at runtime when container starts
- The `custom-entrypoint.sh` script checks for `WP_OFFLOAD_MEDIA_LICENSE` environment variable
- If license key is present, it configures the Pro license in WordPress
- If no license key, it simply uses the Lite version

### Why Runtime vs Build-Time?
- DeliciousBrains' download URLs require proper authentication that's difficult to handle during Docker build
- Runtime configuration is more flexible and reliable
- Same Docker image works for both Lite and Pro (just add/remove license key)
- License can be updated without rebuilding the entire Docker image

### Configuration Files Modified
1. **Dockerfile** - Installs WP Offload Media Lite (Pro configured at runtime)
2. **docker-compose.yml** - Passes environment variables to container
3. **custom-entrypoint.sh** - Configures Pro license at startup if provided
4. **pull-secrets.sh** - Fetches license from Secret Manager
5. **.github/workflows/deploy.yml** - Passes license as environment variable to Cloud Run

## Pro Features Available

With WP Offload Media Pro, you get:

- ✅ **Assets Pull** - Serve CSS, JS, and other assets from GCS
- ✅ **WooCommerce Support** - Offload WooCommerce product images
- ✅ **Easy Digital Downloads Support**
- ✅ **Media Library Browsing** - Browse GCS bucket directly in WordPress
- ✅ **Download from Bucket** - Download files from GCS back to WordPress
- ✅ **Remove Local Media** - Save server space by removing local copies
- ✅ **Private Media** - Control access to files with signed URLs
- ✅ **Priority Support** - Direct support from DeliciousBrains

## Verification

After deploying, verify Pro is active:

1. Go to WordPress Admin → Settings → Offload Media
2. You should see "WP Offload Media Pro" in the title
3. Check for Pro-only features in the settings

## Troubleshooting

### Pro Version Not Installing

Check if license key is properly set:

```bash
# Verify secret exists
gcloud secrets describe WP_OFFLOAD_MEDIA_LICENSE --project=dashboard-254616

# Check secret value
gcloud secrets versions access latest --secret=WP_OFFLOAD_MEDIA_LICENSE --project=dashboard-254616
```

### Fallback to Lite

If the license key is invalid or not provided, the system will automatically fall back to installing WP Offload Media Lite.

### License Not Activating

Check the container logs in Cloud Run:
```bash
gcloud run services logs read wp-test --region northamerica-northeast1 --limit 100 | grep -i offload
```

## Support

- **WP Offload Media Pro**: https://deliciousbrains.com/support/
- **License Management**: https://deliciousbrains.com/my-account/

## Notes

- The license key is stored securely in Google Secret Manager
- License is never committed to Git
- Same configuration works for both local development and production
- You can switch back to Lite by simply not providing the license key


