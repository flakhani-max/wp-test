# WordPress - Cloud Run

---

## 🔑 Admin Login

**Production:** <https://wordpress-hello-world-349612720555.northamerica-northeast1.run.app/wp-admin>

**Username:** `admin`  
**Password:** `admin123`

---

## 🚀 Deploy Changes

```bash
git add .
git commit -m "Your change"
git push
```

Done. GitHub Actions handles the rest.

---

## 📊 Monitor Deployment

```bash
# Watch deployment
gh run list --limit 1

# View logs
gcloud run services logs read wordpress-hello-world --region=northamerica-northeast1 --limit=50
```

---

## 💻 Local Development

### First Time Setup

```bash
# 1. Pull production secrets (GCS, ACF Pro key, etc.)
./pull-secrets.sh
# This creates .env (gitignored)

# 2. Build Docker image with ACF Pro
docker-compose build

# 3. Clone production data (optional, for testing with real data)
./sync-production-to-local.sh

# 4. Start local WordPress
docker-compose up
```

**Login:** <http://localhost:8080/wp-admin> (admin/admin123)

### Making Changes

```bash
# Edit files in:
#   - wp-content/themes/
#   - wp-content/plugins/
#   - Dockerfile, custom-entrypoint.sh, etc.

# Changes auto-reload (no rebuild needed for theme/plugin edits)

# Deploy when ready
git add .
git commit -m "Your changes"
git push
```

**Note:** 
- `.env.local` has local database settings (safe to commit)
- `.env` has all production secrets (gitignored, auto-created by `pull-secrets.sh`)

---

## 🔄 Rebuilding Docker After Changes

If you've made changes to theme files, plugins, or the Dockerfile and they're not appearing:

```bash
# Option 1: Full rebuild (recommended for theme/plugin changes)
docker-compose down
docker-compose build --no-cache
docker-compose up

# Option 2: Quick rebuild (for minor changes)
docker-compose down
docker-compose up --build

# Option 3: Complete reset (if you want fresh WordPress install)
docker-compose down -v  # This removes volumes/database
docker-compose build --no-cache
docker-compose up
```

**When to rebuild:**

- ✅ After modifying theme files (PHP, CSS, JS)
- ✅ After adding/removing plugins  
- ✅ After changing `Dockerfile` or `custom-entrypoint.sh`
- ✅ After modifying `docker-compose.yml`
- ✅ When you get "function not found" errors
- ❌ Not needed for content changes made through WordPress admin

---

## 🐛 Troubleshooting

### "Call to undefined function" errors

This usually means the Docker image needs to be rebuilt:

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up
```

### Site not loading in Chrome but works with curl

Use `<http://127.0.0.1:8080>` instead of `<http://localhost:8080>`

### Changes not appearing

Make sure to rebuild Docker after file changes (see section above)
