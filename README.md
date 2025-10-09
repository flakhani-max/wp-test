# WordPress - Cloud Run

---

## 🔑 Admin Login

**Production:** https://wordpress-hello-world-349612720555.northamerica-northeast1.run.app/wp-admin

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

```bash
# 1. Clone production data (optional, for testing with real data)
./sync-production-to-local.sh

# 2. Start local WordPress
docker-compose up

# 3. Make changes to:
#    - wp-content/themes/
#    - wp-content/plugins/
#    - Dockerfile, custom-entrypoint.sh, etc.

# 4. Test at http://localhost:8080

# 5. Deploy when ready
git add .
git commit -m "Your changes"
git push
```

**Login:** http://localhost:8080/wp-admin (admin/admin123)
