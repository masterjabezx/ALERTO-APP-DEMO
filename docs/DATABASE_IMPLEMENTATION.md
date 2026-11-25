# ALERTO APP — Database Implementation Report

## 1. Executive Summary
- **Database Engine:** MariaDB 10.11.19-MariaDB (InnoDB)
- **Host / Port:** `127.0.0.1:3306`
- **Database Name:** `alerto_app`
- **Database User:** `alerto_user`
- **Character Set / Collation:** `utf8mb4` / `utf8mb4_unicode_ci`
- **Total Tables Implemented:** 63 (56 operational domain tables + 7 Laravel system tables)
- **Status:** All migrations executed in Batch 1. All automated tests passing. Zero data loss.

---

## 2. Table Implementation Inventory

| # | Table Name | Domain Group | Storage Engine | Record Count (Seeded) |
|---|---|---|---|---|
| 1 | `users` | Group A: Auth | InnoDB | 5 |
| 2 | `roles` | Group A: Auth | InnoDB | 4 |
| 3 | `permissions` | Group A: Auth | InnoDB | 19 |
| 4 | `role_user` | Group A: Auth | InnoDB | 5 |
| 5 | `permission_role` | Group A: Auth | InnoDB | 38 |
| 6 | `permission_user` | Group A: Auth | InnoDB | 0 |
| 7 | `sessions` | Group A: Auth | InnoDB | 0 |
| 8 | `user_presences` | Group A: Auth | InnoDB | 5 |
| 9 | `audit_logs` | Group A: Auth | InnoDB | 0 |
| 10 | `citizen_profiles` | Group B: Citizen | InnoDB | 1 |
| 11 | `citizen_addresses` | Group B: Citizen | InnoDB | 1 |
| 12 | `contact_verifications` | Group B: Citizen | InnoDB | 0 |
| 13 | `personnel_profiles` | Group C: Personnel | InnoDB | 1 |
| 14 | `personnel_availabilities` | Group C: Personnel | InnoDB | 1 |
| 15 | `availability_sessions` | Group C: Personnel | InnoDB | 0 |
| 16 | `response_teams` | Group C: Personnel | InnoDB | 8 |
| 17 | `team_memberships` | Group C: Personnel | InnoDB | 0 |
| 18 | `personnel_jurisdictions` | Group C: Personnel | InnoDB | 0 |
| 19 | `personnel_locations` | Group C: Personnel | InnoDB | 0 |
| 20 | `offices` | Group D: Office | InnoDB | 4 |
| 21 | `office_users` | Group D: Office | InnoDB | 2 |
| 22 | `office_locations` | Group D: Office | InnoDB | 4 |
| 23 | `office_responsibilities` | Group D: Office | InnoDB | 5 |
| 24 | `emergency_hotlines` | Group D: Office | InnoDB | 4 |
| 25 | `municipalities` | Group E: Geographic | InnoDB | 1 |
| 26 | `barangays` | Group E: Geographic | InnoDB | 6 |
| 27 | `barangay_boundaries` | Group E: Geographic | InnoDB | 6 |
| 28 | `incident_categories` | Group F: Incidents | InnoDB | 5 |
| 29 | `incidents` | Group F: Incidents | InnoDB | 0 |
| 30 | `incident_locations` | Group F: Incidents | InnoDB | 0 |
| 31 | `incident_reviews` | Group F: Incidents | InnoDB | 0 |
| 32 | `incident_assignments` | Group F: Incidents | InnoDB | 0 |
| 33 | `incident_routing_rules` | Group F: Incidents | InnoDB | 0 |
| 34 | `incident_status_histories` | Group F: Incidents | InnoDB | 0 |
| 35 | `response_logs` | Group F: Incidents | InnoDB | 0 |
| 36 | `incident_evidence_records` | Group F: Incidents | InnoDB | 0 |
| 37 | `media_attachments` | Group G: Media | InnoDB | 0 |
| 38 | `media_geo_metadata` | Group G: Media | InnoDB | 0 |
| 39 | `conversations` | Group H: Messenger | InnoDB | 0 |
| 40 | `conversation_participants` | Group H: Messenger | InnoDB | 0 |
| 41 | `messages` | Group H: Messenger | InnoDB | 0 |
| 42 | `message_attachments` | Group H: Messenger | InnoDB | 0 |
| 43 | `message_read_receipts` | Group H: Messenger | InnoDB | 0 |
| 44 | `message_notifications` | Group H: Messenger | InnoDB | 0 |
| 45 | `fb_integration_settings` | Group I: FB Messenger | InnoDB | 0 |
| 46 | `fb_conversations` | Group I: FB Messenger | InnoDB | 0 |
| 47 | `fb_messages` | Group I: FB Messenger | InnoDB | 0 |
| 48 | `fb_attachments` | Group I: FB Messenger | InnoDB | 0 |
| 49 | `fb_webhook_events` | Group I: FB Messenger | InnoDB | 0 |
| 50 | `incident_intake_records` | Group I: FB Messenger | InnoDB | 0 |
| 51 | `notifications` | Group J: Notifications | InnoDB | 0 |
| 52 | `notification_deliveries` | Group J: Notifications | InnoDB | 0 |
| 53 | `user_notification_settings` | Group J: Notifications | InnoDB | 5 |
| 54 | `system_settings` | Group K: System | InnoDB | 0 |
| 55 | `activity_logs` | Group K: System | InnoDB | 0 |
| 56 | `system_backup_records` | Group K: System | InnoDB | 0 |
| 57-63 | Laravel System (`migrations`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`) | System | InnoDB | Varies |

