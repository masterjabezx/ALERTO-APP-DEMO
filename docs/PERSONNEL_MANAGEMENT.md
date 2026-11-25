# ALERTO APP — Personnel Management Architecture

## Overview
The Personnel Management System manages field operatives, tactical response teams, jurisdictional coverage, real-time availability, and live telemetry across the Municipality of Hinoba-an.

## Core Capabilities
1. **Personnel Directory & Profiles** (`/admin/personnel`):
   - User account paired with `PersonnelProfile` (badge number, radio handle username, rank, specialization, department office).
   - Real-time KPI metrics: Total Registered, Available, Dispatched, On Scene, Off Duty, and Active Emergency Deployments.
2. **Registration & Credential Management** (`/admin/personnel/create`, `/{id}/edit`):
   - Secure account provisioning with encrypted passwords.
   - Deactivate/Activate toggle with automatic offline enforcement upon deactivation.
   - Secure administrative password reset.
3. **Response Units & Teams** (`/admin/personnel/teams`):
   - Specialized tactical squads (Medical, Rescue, Fire, Police, Logistics).
   - Radio call signs, assigned base stations, team leaders, and team memberships.
4. **Active Assignment Safety Protection**:
   - A responder assigned to an active, unresolved emergency run cannot transition to `off_duty` / offline until the incident is completed or reassigned.
5. **Presence vs. Availability Distinction**:
   - `user_presences`: Digital connection state (`online`, `idle`, `offline`, `connection_lost`).
   - `personnel_availabilities`: Operational duty state (`available`, `dispatched`, `on_scene`, `busy`, `off_duty`).

