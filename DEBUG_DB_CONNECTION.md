# Database Connection Debugging Guide

## Common Issues and Solutions

### 1. Check Your GitHub Secrets

Make sure these secrets are set correctly in GitHub:

```
WORDPRESS_DB_HOST: localhost
WORDPRESS_DB_USER: wordpress (or your actual DB username)
WORDPRESS_DB_PASSWORD: (your actual DB password)
WORDPRESS_DB_NAME: wordpress (or your actual DB name)
CLOUD_SQL_CONNECTION_NAME: dashboard-254616:northamerica-northeast1:INSTANCE_NAME
```

**CRITICAL:** The `CLOUD_SQL_CONNECTION_NAME` format must be exactly:
```
PROJECT_ID:REGION:INSTANCE_NAME
```

### 2. Verify Cloud SQL Instance

```bash
# List your Cloud SQL instances
gcloud sql instances list

# Check if your instance is in the correct region
gcloud sql instances describe YOUR_INSTANCE_NAME --format="value(region)"

# Get the connection name
gcloud sql instances describe YOUR_INSTANCE_NAME --format="value(connectionName)"
```

### 3. Check Database and User Exist

```bash
# Connect to your Cloud SQL instance
gcloud sql connect YOUR_INSTANCE_NAME --user=root

# Then in MySQL prompt:
SHOW DATABASES;
SELECT user, host FROM mysql.user;
```

Make sure:
- The database exists (e.g., `wordpress`)
- The user exists with proper privileges
- The user can connect from `%` or `cloudsqlproxy~%`

### 4. Verify Cloud Run Service Account Has Cloud SQL Client Role

```bash
PROJECT_ID=$(gcloud config get-value project)
SA_EMAIL="github-actions-deploy@${PROJECT_ID}.iam.gserviceaccount.com"

# Check if role is present
gcloud projects get-iam-policy $PROJECT_ID \
  --flatten="bindings[].members" \
  --filter="bindings.members:serviceAccount:${SA_EMAIL}" \
  --format="table(bindings.role)" | grep cloudsql.client
```

If not present:
```bash
gcloud projects add-iam-policy-binding $PROJECT_ID \
  --member="serviceAccount:${SA_EMAIL}" \
  --role="roles/cloudsql.client"
```

### 5. Check Cloud Run Logs

```bash
# Get recent logs from your Cloud Run service
gcloud run services logs read wordpress-hello-world \
  --region northamerica-northeast1 \
  --limit 50

# Look for these indicators:
# - "Configuring PHP to use Cloud SQL socket"
# - Database connection errors
# - Socket path information
```

### 6. Common Mistakes

❌ **Wrong:**
```
CLOUD_SQL_CONNECTION_NAME: my-instance
WORDPRESS_DB_HOST: my-instance
```

✅ **Correct:**
```
CLOUD_SQL_CONNECTION_NAME: dashboard-254616:northamerica-northeast1:my-instance
WORDPRESS_DB_HOST: localhost
```

### 7. Test Database Connection from Cloud Shell

```bash
# Create a test Cloud Run service
gcloud run deploy test-db-connection \
  --image gcr.io/cloudsql-docker/gce-proxy:latest \
  --add-cloudsql-instances dashboard-254616:northamerica-northeast1:YOUR_INSTANCE \
  --region northamerica-northeast1 \
  --command "/bin/sh" \
  --args "-c,echo Connection test"
```

### 8. Quick Fix Commands

```bash
# If database doesn't exist, create it:
gcloud sql databases create wordpress --instance=YOUR_INSTANCE_NAME

# If user doesn't exist, create it:
gcloud sql users create wordpress \
  --instance=YOUR_INSTANCE_NAME \
  --password=YOUR_SECURE_PASSWORD

# Grant the user privileges (connect with root):
gcloud sql connect YOUR_INSTANCE_NAME --user=root
# Then:
# GRANT ALL PRIVILEGES ON wordpress.* TO 'wordpress'@'%';
# FLUSH PRIVILEGES;
```

## Expected Log Output (Success)

When working correctly, you should see in Cloud Run logs:

```
Configuring PHP to use Cloud SQL socket: /cloudsql/dashboard-254616:northamerica-northeast1:instance-name
✓ Database connection successful!
Installing WordPress...
✓ WordPress installed successfully!
✓ Hello World theme activated!
WordPress is ready!
```

## Getting Your Current Cloud SQL Setup

Run this to see your current setup:

```bash
PROJECT_ID=$(gcloud config get-value project)
echo "Project: $PROJECT_ID"
echo ""
echo "Cloud SQL Instances:"
gcloud sql instances list --format="table(name,region,connectionName)"
echo ""
echo "Databases:"
for instance in $(gcloud sql instances list --format="value(name)"); do
  echo "Instance: $instance"
  gcloud sql databases list --instance=$instance --format="table(name)"
  echo ""
done
```

