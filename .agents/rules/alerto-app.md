---
description: Master development rules and architectural constraints for ALERTO APP
globs: ["**/*"]
alwaysApply: true
---

# ALERTO APP Master Rules & Constraints

Refer to the primary guidelines in `.ai/guidelines/alerto-app.md` for complete specifications.

## Critical Architectural Constraints:
1. **Four Account Types Only:** `Citizen`, `Personnel`, `Office`, and `Admin`.
2. **NO Super Admin:** `Admin` is the highest system authority. Super Admin must never be created or referenced.
3. **Official Directory:** All operations must occur within `C:\Users\ACER NITRO\Desktop\JABEZ DEV FILES\WEB\ALERTO APP`. Never create nested `alerto-app` subfolders.
4. **Database Stack:** MariaDB / MySQL on XAMPP (`127.0.0.1:3306`), database name: `alerto_app`, encoding: `utf8mb4`.
5. **UI Standard:**
   - Citizen: Clean, simple, minimalist, mobile-first.
   - Personnel: Clean operational interface, mobile-first.
   - Office: Professional, organized, simple.
   - Admin: Premium Emergency Operations Center dashboard with GSAP animations, live visualization, interactive maps.
6. **Code Quality:** Service classes for business logic, Form Requests for validation, Policies/Gates for authorization, DB transactions for mutations.

