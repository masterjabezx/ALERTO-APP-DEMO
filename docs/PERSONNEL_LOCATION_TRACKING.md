# ALERTO APP — Duty Location Tracking & Telemetry

## Overview
Duty location tracking records and broadcasts live GPS coordinates of field operatives during active operational duty.

## Specifications
- **Endpoints**:
  - `POST /personnel/location`: Broadcasts `latitude`, `longitude`, `accuracy`, `speed`, and `heading`.
  - `POST /personnel/heartbeat`: Keep-alive presence pulse sent every 60 seconds.
- **Privacy & Permissions**:
  - Tracking is strictly active only when the operative is authenticated and has toggled Duty GPS.
  - Off-duty personnel are never tracked.
- **Data Persistence**:
  - Stored in `personnel_locations` table (`personnel_id`, `latitude`, `longitude`, `accuracy`, `speed`, `heading`, `recorded_at`).
  - Rendered on the Admin Live Operations Map and Personnel Tactical Map.

