# Quick Deployment Guide

## ✅ You're Ready to Deploy a New Cloud Run Service!

The app now **automatically detects its Cloud Run URL** - you don't need to know it in advance!

## Required GitHub Secrets (10 total)

Set these in: `GitHub Repository → Settings → Secrets and variables → Actions`

### 1. Google Cloud Platform (2 secrets)
```
GCP_PROJECT_ID          → Your GCP project ID (e.g., my-project-123456)
GCP_SA_KEY              → Service account JSON key (entire contents of .json file)
```

### 2. Database Connection (5 secrets)
```
WORDPRESS_DB_HOST       → localhost (for Cloud SQL socket)
WORDPRESS_DB_USER       → wordpress (or your DB username)
WORDPRESS_DB_PASSWORD   → your_secure_database_password
WORDPRESS_DB_NAME       → wordpress (or your database name)
CLOUD_SQL_CONNECTION_NAME → project-id:region:instance-name
```

### 3. WordPress Admin (3 secrets)
```
WP_ADMIN_USER          → admin (or your preferred username)
WP_ADMIN_PASS          → your_secure_admin_password
WP_ADMIN_EMAIL         → admin@yourdomain.com
```

## Optional Secrets (not needed for first deployment)
```
WP_URL                 → Only set if using custom domain
WP_TITLE               → Defaults to "Hello World WordPress"
```

## Deployment Steps

1. **Set up Cloud SQL database** (see main README)
2. **Create service account** with proper permissions (see main README)
3. **Add the 10 required secrets above** to GitHub
4. **Push to main branch:**
   ```bash
   git init
   git add .
   git commit -m "Deploy WordPress to Cloud Run"
   git remote add origin <your-repo-url>
   git push -u origin main
   ```
5. **Watch GitHub Actions** deploy your site
6. **Get your URL** from the Actions output

## After First Deployment

Your Cloud Run URL will be: `https://wordpress-hello-world-XXXXX-nn.a.run.app`

**Region:** northamerica-northeast1  
**Artifact Registry:** wordpress_test

The app will automatically:
- ✅ Detect its own URL
- ✅ Configure WordPress correctly
- ✅ Handle redirects properly
- ✅ Work with the generated Cloud Run URL

## Custom Domain (Optional)

If you want to use a custom domain later:
1. Set up domain mapping in Cloud Run
2. Add `WP_URL` secret with your custom domain
3. Redeploy

## Test Locally First

```bash
cd /Users/firoz/Documents/CTF/wp-test
docker-compose up --build
```

Visit: http://localhost:8080

---

**Need help?** Check the main README.md for detailed setup instructions.

