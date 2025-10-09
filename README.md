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
docker-compose up
```

Access at http://localhost:8080/wp-admin (admin/admin123)

---

## 🔄 Sync Production DB to Local

```bash
./sync-production-to-local.sh
```
