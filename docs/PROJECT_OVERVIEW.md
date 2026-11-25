# ALERTO APP — Project Overview

## 1. Project Information
- **Project Name:** ALERTO APP
- **Official Root Directory:** `C:\Users\ACER NITRO\Desktop\JABEZ DEV FILES\WEB\ALERTO APP`
- **Application Type:** Web-based Alert, Incident Reporting, and Community Safety Response Platform
- **Target Database:** XAMPP MariaDB / MySQL (`127.0.0.1:3306`, Database: `alerto_app`)

---

## 2. Technical Stack & Foundation

| Layer | Technology | Version / Specification |
|---|---|---|
| **Backend Framework** | Laravel | 13.43.0 |
| **Language Runtime** | PHP | 8.5.13 (XAMPP) / 8.6.0 (CLI) |
| **Dependency Manager** | Composer | 2.11.3 |
| **AI Development Layer** | Laravel Boost | ^2.10 (MCP + Guidelines + Skills) |
| **Frontend Tooling** | Vite | ^7.0.7 |
| **Styling Framework** | Tailwind CSS | ^4.0.0 (`@tailwindcss/vite`) |
| **UI Interactivity** | Alpine.js / Blade Components | Native reactive Blade architecture |
| **EOC Visual Effects** | GSAP | GreenSock Animation Platform (Admin Portal) |
| **HTTP Client** | Axios | ^1.11.0 |
| **Development Database** | MariaDB / MySQL | 10.11.19-MariaDB (XAMPP) |
| **Version Control** | Git | 2.56.0.windows.4 |

---

## 3. Directory Structure

```text
C:\Users\ACER NITRO\Desktop\JABEZ DEV FILES\WEB\ALERTO APP\
├── .agents/              # Agent skills, rules, and MCP configuration
│   ├── rules/
│   │   └── alerto-app.md # Project-level constraints for AI agents
│   ├── skills/           # Domain skills (laravel-best-practices, etc.)
│   └── mcp_config.json   # Boost MCP server connection config
├── .ai/                  # AI development guidelines
│   └── guidelines/
│       └── alerto-app.md # Master 22-section ALERTO APP specification
├── app/                  # Application core logic (Models, Providers, Http, Services)
├── bootstrap/            # Framework bootstrap & app configuration
├── config/               # Application configuration files
├── database/             # Migrations, seeders, and factories
├── docs/                 # Official project documentation and logs
│   ├── AI_DEVELOPMENT_GUIDELINES.md
│   ├── DEVELOPMENT_PROGRESS.md
│   └── PROJECT_OVERVIEW.md
├── public/               # Web server public document root
├── resources/            # Views (Blade), frontend scripts (js), and styles (css)
├── routes/               # Route definitions (web.php, console.php)
├── storage/              # File logs, framework caches, and uploads
├── tests/                # Feature and unit test suites
├── vendor/               # Composer dependencies
├── .editorconfig         # Code editor formatting rules
├── .env                  # Active environment configuration
├── .env.example          # Environment template
├── .gitattributes        # Git repository attributes
├── .gitignore            # Git exclusion rules
├── AGENTS.md             # Boost agent guidelines file
├── artisan               # Laravel command-line interface
├── boost.json            # Laravel Boost configuration
├── composer.json         # PHP dependency declarations
├── composer.lock         # Locked PHP dependency versions
├── package.json          # Node.js/NPM dependencies and build scripts
├── phpunit.xml           # Test suite configuration
├── README.md             # Standard repository readme
└── vite.config.js        # Vite bundler configuration
```

---

## 4. Key Configuration Baseline
- **Application Environment:** `local`
- **Application Key:** Generated (`APP_KEY=base64:JfwPrfl+...`)
- **Default Database Connection:** Configured for MariaDB (`DB_CONNECTION=mysql`, `DB_DATABASE=alerto_app`, `DB_USERNAME=alerto_user`).
- **AI Agent Integration:** Laravel Boost enabled with Antigravity driver, custom rule enforcement, and MCP tools.
- **Frontend Asset Pipeline:** `vite.config.js` configured with `laravel-vite-plugin` and `@tailwindcss/vite`.
