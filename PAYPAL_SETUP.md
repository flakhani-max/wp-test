# PayPal Integration Setup Guide

This guide explains how to set up PayPal for donations on your WordPress site.

## What We've Integrated

- **PayPal Smart Payment Buttons** on the donation page
- Support for **one-time donations** (monthly PayPal donations require PayPal subscriptions API)
- Seamless integration alongside Stripe, Apple Pay, and Google Pay
- Backend donation tracking

## Setup Steps

### 1. Create a PayPal Business Account

1. Go to https://www.paypal.com/business
2. Sign up for a **PayPal Business Account** (free)
3. Complete your business profile
4. Verify your email and bank account

### 2. Get Your PayPal API Credentials

1. Log in to https://developer.paypal.com/
2. Go to **Dashboard** → **My Apps & Credentials**
3. Under **REST API apps**, click **"Create App"**
4. Give your app a name (e.g., "CTF Donations")
5. You'll see two sets of credentials:
   - **Sandbox** (for testing)
   - **Live** (for production)

### 3. Add Credentials to Google Secret Manager

#### For Production (Live):
```bash
# Add PayPal Client ID
echo -n "YOUR_LIVE_CLIENT_ID" | gcloud secrets create PAYPAL_CLIENT_ID \
  --data-file=- \
  --project=dashboard-254616

# Add PayPal Secret (optional, for server-side verification)
echo -n "YOUR_LIVE_SECRET" | gcloud secrets create PAYPAL_SECRET \
  --data-file=- \
  --project=dashboard-254616
```

#### For Local Development:
Add to your `.env` file:
```
PAYPAL_CLIENT_ID=your_sandbox_client_id_here
PAYPAL_SECRET=your_sandbox_secret_here
```

### 4. Update GitHub Actions Workflow

Edit `.github/workflows/deploy.yml` to fetch PayPal secrets:

```yaml
- name: Fetch secrets from Secret Manager
  id: secrets
  run: |
    # ... existing secrets ...
    echo "paypal_client_id=$(gcloud secrets versions access latest --secret=PAYPAL_CLIENT_ID)" >> $GITHUB_OUTPUT
```

Then update the Cloud Run deploy command:
```yaml
--set-env-vars="PAYPAL_CLIENT_ID=${{ steps.secrets.outputs.paypal_client_id }}"
```

### 5. Deploy Your Changes

```bash
cd /Users/firoz/Documents/CTF/wp-test
git add .
git commit -m "Add PayPal payment integration"
git push origin main
```

### 6. Test the Integration

1. Visit a donation page on your site
2. Fill in your name, email, and select an amount
3. Click the **PayPal** button
4. Complete the payment using:
   - **Sandbox test account** (for testing): See https://developer.paypal.com/tools/sandbox/accounts/
   - **Real PayPal account** (for production)

## How It Works

### Frontend (JavaScript)
1. User clicks PayPal button
2. PayPal SDK creates an order
3. User logs in to PayPal and approves payment
4. Payment is captured immediately
5. Order ID is sent to WordPress backend

### Backend (PHP)
1. Receives PayPal order ID and donor info
2. Validates the donation data
3. Optionally verifies order with PayPal API (for extra security)
4. Saves donation record to database (TODO: implement)
5. Returns success response
6. User is redirected to thank you page

## Files Modified

1. **functions.php** - Added PayPal SDK loading
2. **single-donation.php** - Added PayPal button container
3. **donation-template.js** - Added PayPal button initialization and handling
4. **donation-template.css** - Added PayPal button styling
5. **paypal-payment-handler.php** - New backend handler for PayPal donations
6. **ctf-custom-plugin.php** - Included PayPal handler

## Testing with Sandbox

### Create Sandbox Test Accounts
1. Go to https://developer.paypal.com/tools/sandbox/accounts/
2. You'll see auto-generated **Business** and **Personal** test accounts
3. Use the **Personal** account email/password to test donations
4. Check the **Business** account to see received payments

### Sandbox Credentials
- Personal Account (buyer): See in Sandbox Accounts dashboard
- Business Account (you): See in Sandbox Accounts dashboard

## Important Notes

### Currency
- Currently configured for **CAD** (Canadian dollars)
- To change: Update `currency_code: 'CAD'` in `donation-template.js`

### Monthly Donations (Subscriptions)
- Current implementation: **One-time donations only**
- For monthly donations via PayPal, you need to:
  1. Use PayPal Subscriptions API instead of Orders API
  2. Create subscription plans in PayPal dashboard
  3. Update JavaScript to use `createSubscription` instead of `createOrder`

### Security Best Practices
1. **Never expose your Secret Key** on the frontend
2. Always validate donations on the backend
3. Consider implementing server-side order verification (see `ctf_verify_paypal_order()`)
4. Enable IPN (Instant Payment Notification) in PayPal for webhook notifications

### Domain Approval
- PayPal may require domain approval for production
- Add your domain in PayPal Business Account settings

## Troubleshooting

### PayPal button not showing
1. Check browser console for errors
2. Verify `PAYPAL_CLIENT_ID` environment variable is set
3. Ensure PayPal SDK is loading (check Network tab)

### Payment failing
1. Check if using correct environment (Sandbox vs Live)
2. Verify Client ID matches environment
3. Check backend logs: `gcloud run services logs read wordpress-hello-world`

### "Currency not supported" error
- Ensure PayPal Business Account supports CAD
- Some countries have currency restrictions

## Support Resources

- **PayPal Developer Docs**: https://developer.paypal.com/docs/
- **Smart Payment Buttons**: https://developer.paypal.com/sdk/js/reference/
- **Sandbox Testing**: https://developer.paypal.com/tools/sandbox/
- **PayPal Business Support**: https://www.paypal.com/businesshelp/

## Next Steps (TODO)

1. ✅ Basic PayPal integration
2. ⬜ Implement database saving for PayPal donations
3. ⬜ Add PayPal subscription support for monthly donations
4. ⬜ Implement server-side order verification
5. ⬜ Set up PayPal IPN/Webhooks for notifications
6. ⬜ Add PayPal to admin donation reports

