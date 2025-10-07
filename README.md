# WordPress Hello World - Docker + Google Cloud Run

A basic WordPress application with a custom "Hello World" theme, containerized with Docker and deployable to Google Cloud Run via GitHub Actions.

## Features

- 🐳 Docker containerized WordPress
- 🎨 Custom "Hello World" theme
- ☁️ Google Cloud Run deployment ready
- 🚀 Automated deployment via GitHub Actions
- 🔐 Cloud SQL compatible
- 📦 WP-CLI pre-installed

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
   gcloud services enable containerregistry.googleapis.com
   ```

### Setup Cloud SQL

1. **Create a Cloud SQL instance:**
   ```bash
   gcloud sql instances create wordpress-db \
     --database-version=MYSQL_8_0 \
     --tier=db-f1-micro \
     --region=us-central1
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
     --role="roles/storage.admin"
   
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

Go to your GitHub repository → Settings → Secrets and variables → Actions, and add:

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `GCP_PROJECT_ID` | Your GCP project ID | `my-project-123456` |
| `GCP_SA_KEY` | Contents of `github-actions-key.json` | `{ "type": "service_account", ... }` |
| `WORDPRESS_DB_HOST` | Cloud SQL connection (use `localhost` for socket) | `localhost` |
| `WORDPRESS_DB_USER` | Database username | `wordpress` |
| `WORDPRESS_DB_PASSWORD` | Database password | `your_secure_password` |
| `WORDPRESS_DB_NAME` | Database name | `wordpress` |
| `CLOUD_SQL_CONNECTION_NAME` | Full connection name | `project:region:instance` |
| `WP_URL` | Your Cloud Run URL | `https://wordpress-hello-world-xxx.run.app` |
| `WP_TITLE` | Site title | `Hello World WordPress` |
| `WP_ADMIN_USER` | Admin username | `admin` |
| `WP_ADMIN_PASS` | Admin password | `secure_admin_password` |
| `WP_ADMIN_EMAIL` | Admin email | `admin@yourdomain.com` |

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
   - The URL will be: `https://wordpress-hello-world-[hash].run.app`
   - You can find it in the GitHub Actions output or:
     ```bash
     gcloud run services describe wordpress-hello-world \
       --region us-central1 \
       --format="value(status.url)"
     ```

### Manual Deployment (Optional)

If you prefer to deploy manually:

```bash
# Set variables
PROJECT_ID="your-project-id"
REGION="us-central1"
SERVICE_NAME="wordpress-hello-world"

# Build image
docker build -t gcr.io/$PROJECT_ID/$SERVICE_NAME:latest .

# Push to GCR
docker push gcr.io/$PROJECT_ID/$SERVICE_NAME:latest

# Deploy to Cloud Run
gcloud run deploy $SERVICE_NAME \
  --image gcr.io/$PROJECT_ID/$SERVICE_NAME:latest \
  --region $REGION \
  --platform managed \
  --allow-unauthenticated \
  --set-env-vars "WORDPRESS_DB_HOST=localhost,WORDPRESS_DB_USER=wordpress,WORDPRESS_DB_PASSWORD=your_password,WORDPRESS_DB_NAME=wordpress,WP_URL=https://your-service-url.run.app,WP_TITLE=Hello World,WP_ADMIN_USER=admin,WP_ADMIN_PASS=admin_password,WP_ADMIN_EMAIL=admin@example.com" \
  --add-cloudsql-instances your-project:region:instance \
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

### Environment Variables

Available environment variables:

| Variable | Description | Default |
|----------|-------------|---------|
| `PORT` | Server port | `8080` |
| `WP_URL` | WordPress site URL | `http://127.0.0.1:8080` |
| `WP_TITLE` | Site title | `Hello World WordPress` |
| `WP_ADMIN_USER` | Admin username | `admin` |
| `WP_ADMIN_PASS` | Admin password | `admin123` |
| `WP_ADMIN_EMAIL` | Admin email | `admin@example.com` |
| `WORDPRESS_DB_HOST` | Database host | - |
| `WORDPRESS_DB_USER` | Database user | - |
| `WORDPRESS_DB_PASSWORD` | Database password | - |
| `WORDPRESS_DB_NAME` | Database name | - |
| `CLOUD_SQL_CONNECTION_NAME` | Cloud SQL connection | - |

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
  --region us-central1 \
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
- **Container Registry:** Storage costs (minimal for single image)
- **Bandwidth:** Egress charges apply

## Support

For issues or questions:
1. Check GitHub Actions logs for deployment errors
2. Review Cloud Run logs for runtime issues
3. Verify all secrets are correctly configured

## License

This is a basic template for educational purposes. WordPress itself is licensed under GPL v2 or later.

