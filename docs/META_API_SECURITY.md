# Meta API & Messenger Security Architecture

## 1. Security Tenets & Access Control

The ALERTO APP Facebook Messenger integration strictly enforces the following security boundaries:

1. **Strict 4-Account Architecture Compliance:**
   - **Admin Only:** Only users with `account_type === 'admin'` can access `/admin/communications/facebook-messenger/*`.
   - **Citizen Complete Prohibition:** Citizens have zero access to the Admin Facebook Desk or internal operations channels.
   - **No Super Admin:** The application operates strictly with the four defined account types (`citizen`, `personnel`, `office`, `admin`).

2. **Credential Sanitization & Masking:**
   - Page Access Tokens and Meta App Secrets are stored encrypted in the database or provided via `.env`.
   - On the settings interface, credentials are masked (e.g., `EAA...***...xyz` or `••••••••••••••••••••••••••••••••`).
   - Plaintext credentials are never rendered in HTML form values, script tags, or JSON API payloads.

---

## 2. Rate Limiting & Abuse Defense

1. **Webhook Throttle:**
   - Incoming webhook endpoints are protected by rate limiters to mitigate DoS floods.
2. **24-Hour Messaging Window Compliance:**
   - Meta imposes a strict 24-hour standard messaging window from the user's last message.
   - Outbound dispatcher messages check `last_interaction_at` and employ `MESSAGE_TAG` (`ACCOUNT_UPDATE` or `CONFIRMED_EVENT_UPDATE`) when responding outside standard intervals.
3. **Graph API Error Handling:**
   - Handled error subcodes (e.g., error code 190 invalid token, subcode 2018001 outside 24-hour window).
   - If an API call fails, the message is marked `failed` with descriptive operator error messages instead of crashing the application.

