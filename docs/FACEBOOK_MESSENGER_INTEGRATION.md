# ALERTO APP Facebook Messenger Integration

## 1. Executive Summary & Operational Mission
The **ALERTO APP Facebook Messenger Integration** provides an automated, resilient, and secure intake bridge between the Facebook Messenger platform and the Hinoba-an Local Disaster Risk Reduction and Management Office (LDRRMO) Emergency Operations Center (EOC).

Given that Meta Messenger is one of the most widely used messaging channels in the Philippines, this integration allows Hinoba-an citizens to report emergencies, hazards, and flood events directly from Facebook without needing an account on the ALERTO web portal.

### Key Operating Directives
- **Zero Auto-Dispatch Rule:** Incoming reports submitted via Facebook Messenger **ALWAYS** enter `pending_review` status. Automated dispatching or direct unit assignment without human dispatcher verification is strictly prohibited.
- **Strict Role Boundaries:** Facebook Messenger management is exclusively accessible to authorized `admin` personnel. Citizen, personnel, and agency office accounts have zero access to the Facebook inbox or webhook controls.
- **Defense-in-Depth Security:** Meta App Secrets and Page Access Tokens are securely held in environment variables and encrypted database settings. They are never exposed in browser HTML or frontend JavaScript.

---

## 2. System Architecture & Component Overview

```
 [Citizen on Facebook Messenger]
               │ (HTTPS POST Webhook)
               ▼
 [Laravel Route: POST /webhooks/facebook/messenger]
               │ (Signature Verification: X-Hub-Signature-256)
               ▼
 [ProcessFacebookMessengerWebhookJob] (Laravel Queue)
               │
               ▼
 [FacebookMessengerService]
   ├── Deduplication Cache Check
   ├── Contact Phone Extractor (09XXXXXXXXX)
   ├── Location & GPS Parser
   ├── Private Disk Media Ingest
   └── Automated Reporting Assistant State Machine
               │
               ├── (Meta Graph API: Quick Replies & Guidance)
               ▼
 [Admin Facebook Messenger Inbox: /admin/communications/facebook-messenger]
   ├── 3-Panel Real-Time Desk
   ├── Reverb WebSocket (private-admin.facebook-messenger)
   ├── Conversational Reply Dispatch
   └── "Convert to Formal Incident" Modal
               │
               ▼
 [Formal ALERTO Incident (Status: pending_review)]
   ├── Reference: INC-YYYY-XXXX-FB
   ├── Source: facebook_messenger
   └── Linked Evidence & Forensic Audit Trail
```

---

## 3. Core Database Entities

| Table | Model | Role / Description |
| :--- | :--- | :--- |
| `fb_integration_settings` | `FbIntegrationSetting` | Stores Page ID, App ID, Page Access Token, App Secret, Verify Token, greeting templates, and webhook health statistics. |
| `fb_conversations` | `FbConversation` | Tracks unique PSIDs (Page-Scoped User IDs), citizen names, unread badges, last interaction timestamps, and intake link status. |
| `fb_messages` | `FbMessage` | Complete bidirectional message audit log (`incoming` / `outgoing`), mid deduplication, delivery statuses (`sent`, `delivered`, `failed`), and error tracking. |
| `incident_intake_records` | `IncidentIntakeRecord` | Intermediate intake hopper preserving incomplete or pending reports before conversion into formal incidents. |

---

## 4. Admin Management Interface

The Admin interface consists of two dedicated modules:

1. **Facebook Messenger Inbox (`/admin/communications/facebook-messenger`)**:
   - **Panel 1 (Left):** Real-time conversations list with unread counter badges, search filter, and active intake indicators.
   - **Panel 2 (Center):** Interactive chat stream displaying citizen and operator bubbles, attachment previews, status indicators, and quick template reply bar.
   - **Panel 3 (Right):** Structured Incident Intake details pane displaying parsed category, extracted mobile number, GPS coordinates, location details, and a one-click "Convert to Incident" action.
2. **Settings & Health Dashboard (`/admin/communications/facebook-messenger/settings`)**:
   - Integration status badge, webhook verification status, Meta Developer webhook callback URL, verify token display, masked credentials management, and real-time intake throughput metrics.

