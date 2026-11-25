# ALERTO APP — Response Base Management

## Overview
Response Bases represent physical dispatch facilities, sub-stations, and staging outposts across Hinoba-an.

## Schema
- `response_bases`:
  - `office_id`: Agency operating the facility.
  - `barangay_id`: Barangay location.
  - `base_name`: Facility name (e.g., Central Rescue HQ, South Coastal Outpost).
  - `address`: Street address.
  - `latitude`, `longitude`: Spatial coordinates.
  - `contact_phone`: Direct facility dispatch hotline.
  - `is_active`: Operational readiness status.

