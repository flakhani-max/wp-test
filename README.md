# WordPress Hello World - Docker + Google Cloud Run

A basic WordPress application with a custom "Hello World" theme, containerized with Docker and deployable to Google Cloud Run via GitHub Actions.

## Features

- 🐳 Docker containerized WordPress
- 🎨 Custom "Hello World" theme
- ☁️ Google Cloud Run deployment ready
- 🚀 Automated deployment via GitHub Actions
- 🔐 Cloud SQL compatible
- 📦 WP-CLI pre-installed
- 🔄 **Auto URL Detection** - No need to know the Cloud Run URL in advance!

## Local Development

### Prerequisites

- Docker and Docker Compose installed
- Git

### Setup

1. **Clone or navigate to this directory:**
   ```bash
   cd wp-test
   ```

2. **Start the application:**
   ```bash
   docker-compose up --build
   ```

3. **Access WordPress:**
   - Frontend: http://localhost:8080
   - Admin: http://localhost:8080/wp-admin
   - Default credentials:
     - Username: `admin`
     - Password: `admin123`

4. **Stop the application:**
   ```bash
   docker-compose down
   ```

5. **Reset everything (including database):**
   ```bash
   docker-compose down -v
   ```

## Google Cloud Run Deployment

### Prerequisites

1. **Google Cloud Project** with billing enabled
2. **Cloud SQL instance** (MySQL/MariaDB) set up
3. **GitHub repository** with this code
4. **Required APIs enabled:**
   ```bash
   gcloud services enable run.googleapis.com
   gcloud services enable cloudbuild.googleapis.com
   gcloud services enable sqladmin.googleapis.com
   gcloud services enable artifactregistry.googleapis.com
   ```

5. **Artifact Registry repository** (if not already created):
   ```bash
   gcloud artifacts repositories create wordpress_test \
     --repository-format=docker \
     --location=northamerica-northeast1 \
     --description="WordPress Docker images"
   ```

### Setup Cloud SQL

1. **Create a Cloud SQL instance:**
   ```bash
   gcloud sql instances create wordpress-db \
     --database-version=MYSQL_8_0 \
     --tier=db-f1-micro \
     --region=northamerica-northeast1
   ```

2. **Create database:**
   ```bash
   gcloud sql databases create wordpress --instance=wordpress-db
   ```

3. **Create database user:**
   ```bash
   gcloud sql users create wordpress \
     --instance=wordpress-db \
     --password=YOUR_SECURE_PASSWORD
   ```

4. **Get connection name:**
   ```bash
   gcloud sql instances describe wordpress-db --format="value(connectionName)"
   ```
   Save this for later (format: `project-id:region:instance-name`)

### Setup Service Account

1. **Create service account for GitHub Actions:**
   ```bash
   gcloud iam service-accounts create github-actions \
     --display-name="GitHub Actions Deployer"
   ```

2. **Grant necessary permissions:**
   ```bash
   PROJECT_ID=$(gcloud config get-value project)
   
   gcloud projects add-iam-policy-binding $PROJECT_ID \
     --member="serviceAccount:github-actions@${PROJECT_ID}.iam.gserviceaccount.com" \
     --role="roles/run.admin"
   
   gcloud projects add-iam-policy-binding $PROJECT_ID \
     --member="serviceAccount:github-actions@${PROJECT_ID}.iam.gserviceaccount.com" \
     --role="roles/artifactregistry.writer"
   
   gcloud projects add-iam-policy-binding $PROJECT_ID \
     --member="serviceAccount:github-actions@${PROJECT_ID}.iam.gserviceaccount.com" \
     --role="roles/iam.serviceAccountUser"
   ```

3. **Create and download key:**
   ```bash
   gcloud iam service-accounts keys create github-actions-key.json \
     --iam-account=github-actions@${PROJECT_ID}.iam.gserviceaccount.com
   ```

### Configure GitHub Secrets

Go to your GitHub repository → Settings → Secrets and variables → Actions, and add these **required** secrets:

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `GCP_PROJECT_ID` | Your GCP project ID | `my-project-123456` |
| `GCP_SA_KEY` | Contents of `github-actions-key.json` | `{ "type": "service_account", ... }` |
| `WORDPRESS_DB_HOST` | Cloud SQL connection (use `localhost` for socket) | `localhost` |
| `WORDPRESS_DB_USER` | Database username | `wordpress` |
| `WORDPRESS_DB_PASSWORD` | Database password | `your_secure_password` |
| `WORDPRESS_DB_NAME` | Database name | `wordpress` |
| `CLOUD_SQL_CONNECTION_NAME` | Full connection name | `project:region:instance` |
| `WP_ADMIN_USER` | Admin username | `admin` |
| `WP_ADMIN_PASS` | Admin password | `secure_admin_password` |
| `WP_ADMIN_EMAIL` | Admin email | `admin@yourdomain.com` |

**Optional secrets** (will use defaults or auto-detect if not provided):

| Secret Name | Description | Default |
|-------------|-------------|---------|
| `WP_URL` | Your Cloud Run URL (auto-detected if not set) | Auto-detected from headers |
| `WP_TITLE` | Site title | `Hello World WordPress` |

> **✨ Auto URL Detection:** You don't need to know the Cloud Run URL in advance! The app automatically detects its URL from Cloud Run headers. You can deploy first, then optionally set `WP_URL` if you want to use a custom domain.

### Deploy

1. **Push to main branch:**
   ```bash
   git add .
   git commit -m "Initial WordPress setup"
   git push origin main
   ```

2. **Monitor deployment:**
   - Go to GitHub Actions tab in your repository
   - Watch the deployment progress
   - Once complete, the service URL will be shown

