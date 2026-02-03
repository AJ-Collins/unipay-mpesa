# Yiitron Framework

**The Modular, API-First Framework for Modern Enterprise Applications.**

Yiitron is a specialized extension of Yii2, designed for building scalable, containerized, and modular APIs. It replaces the standard MVC boilerplate with a robust "Four Pillar" architecture, integrated DevOps tooling, and a self-documenting developer console.

---

## 📚 Documentation
**[Read the Full Documentation on Vercel →](https://yiitron-docs.vercel.app)**

---

## 🚀 Key Features

### 1. The Core Modules (The 4 Pillars)
Instead of starting from scratch, Yiitron comes pre-wired with the essential engines every app needs:
* **IAM Module:** Complete Identity & Access Management with JWT, RBAC, and granular Permissions.
* **Admin Module:** System observability, Audit Trails, Dynamic Settings, and Logs.
* **Pulse Module:** High-performance communication engine (Email, SMS) with background queue workers.
* **Website Module:** Serves the interactive **OmniBase Dev Console** for testing APIs.

### 2. Powerful CLI Tools
* **Voyage (`yii voyage`):** A custom migration manager that keeps your database schema AND RBAC permissions in perfect sync.
* **OmniCraft:** An interactive CLI wizard for generating Modules, Controllers, and Migrations without writing boilerplate code.
* **ConsoleX:** A unified command runner for background tasks and cron jobs.

### 3. Developer Experience (DX)
* **OmniBase Console:** A built-in Swagger/OpenAPI interface available at `localhost:8080`.
* **Auto-Auth:** The console automatically handles Bearer tokens, so you never have to copy-paste headers.
* **WebSocket Tester:** Integrated tool for testing real-time socket connections.

---

## 🛠️ Quick Start

Yiitron is designed to run in **Docker**. We use a "Fat Container" strategy where Nginx, PHP-FPM, Queue Workers, and Schedulers run inside a single optimized image managed by Supervisord.

### 1. Clone & Setup
```bash
git clone [https://github.com/yiitron/api.git](https://github.com/yiitron/api.git)
cd yiitron
```

### 2. Run with Docker
```
docker-compose up -d --build

```
### 3. Access the System
```
API Console: http://localhost:8080

```
### 4. Contributing
This is an active development project. If you find bugs, security issues, or want to suggest improvements:

* Open an Issue: Discuss the bug or feature first.
* Submit a Pull Request: Fork the repo, make your fix, and submit a PR for review.
### 5. Architecture Overview
```
root/
├── config/              # Global Application Configuration
├── modules/             # The Modular Micro-Apps
│   ├── iam/             # Auth & Users
│   ├── admin/           # Settings & Logs
│   ├── pulse/           # Queues & Mail
│   └── website/         # API Docs UI
├── system/              # Core Framework Extensions
│   ├── ConsoleX/        # CLI Tools (Voyage, OmniCraft)
│   └── OmniCraft/       # Code Generators
└── Dockerfile           # Production-ready image definition   
```