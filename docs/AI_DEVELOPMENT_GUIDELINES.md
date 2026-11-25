# ALERTO APP — AI Development Guidelines & Agent Configuration

## 1. Overview
This document outlines the AI-assisted development protocols, agent integrations, and development rules established for the **ALERTO APP** project using **Laravel Boost**.

---

## 2. Laravel Boost Installation Status
- **Package:** `laravel/boost` (Version: `v2.10.4`)
- **Supporting Packages Installed:**
  - `laravel/mcp` (`v1.2.1`) — Model Context Protocol server integration
  - `laravel/roster` (`v1.0.0`) — Project & agent convention registry
  - `symfony/yaml` (`v8.1.11`)
  - `composer/semver` (`3.6.0`)
- **Installation Scope:** Development dependency (`require-dev` in `composer.json`)
- **Status:** Successfully installed, discovered by Laravel framework, and active.

---

## 3. Agent Integration Status
Laravel Boost natively detected and configured the **Antigravity** agent:

| Component | Target Location | Status |
|---|---|---|
| **AI Guidelines** | `AGENTS.md` | Configured with core Laravel Boost foundation rules & ALERTO rules ✅ |
| **Agent Skills** | `.agents/skills/*` | 5 core skills synchronized (`laravel-best-practices`, `tailwindcss-development`, `testing-best-practices`, `deploying-to-cloud`, `infer-conventions`) ✅ |
| **MCP Server Config** | `.agents/mcp_config.json` | Configured to run `php artisan boost:mcp` ✅ |
| **Custom Rules** | `.agents/rules/alerto-app.md` | Workspace rule file enforcing ALERTO architecture ✅ |
| **Master Guidelines** | `.ai/guidelines/alerto-app.md` | Comprehensive 22-section project rules specification ✅ |

---

## 4. Strict Account Structure Rules
The system recognizes **ONLY FOUR (4) ACCOUNT TYPES**:
1. **Citizen:** Public user reporting incidents; clean, mobile-first interface.
2. **Personnel:** Field emergency responders (police, medics, fire, rescue); high-contrast operational mobile UI.
3. **Office:** Departmental dispatchers & managers; organized desktop management UI.
4. **Admin:** The single highest authority in the system; Emergency Operations Center (EOC) dashboard.

> [!CAUTION]
> **NO SUPER ADMIN:** The concept of a "Super Admin" does NOT exist in ALERTO APP. Admin is the highest authority.

---

## 5. Architectural & Code Standards
- **Controller Hygiene:** Controllers must be thin; no inline business logic or database queries.
- **Service Layer:** All operations, routing algorithms, incident updates, and messaging reside in `app/Services/*`.
- **Validation:** Enforced exclusively through dedicated Form Request classes (`app/Http/Requests/*`).
- **Authorization:** Handled strictly via Laravel Policies (`app/Policies/*`).
- **Data Integrity:** All multi-table updates must use `DB::transaction()`.
- **Database:** MariaDB / MySQL on XAMPP (`127.0.0.1:3306`, DB: `alerto_app`, `utf8mb4`).
- **Testing:** Comprehensive automated testing with PHPUnit for all services, controllers, and policies.

