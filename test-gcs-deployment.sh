#!/bin/bash
# Quick test for GCS mount deployment

set -e

echo "=== Testing GCS Mount Deployment ==="
echo ""

# Get service name from Secret Manager
SERVICE_NAME=$(gcloud secrets versions access latest --secret="SERVICE_NAME" 2>/dev/null || echo "wordpress-hello-world")
echo "Testing service: ${SERVICE_NAME}"
echo ""

# Check if deployed
SERVICE_URL=$(gcloud run services describe ${SERVICE_NAME} --region northamerica-northeast1 --format='value(status.url)' 2>/dev/null || echo "")

if [ -z "$SERVICE_URL" ]; then
    echo "❌ Service not deployed yet"
    echo "Check GitHub Actions: https://github.com/flakhani-max/wp-test/actions"
    exit 1
fi

echo "✅ Service URL: $SERVICE_URL"
echo ""

# Check if GCS mount is configured
echo "Checking GCS mount configuration..."
if gcloud run services describe ${SERVICE_NAME} --region northamerica-northeast1 --format=yaml | grep -q "gcsfuse.run.googleapis.com"; then
    echo "✅ GCS volume mount is configured"
else
    echo "❌ GCS mount NOT found - deployment may have failed"
    exit 1
fi
echo ""

# Check service is responding
echo "Testing service response..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" $SERVICE_URL)
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "301" ] || [ "$HTTP_CODE" = "302" ]; then
    echo "✅ Service is responding (HTTP $HTTP_CODE)"
else
    echo "⚠️  Service returned HTTP $HTTP_CODE"
fi
echo ""

# Check bucket
echo "Checking GCS bucket..."
if gsutil ls gs://taxpayer-media-bucket/ &> /dev/null; then
    FILE_COUNT=$(gsutil ls gs://taxpayer-media-bucket/** 2>/dev/null | wc -l | tr -d ' ')
    echo "✅ Bucket accessible"
    echo "   Current files in bucket: $FILE_COUNT"
else
    echo "❌ Cannot access bucket"
fi
echo ""

echo "=== Next Steps ==="
echo "1. Visit: $SERVICE_URL/wp-admin"
echo "2. Go to Media → Add New"
echo "3. Upload an image"
echo "4. Verify it appears in bucket:"
echo "   gsutil ls gs://taxpayer-media-bucket/"
