# WordPress Hello World - Docker + Cloud Run

Basic WordPress application with automated deployment to Google Cloud Run.

---

## 🔑 Admin Credentials

### Production (Cloud Run)
- **URL:** https://wordpress-hello-world-349612720555.northamerica-northeast1.run.app/wp-admin
- **Username:** `admin`
- **Password:** `admin123`

### Local Development
- **URL:** http://localhost:8080/wp-admin
- **Username:** `admin`
- **Password:** `admin123`

---

## 🚀 CI/CD Process

### How It Works

```
1. Make code changes locally
2. Commit & push to GitHub
3. GitHub Actions automatically:
   - Builds Docker image
   - Pushes to Artifact Registry
   - Deploys to Cloud Run
4. Site updates automatically
```

**⚡ Smart Deployment:** Only triggers on changes to:
- `Dockerfile` - Container configuration
- `custom-entrypoint.sh` - Startup script
- `wp-content/` - Themes, plugins, assets
- `.github/workflows/deploy.yml` - Deployment pipeline

**📝 Changes to these files WON'T trigger deployment:**
- `README.md`, `*.md` - Documentation
- `sync-*.sh`, `clone-*.sh` - Local development scripts
- Other non-application files

### Deploy Changes

```bash
# Make your changes, then:
git add .
git commit -m "Your change description"
git push
```

That's it! GitHub Actions handles everything else.

### Monitor Deployment

**Option 1: GitHub Web UI**
- Go to your repo → Actions tab
- Watch the workflow run

**Option 2: Command Line**
```bash
gh run list --limit 1
gh run watch
```

### Check Deployment Status

```bash
# View latest logs
gcloud run services logs read wordpress-hello-world \
  --region=northamerica-northeast1 \
  --limit=50

# Get service URL
gcloud run services describe wordpress-hello-world \
  --region=northamerica-northeast1 \
  --format="value(status.url)"
```

---

## 🛠️ GitHub Secrets (Already Configured)

The following secrets are configured in GitHub Actions:

| Secret | Value |
|--------|-------|
| `GCP_PROJECT_ID` | `dashboard-254616` |
| `GCP_SA_KEY` | Service account key JSON |
| `WORDPRESS_DB_HOST` | `localhost` |
| `WORDPRESS_DB_USER` | `wordpress` |
| `WORDPRESS_DB_PASSWORD` | `D~=X"?Ug5%e5P^uR` |
| `WORDPRESS_DB_NAME` | `wordpress` |
| `CLOUD_SQL_CONNECTION_NAME` | `dashboard-254616:northamerica-northeast1:wordpress-test-db` |
| `WP_ADMIN_USER` | `admin` |
| `WP_ADMIN_PASS` | `admin123` |
| `WP_ADMIN_EMAIL` | `admin@example.com` |

**To update a secret:**
```bash
gh secret set SECRET_NAME -b "new_value"
```

---

## 📦 Local Development (Optional)

```bash
# Start locally
docker-compose up

# Access at http://localhost:8080
# Stop with Ctrl+C

# Remove everything (including database)
docker-compose down -v
```

---

## 🔄 Sync Production Database to Local

```bash
# Clone production data for local testing
./sync-production-to-local.sh
```

This gives you a local copy of production data (sanitized for development).

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `.github/workflows/deploy.yml` | CI/CD pipeline |
| `Dockerfile` | Container definition |
| `custom-entrypoint.sh` | Startup script |
| `wp-content/themes/hello-world/` | Your custom theme |

---

## 🆘 Quick Troubleshooting

**Deployment failed?**
```bash
gh run view --log  # View error logs
```

**Can't log in?**
```bash
# Reset admin password
gh secret set WP_ADMIN_PASS -b "newpassword123"
git commit --allow-empty -m "Reset password"
git push
```

**Database not connecting?**
- Check Cloud SQL instance is running
- Verify `CLOUD_SQL_CONNECTION_NAME` has no trailing newline
- Ensure service account has Cloud SQL Client role

---

That's it! Push code → Deployment happens automatically → Site updates ✨
