# How to Create and Manage Petitions

This WordPress site uses a **custom post type** for petitions, making it easy to create and manage multiple petition campaigns.

## Creating a New Petition

1. **Login to WordPress Admin**
   - Go to `https://your-site.run.app/wp-admin`
   - Use your admin credentials

2. **Navigate to Petitions**
   - In the left sidebar, click **"Petitions"** → **"Add New"**

3. **Fill in the Petition Details**

   The petition form includes these custom fields:

   ### Basic Information
   - **Petition Title**: Main heading for your petition (e.g., "No Sales Tax on Used Cars")
   - **Petition Image URL**: URL of the hero image for the petition
   - **Introduction Text**: Opening paragraph that grabs attention
   - **Body Text**: Main petition description explaining the issue
   - **Petition Statement**: The formal petition text (e.g., "We, the undersigned...")

   ### Form Configuration
   - **Call to Action**: Text shown above the signature form
   - **Mailchimp Petition Tag**: Tag to apply in Mailchimp for this petition
   - **SMS Opt-in Text**: Text for the SMS checkbox
   
   ### Privacy Settings
   - **Privacy Notice Text**: Privacy disclaimer text
   - **Privacy Policy URL**: Link to your privacy policy
   - **Privacy Policy Link Text**: Text for the privacy link (usually "Privacy Policy")

4. **Assign Categories (Optional)**
   - On the right sidebar, you'll see **"Petition Categories"**
   - Check existing categories or click **"+ Add New Petition Category"**
   - Categories help organize petitions by topic (e.g., "Tax Reform", "Government Spending", "Healthcare")

5. **Publish the Petition**
   - Click **"Publish"** button on the right
   - Your petition is now live!

## Viewing Petitions

- **Single Petition**: `https://your-site.run.app/petitions/your-petition-slug/`
- **All Petitions**: `https://your-site.run.app/petitions/`
- **By Category**: `https://your-site.run.app/petition-category/tax-reform/`

## URL Structure

The petition URLs are automatically generated based on the title:
- Title: "No Sales Tax on Used Cars"
- URL: `https://your-site.run.app/petitions/no-sales-tax-on-used-cars/`

You can customize the slug by editing it in the WordPress editor.

## Managing Petitions

### Editing an Existing Petition
1. Go to **Petitions** → **All Petitions**
2. Click on the petition you want to edit
3. Make your changes
4. Click **"Update"**

### Deleting a Petition
1. Go to **Petitions** → **All Petitions**
2. Hover over the petition and click **"Trash"**

### Managing Categories
1. Go to **Petitions** → **Categories**
2. You can:
   - Add new categories
   - Edit existing categories
   - Add descriptions (shown on category archive pages)
   - Delete unused categories

### Viewing All Active Petitions
- Go to **Petitions** → **All Petitions** in the admin
- Or visit `https://your-site.run.app/petitions/` on the frontend
- Filter by category in admin or visit category pages on frontend

## Technical Details

### Custom Post Type
- **Post Type Slug**: `petition`
- **Archive Slug**: `petitions`
- **Supports**: Title, Editor, Thumbnail, Excerpt, Custom Fields
- **Templates Used**:
  - Single petition: `single-petition.php`
  - Petition archive: `archive-petition.php`

### Custom Taxonomy
- **Taxonomy Slug**: `petition_category`
- **Hierarchical**: Yes (like categories)
- **URL Slug**: `petition-category`
- **Shown in**: Admin column, navigation menus, REST API

### ACF Field Group
All custom fields are managed through Advanced Custom Fields (ACF):
- **Field Group**: "Petition Content"
- **Location**: Applied to `petition` post type
- **Fields**: See ACF field definitions in `acf-petition-fields.php`

### Form Submission
- Petitions are submitted via `admin-post.php` action
- Action: `petition_mailchimp_submit`
- Integrates with Mailchimp via custom plugin

## Tips

1. **Image Recommendations**:
   - Use high-quality images (1200px wide minimum)
   - Image should be relevant to the petition topic
   - Host images on a reliable CDN or media library

2. **Mailchimp Tags**:
   - Use descriptive tags (e.g., "sales-tax-petition")
   - Tags help segment your audience in Mailchimp
   - Keep tags consistent and lowercase

3. **SEO Best Practices**:
   - Write clear, compelling titles
   - Use descriptive petition statements
   - Keep URLs short and readable

## After Deployment

After you deploy changes to the theme:

1. **Flush Permalinks**:
   - Go to **Settings** → **Permalinks**
   - Click **"Save Changes"** (even without making changes)
   - This ensures the new `petitions` post type URLs work correctly

2. **Test the Petition**:
   - Visit a petition page
   - Submit a test signature
   - Verify it appears in Mailchimp

## Need Help?

If petitions aren't showing up or URLs aren't working:
1. Flush permalinks (Settings → Permalinks → Save)
2. Check that ACF Pro is activated
3. Verify the custom post type is registered (should see "Petitions" in admin sidebar)

