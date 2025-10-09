# DevOps Best Practices for WordPress Development

## 🏆 The Gold Standard

### **Three Environments:**
```
┌─────────────┐
│   LOCAL     │  ← You develop here (separate DB)
└─────────────┘
      ↓
┌─────────────┐
│   STAGING   │  ← Test deployments here (optional but recommended)
└─────────────┘
      ↓
┌─────────────┐
│ PRODUCTION  │  ← Live site (never develop here!)
└─────────────┘
```

---

## ✅ **Why Method 1 (Clone Script) is Best:**

### **✅ DO:**
1. **Develop on separate local database**
   - Fast (no network lag)
   - Safe (can't break production)
   - Works offline
   
2. **Periodically clone production data**
   - Test with realistic data
   - Catch edge cases
   - Run weekly or before major features

3. **Sanitize cloned data**
   - Change production URLs to localhost
   - Anonymize user emails (privacy)
   - Don't store production secrets locally

4. **Use version control for schema changes**
   - Document database changes
   - Make changes reproducible
   - Can rollback if needed

5. **Deploy via CI/CD**
   - Automated testing
   - Consistent deployments
   - Git as source of truth

---

## ❌ **Why Method 2 (Direct to Production) is BAD:**

### **❌ DON'T:**
1. **Never develop directly against production database**
   - One mistake = site down
   - No "undo" button
   - Can't test destructive operations

2. **Why it's dangerous:**
   ```
   You: "Let me test this query..."
   Query: DELETE FROM wp_posts WHERE...
   Oops: Forgot the WHERE clause → All posts deleted!
   ```

3. **Real-world disasters:**
   - Accidentally truncated tables
   - Corrupted data during testing
   - Performance issues from dev queries
   - No audit trail of who changed what

---

## 🔄 **Recommended Workflow:**

### **Daily Development:**
```bash
# Work on local database
docker-compose up

# Make changes, test locally
# Commit code changes to git
git add .
git commit -m "Added new feature"
git push

# GitHub Actions deploys to production
```

### **Weekly Data Refresh:**
```bash
# Get fresh production data for testing
./sync-production-to-local.sh

# Now test your features against real-ish data
docker-compose up
```

### **Before Major Changes:**
```bash
# Clone production
./sync-production-to-local.sh

# Test migration/feature locally
# If it works → deploy
# If it breaks → fix without affecting production
```

---

## 🎯 **Your Current Setup (Good!):**

✅ Separate local database (MariaDB in Docker)  
✅ Separate production database (Cloud SQL)  
✅ CI/CD pipeline (GitHub Actions)  
✅ Infrastructure as Code (Dockerfile, docker-compose.yml)  
✅ Environment variables for config  
✅ Clone script for data sync  

### **What You Have:**
- **Local:** `docker-compose up` → Uses MariaDB
- **Production:** Cloud Run → Uses Cloud SQL
- **Sync:** `./sync-production-to-local.sh` → Copies prod to local

---

## 🚀 **Ideal Future State (Optional):**

### **Add a Staging Environment:**
```
Local (dev) → Staging (pre-prod) → Production (live)
```

**Staging would:**
- Mirror production exactly
- Use separate Cloud SQL database
- Test deployments before production
- Run automated tests

**Deploy flow:**
```bash
git push origin develop → Deploys to staging
# Test on staging
# If good:
git push origin main → Deploys to production
```

---

## 📚 **Key Principles:**

1. **Isolation** - Dev/staging/prod are separate
2. **Reproducibility** - Can recreate any environment
3. **Safety** - Can't accidentally break production
4. **Testability** - Test changes before deploying
5. **Auditability** - Git history shows all changes
6. **Automation** - CI/CD handles deployments

---

## 💡 **Summary:**

**Use Method 1 (Clone Script) because:**
- ✅ Follows industry best practices
- ✅ Safe development workflow
- ✅ Can test with real data
- ✅ Fast local development
- ✅ Clear separation of environments
- ✅ Can't accidentally break production

**Never use Method 2 (Direct to Prod) because:**
- ❌ One mistake = site down
- ❌ No safety net
- ❌ Against all DevOps principles
- ❌ Will get you fired at most companies 😅

---

## 🎓 **Learn More:**

- [The Twelve-Factor App](https://12factor.net/)
- [GitOps Principles](https://www.gitops.tech/)
- [Database DevOps](https://www.redgate.com/solutions/database-devops)

