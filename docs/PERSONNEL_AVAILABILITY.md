# ALERTO APP — Personnel Availability & Presence System

## Overview
Operational availability determines whether a responder is eligible to receive emergency dispatch routing and assignment orders.

## State Models
1. **Account Presence (`user_presences`)**:
   - `online`: Device actively connected and sending heartbeats.
   - `idle`: No recent user interaction.
   - `offline`: Explicitly signed off or app closed.
   - `connection_lost`: Heartbeat gap exceeded 2 minutes (>120 seconds).
2. **Duty Status (`personnel_availabilities`)**:
   - `available`: Ready for immediate emergency deployment.
   - `dispatched`: Order received, en route to incident coordinates.
   - `on_scene`: Arrived at emergency site, actively conducting operations.
   - `busy`: Engaged in essential duties, temporarily dispatch-ineligible.
   - `off_duty`: Shift concluded.

## Active Assignment Protection Rule
Any attempt by a responder to transition to `off_duty` while actively assigned to an unresolved incident is blocked by `PersonnelManagementService::setPersonnelAvailability()`, throwing an `InvalidArgumentException`.

