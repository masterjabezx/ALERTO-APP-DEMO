# ALERTO APP — Migration Execution History

## 1. Migration Run Log

| Migration File | Batch | Execution Status | Tables Created |
|---|:---:|:---:|---|
| `0001_01_01_000000_create_users_table.php` | 1 | Ran (44.82ms) | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table.php` | 1 | Ran (16.22ms) | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table.php` | 1 | Ran (19.39ms) | `jobs`, `job_batches`, `failed_jobs` |
| `2026_11_25_000001_create_geographic_tables.php` | 1 | Ran (69.17ms) | `municipalities`, `barangays`, `barangay_boundaries` |
| `2026_11_25_000002_create_rbac_and_auth_tables.php` | 1 | Ran (160.08ms) | `roles`, `permissions`, `role_user`, `permission_role`, `permission_user`, `user_presences`, `audit_logs` |
| `2026_11_25_000003_create_office_tables.php` | 1 | Ran (118.26ms) | `offices`, `office_users`, `office_locations`, `office_responsibilities`, `emergency_hotlines` |
| `2026_11_25_000004_create_citizen_tables.php` | 1 | Ran (98.05ms) | `citizen_profiles`, `citizen_addresses`, `contact_verifications` |
| `2026_11_25_000005_create_personnel_tables.php` | 1 | Ran (181.70ms) | `personnel_profiles`, `personnel_availabilities`, `availability_sessions`, `response_teams`, `team_memberships`, `personnel_jurisdictions`, `personnel_locations` |
| `2026_11_25_000006_create_media_tables.php` | 1 | Ran (78.46ms) | `media_attachments`, `media_geo_metadata` |
| `2026_11_25_000007_create_incident_tables.php` | 1 | Ran (341.34ms) | `incident_categories`, `incidents`, `incident_locations`, `incident_reviews`, `incident_assignments`, `incident_routing_rules`, `incident_status_histories`, `response_logs`, `incident_evidence_records` |
| `2026_11_25_000008_create_internal_messenger_tables.php` | 1 | Ran (162.80ms) | `conversations`, `conversation_participants`, `messages`, `message_attachments`, `message_read_receipts`, `message_notifications` |
| `2026_11_25_000009_create_facebook_messenger_tables.php` | 1 | Ran (140.12ms) | `fb_integration_settings`, `fb_conversations`, `fb_messages`, `fb_attachments`, `fb_webhook_events`, `incident_intake_records` |
| `2026_11_25_000010_create_notification_and_system_tables.php` | 1 | Ran (126.67ms) | `notifications`, `notification_deliveries`, `user_notification_settings`, `system_settings`, `activity_logs`, `system_backup_records` |

Total execution duration: **1,397.08 ms** (Zero failures).
Command used: `php artisan migrate` (Strictly avoided `migrate:fresh` and `db:wipe`).

