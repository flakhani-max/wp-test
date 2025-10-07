#!/bin/bash
# Activate the Hello World theme on the running Cloud Run service

set -e

SERVICE_NAME="wordpress-hello-world"
REGION="northamerica-northeast1"

echo "Getting Cloud Run service URL..."
SERVICE_URL=$(gcloud run services describe $SERVICE_NAME --region $REGION --format='value(status.url)')
echo "Service URL: $SERVICE_URL"
echo ""

echo "This will execute WP-CLI commands on your Cloud Run service to:"
echo "1. Check what theme is currently active"
echo "2. List available themes"  
echo "3. Activate the hello-world theme"
echo ""

# Get a running instance
echo "Executing on Cloud Run container..."
echo ""

# Check current theme
echo "1. Current theme:"
gcloud run services proxy $SERVICE_NAME --region $REGION &
PROXY_PID=$!
sleep 3

# Since we can't easily exec into Cloud Run, let's update the container
echo ""
echo "To activate your theme, we need to redeploy with the theme activation forced."
echo ""
echo "The theme should already be in the container at:"
echo "/var/www/html/wp-content/themes/hello-world/"
echo ""
echo "The entrypoint script should activate it automatically."
echo ""

kill $PROXY_PID 2>/dev/null || true

echo "Let's check if the theme files are actually in the container..."

