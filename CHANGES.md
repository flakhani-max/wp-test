# Migration to Artifact Registry - Changes Summary

## ✅ Updated: Container Registry → Artifact Registry

### What Changed

1. **GitHub Actions Workflow** (`.github/workflows/deploy.yml`)
   - ❌ Old: `gcr.io/PROJECT_ID/IMAGE_NAME`
   - ✅ New: `northamerica-northeast1-docker.pkg.dev/PROJECT_ID/wordpress_test/IMAGE_NAME`
   - Changed region from `us-central1` to `northamerica-northeast1`
   - Using your existing Artifact Registry repo: `wordpress_test`

2. **Docker Authentication**
   - ❌ Old: `gcloud auth configure-docker`
   - ✅ New: `gcloud auth configure-docker northamerica-northeast1-docker.pkg.dev`

3. **Service Account Permissions**
   - ❌ Old: `roles/storage.admin` (for GCR)
   - ✅ New: `roles/artifactregistry.writer` (for Artifact Registry)

4. **Required APIs**
   - ❌ Removed: `containerregistry.googleapis.com`
   - ✅ Added: `artifactregistry.googleapis.com`

### Configuration

Your app is now configured for:

```yaml
Region: northamerica-northeast1
Artifact Registry Repo: wordpress_test
Service Name: wordpress-hello-world
Image Path: northamerica-northeast1-docker.pkg.dev/{PROJECT_ID}/wordpress_test/wordpress-hello-world
```

### No Action Required If...

✅ You already have the `wordpress_test` repository in `northamerica-northeast1`  
✅ Your service account has `artifactregistry.writer` permission  
✅ The `artifactregistry.googleapis.com` API is enabled

### Action Required

If you haven't set up the service account permissions yet, run:

```bash
PROJECT_ID=$(gcloud config get-value project)

gcloud projects add-iam-policy-binding $PROJECT_ID \
  --member="serviceAccount:github-actions@${PROJECT_ID}.iam.gserviceaccount.com" \
  --role="roles/artifactregistry.writer"
```

### Files Modified

- `.github/workflows/deploy.yml` - Complete rewrite for Artifact Registry
- `README.md` - Updated instructions and commands
- `DEPLOYMENT_GUIDE.md` - Updated deployment info
- `CONFIG.md` - New configuration reference (created)
- `.gcloudignore` - Ignore unnecessary files (created)

### Verification

Test that everything works:

```bash
# 1. Local build test
cd /Users/firoz/Documents/CTF/wp-test
docker-compose up --build

# 2. Verify Artifact Registry repo exists
gcloud artifacts repositories describe wordpress_test \
  --location=northamerica-northeast1

# 3. Check service account permissions
gcloud projects get-iam-policy $(gcloud config get-value project) \
  --flatten="bindings[].members" \
  --filter="bindings.members:github-actions@*" \
  --format="table(bindings.role)"
```

### Expected Deployment URL

After successful deployment, your WordPress site will be at:

```
https://wordpress-hello-world-[RANDOM-HASH]-nn.a.run.app
```

The `-nn` indicates northamerica-northeast1 region.

---

**Ready to deploy!** Just push to your `main` branch and GitHub Actions will handle the rest.

