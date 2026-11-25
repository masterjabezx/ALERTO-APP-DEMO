# Meta Developer & Facebook App Setup Guide for ALERTO APP

## 1. Prerequisites
To link ALERTO APP with the official Hinoba-an LDRRMO Facebook Page, you need:
1. A Meta Developer Account at [developers.facebook.com](https://developers.facebook.com/).
2. Admin access to the Hinoba-an LDRRMO Facebook Page.
3. A public HTTPS URL pointing to the ALERTO APP server (or an `ngrok` / `localtunnel` URL during local staging).

---

## 2. Meta App Configuration Steps

### Step 1: Create a Meta App
1. Go to the Meta Developer Dashboard and click **Create App**.
2. Select **Other** as the use case, then choose **Business** app type.
3. Name the app `ALERTO APP Emergency Dispatch` and assign the official municipal contact email.

### Step 2: Add Messenger Product
1. Under **Add products to your app**, locate **Messenger** and click **Set up**.
2. In the Messenger settings menu, scroll down to **Access Tokens**.
3. Click **Add or remove Pages** and select the official Hinoba-an LDRRMO Facebook Page.
4. Generate the **Page Access Token**. Keep this token secure.

### Step 3: Configure Webhooks
1. In the Messenger Settings, locate the **Webhooks** section.
2. Click **Add Callback URL**.
3. Fill in the parameters:
   - **Callback URL:** `https://your-domain.gov.ph/webhooks/facebook/messenger`
   - **Verify Token:** The string configured in ALERTO Settings (e.g., `alerto_webhook_secure_token_...`).
4. Click **Verify and Save**. ALERTO APP will instantly validate the challenge string and respond with HTTP 200 containing `hub.challenge`.
5. Under **Subscription Fields**, subscribe to:
   - `messages`
   - `messaging_postbacks`
   - `message_deliveries`
   - `message_reads`

---

## 3. Environment Configuration (`.env`)

Add the following credentials to your `.env` file on the production server:

```env
# Meta / Facebook Messenger Integration
META_APP_ID=123456789012345
META_APP_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
META_PAGE_ID=100234567890
META_PAGE_ACCESS_TOKEN=EAA...
META_VERIFY_TOKEN=alerto_webhook_secure_token_hinobaan_ldrmo
META_API_VERSION=v22.0
```

---

## 4. Permissions & App Review Checklist
For production deployment beyond developers and page admins, submit for Meta App Review with the following permissions:
- `pages_messaging` (Required for bidirectional chat and automated replies).
- `pages_show_list` (Required to link and manage the official municipal Facebook Page).
- `pages_manage_metadata` (Required for webhook subscription management).