3. **Access your site:**
   - The URL will be: `https://wordpress-hello-world-[hash]-nn.a.run.app`
   - You can find it in the GitHub Actions output or:
     ```bash
     gcloud run services describe wordpress-hello-world \
       --region northamerica-northeast1 \
       --format="value(status.url)"
     ```

### Manual Deployment (Optional)

If you prefer to deploy manually:

```bash
# Set variables
PROJECT_ID="your-project-id"
REGION="northamerica-northeast1"
SERVICE_NAME="wordpress-hello-world"
ARTIFACT_REGISTRY_REPO="wordpress_test"

# Configure Docker for Artifact Registry
gcloud auth configure-docker northamerica-northeast1-docker.pkg.dev

# Build image
IMAGE_BASE=northamerica-northeast1-docker.pkg.dev/$PROJECT_ID/$ARTIFACT_REGISTRY_REPO/$SERVICE_NAME
docker build -t $IMAGE_BASE:latest .

# Push to Artifact Registry
docker push $IMAGE_BASE:latest

# Deploy to Cloud Run
gcloud run deploy $SERVICE_NAME \
  --image $IMAGE_BASE:latest \
  --region $REGION \
  --platform managed \
  --allow-unauthenticated \
  --set-env-vars "WORDPRESS_DB_HOST=localhost,WORDPRESS_DB_USER=wordpress,WORDPRESS_DB_PASSWORD=your_password,WORDPRESS_DB_NAME=wordpress,WP_TITLE=Hello World,WP_ADMIN_USER=admin,WP_ADMIN_PASS=admin_password,WP_ADMIN_EMAIL=admin@example.com" \
  --add-cloudsql-instances your-project:northamerica-northeast1:instance \
  --memory 1Gi \
  --cpu 1
```

## Project Structure

```
wp-test/
├── .github/
│   └── workflows/
│       └── deploy.yml          # GitHub Actions workflow
├── wp-content/
│   ├── mu-plugins/
│   │   └── cloud-run-url-fix.php  # Auto URL detection plugin
│   └── themes/
│       └── hello-world/        # Custom theme
│           ├── style.css       # Theme styles
│           ├── index.php       # Main template
│           └── functions.php   # Theme functions
├── Dockerfile                  # Docker image definition
├── docker-compose.yml          # Local development setup
├── custom-entrypoint.sh        # Container startup script
├── .gitignore                  # Git ignore rules
├── .dockerignore              # Docker ignore rules
└── README.md                   # This file
```

## Customization

### Modify the Theme

Edit files in `wp-content/themes/hello-world/`:
- `index.php` - Change the HTML structure
- `style.css` - Modify the styling
- `functions.php` - Add WordPress functionality

### URL Auto-Detection

The app includes a must-use plugin (`wp-content/mu-plugins/cloud-run-url-fix.php`) that:
- Automatically detects the Cloud Run URL from HTTP headers
- Fixes WordPress redirects and URLs dynamically
- Works without needing to know the URL in advance
- Supports custom domains when configured

### Environment Variables

Available environment variables:

| Variable | Description | Default |
|----------|-------------|---------|
| `PORT` | Server port | `8080` |
| `WP_URL` | WordPress site URL (optional) | Auto-detected from headers |
| `WP_TITLE` | Site title | `Hello World WordPress` |
| `WP_ADMIN_USER` | Admin username | `admin` |
| `WP_ADMIN_PASS` | Admin password | `admin123` |
| `WP_ADMIN_EMAIL` | Admin email | `admin@example.com` |
| `WORDPRESS_DB_HOST` | Database host | Required |
| `WORDPRESS_DB_USER` | Database user | Required |
| `WORDPRESS_DB_PASSWORD` | Database password | Required |
| `WORDPRESS_DB_NAME` | Database name | Required |
| `CLOUD_SQL_CONNECTION_NAME` | Cloud SQL connection | Required for Cloud Run |

## Troubleshooting

### Local Development Issues

**Database connection errors:**
```bash
docker-compose down -v  # Remove volumes
docker-compose up --build  # Rebuild
```

**Port already in use:**
```bash
# Change port in docker-compose.yml
ports:
  - "8081:8080"  # Use 8081 instead
```

### Cloud Run Issues

**Check logs:**
```bash
gcloud run services logs read wordpress-hello-world \
  --region northamerica-northeast1 \
  --limit 50
```

**Database connection fails:**
- Verify Cloud SQL connection name is correct
- Ensure service account has Cloud SQL Client role
- Check database credentials in secrets

**Site URL incorrect:**
- Update `WP_URL` secret to match your Cloud Run URL
- Redeploy the service

## Security Notes

⚠️ **Important for Production:**

1. **Change default passwords** - Don't use `admin123` in production
2. **Use strong database passwords** - Generate secure passwords
3. **Enable HTTPS** - Cloud Run provides HTTPS by default
4. **Secure secrets** - Never commit credentials to Git
5. **Regular updates** - Keep WordPress and dependencies updated
6. **Backup database** - Set up automated Cloud SQL backups

## Cost Considerations

- **Cloud Run:** Pay per use (generous free tier)
- **Cloud SQL:** Charged hourly (can use `db-f1-micro` for minimal cost)
- **Artifact Registry:** Storage costs (minimal for single image, 0.5GB free)
- **Bandwidth:** Egress charges apply

## Support

For issues or questions:
1. Check GitHub Actions logs for deployment errors
2. Review Cloud Run logs for runtime issues
3. Verify all secrets are correctly configured

## License

This is a basic template for educational purposes. WordPress itself is licensed under GPL v2 or later.

