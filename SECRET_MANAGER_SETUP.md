# Google Secret Manager Integration

This setup uses Google Secret Manager to securely manage API keys and sensitive configuration for the CTF WordPress site running on Google Cloud Run.

## Architecture

```
Cloud Run Container
├── WordPress Application
├── CTF Custom Plugin
│   ├── Fetches secrets from Secret Manager
│   └── Caches secrets in memory
└── Google Cloud SDK (for secret access)
```

## Security Benefits

1. **No hardcoded secrets** in code or environment variables
2. **Audit trail** - all secret access is logged
3. **Automatic rotation** support
4. **Fine-grained IAM** controls
5. **Encrypted at rest and in transit**

## Setup Process

### 1. Create Secrets Manually

Create the required secrets in Google Secret Manager:

```bash
# Mailchimp API Key
gcloud secrets create mailchimp-api-key --project=YOUR_PROJECT_ID
echo "your-mailchimp-api-key" | gcloud secrets versions add mailchimp-api-key --data-file=- --project=YOUR_PROJECT_ID

# Mailchimp Audience ID  
gcloud secrets create mailchimp-audience-id --project=YOUR_PROJECT_ID
echo "your-audience-id" | gcloud secrets versions add mailchimp-audience-id --data-file=- --project=YOUR_PROJECT_ID

# WordPress Database Secrets
gcloud secrets create wordpress-db-name --project=YOUR_PROJECT_ID
echo "your-db-name" | gcloud secrets versions add wordpress-db-name --data-file=- --project=YOUR_PROJECT_ID

gcloud secrets create wordpress-db-user --project=YOUR_PROJECT_ID  
echo "your-db-user" | gcloud secrets versions add wordpress-db-user --data-file=- --project=YOUR_PROJECT_ID

gcloud secrets create wordpress-db-password --project=YOUR_PROJECT_ID
echo "your-db-password" | gcloud secrets versions add wordpress-db-password --data-file=- --project=YOUR_PROJECT_ID
```

### 2. Create Service Account and Grant Permissions

```bash
# Create service account
SERVICE_ACCOUNT_NAME="ctf-wordpress-sa"
gcloud iam service-accounts create $SERVICE_ACCOUNT_NAME \
    --display-name="CTF WordPress Service Account" \
    --project=YOUR_PROJECT_ID

# Grant Secret Manager access
gcloud projects add-iam-policy-binding YOUR_PROJECT_ID \
    --member="serviceAccount:${SERVICE_ACCOUNT_NAME}@YOUR_PROJECT_ID.iam.gserviceaccount.com" \
    --role="roles/secretmanager.secretAccessor"

# Grant Cloud SQL Client access (if using Cloud SQL)
gcloud projects add-iam-policy-binding YOUR_PROJECT_ID \
    --member="serviceAccount:${SERVICE_ACCOUNT_NAME}@YOUR_PROJECT_ID.iam.gserviceaccount.com" \
    --role="roles/cloudsql.client"
```

### 3. Deploy to Cloud Run

```bash
# Build container with Secret Manager support
docker build -f Dockerfile.secretmanager -t gcr.io/YOUR_PROJECT/ctf-wordpress .

# Push to registry
docker push gcr.io/YOUR_PROJECT/ctf-wordpress

# Deploy to Cloud Run
gcloud run services replace cloudrun-service.yaml --region=us-central1
```

## How It Works

### 1. Plugin Secret Retrieval
```php
// In CTF Custom Plugin
$api_key = ctf_get_mailchimp_api_key();  // Fetches from Secret Manager
$audience_id = ctf_get_mailchimp_audience_id();
```

### 2. Fallback Strategy
```php
if (CTF_USE_SECRET_MANAGER) {
    // Try Secret Manager first
    return ctf_get_secret('mailchimp-api-key');
} else {
    // Fallback to environment variables or constants
    return getenv('CTF_MAILCHIMP_API_KEY') ?: CTF_MAILCHIMP_API_KEY;
}
```

### 3. Caching
- Secrets are cached in memory for the container lifetime
- Reduces API calls to Secret Manager
- Automatically cleared on container restart

## Environment Variables

Set these in your Cloud Run service:

```yaml
env:
- name: CTF_USE_SECRET_MANAGER
  value: "true"
- name: CTF_GCP_PROJECT_ID  
  value: "your-project-id"
```

## Local Development

For local development, you can:

1. **Use gcloud CLI authentication:**
   ```bash
   gcloud auth application-default login
   export CTF_USE_SECRET_MANAGER=true
   export CTF_GCP_PROJECT_ID=your-project-id
   ```

2. **Use environment variables (fallback):**
   ```bash
   export CTF_USE_SECRET_MANAGER=false
   export CTF_MAILCHIMP_API_KEY=your-local-key
   export CTF_MAILCHIMP_AUDIENCE_ID=your-local-audience
   ```

## Security Best Practices

1. **Principle of Least Privilege** - Service account only has access to needed secrets
2. **Audit Logging** - Enable audit logs for Secret Manager
3. **Secret Rotation** - Regularly rotate API keys
4. **Version Management** - Use Secret Manager versioning for zero-downtime updates

## Monitoring

Monitor secret access in Cloud Logging:
```
resource.type="gce_instance"
protoPayload.serviceName="secretmanager.googleapis.com"
```

## Troubleshooting

### Container can't access secrets
- Verify service account has `secretmanager.secretAccessor` role
- Check that `CTF_GCP_PROJECT_ID` is set correctly
- Ensure secrets exist in the project

### WordPress errors
- Check logs: `gcloud logs read --service=ctf-wordpress`
- Verify database connectivity if using Cloud SQL
- Check that all required secrets are created

## Cost Optimization

- Secret Manager charges per 10,000 API calls
- Caching reduces API calls significantly  
- Typical WordPress site uses <1000 calls/month = ~$0.60/month