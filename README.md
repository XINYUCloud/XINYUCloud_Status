# XINYU Status Monitor

A full-featured website uptime monitoring system built with PHP, MySQL, and Redis. Monitor HTTP/HTTPS endpoints, SSL certificates, and DNS resolution in real time, with a modern admin dashboard and public status page.

## Features

- **Real-time Monitoring** — HTTP status codes, response time, SSL certificate expiry, and DNS resolution
- **Admin Dashboard** — Site management, incident tracking, user management, and system settings
- **Public Status Page** — Clean, responsive status page with auto-refresh and Chart.js-powered response time trends
- **Multi-channel Notifications** — Webhook, Email, Slack, DingTalk, and WeCom Work
- **Installation Wizard** — 4-step guided setup with environment checks, database configuration, and admin account creation
- **REST API** — Public status API and authenticated admin API for external integrations
- **Cron-ready** — CLI script for automated periodic checks with automatic incident creation
- **Dark Mode** — Built-in dark theme support for the public status page

## Requirements

| Component | Minimum Version |
|-----------|----------------|
| PHP       | 8.0+           |
| MySQL     | 5.7+           |
| Redis     | 6.0+ (optional, recommended) |
| Nginx / Apache | —          |

**PHP Extensions:** `pdo`, `pdo_mysql`, `curl`, `openssl`, `json`, `redis` (optional)

## Quick Start

### 1. Download & Upload

Download the latest release from the [Releases page](https://github.com/XINYUCloud/XINYUCloud_Status/releases) and extract it to the web server's document root.

### 2. Run the Installation Wizard

Visit `http(s)://your-domain.com/install/` and follow the 4-step wizard:

1. **Environment Check** — verifies PHP version and required extensions
2. **Database Setup** — configures MySQL and Redis connection, initializes the schema
3. **Admin Account** — creates the administrator user
4. **Complete** — provides links to the status page and admin panel

### 3. Configure Cron

Add a cron job to run periodic checks (recommended: every minute):

```
* * * * * php /path/to/XINYU_status/cron/check.php
```

### 4. Access

- **Public Status Page:** `http(s)://your-domain.com/`
- **Admin Panel:** `http(s)://your-domain.com/admin/`

## Project Structure

```
XINYU_status/
├── admin/                  # Admin dashboard
│   ├── api/index.php       # Admin REST API endpoints
│   ├── index.php           # Dashboard overview
│   ├── sites.php           # Site management (CRUD)
│   ├── incidents.php       # Incident tracking
│   ├── users.php           # User management
│   ├── notifications.php   # Notification settings
│   ├── settings.php        # System settings & password change
│   ├── login.php           # Admin login
│   └── sidebar.php         # Shared sidebar component
├── assets/
│   └── css/
│       ├── admin.css       # Admin panel styles
│       └── public.css      # Public status page styles
├── cron/
│   └── check.php           # CLI cron check script
├── includes/
│   ├── Auth.php            # Authentication (bcrypt, CSRF, rate limiting)
│   ├── Database.php        # PDO MySQL singleton wrapper
│   ├── functions.php       # Utility helpers
│   ├── Monitor.php         # Core monitoring engine
│   ├── RedisClient.php     # Redis singleton client
│   └── Session.php         # Redis-based session management
├── install/
│   └── index.php           # 4-step installation wizard
├── public/
│   ├── api/status.php      # Public JSON status API
│   └── index.php           # Public status page
├── .env.example            # Environment configuration template
├── .htaccess               # Apache security rules
├── config.php              # System configuration loader
├── schema.sql              # MySQL database schema (7 tables)
└── index.php               # Entry redirect
```

## Database Schema

The system uses 7 MySQL tables:

| Table              | Description                          |
|--------------------|--------------------------------------|
| `users`            | Admin user accounts (bcrypt hashed)  |
| `sites`            | Monitored websites and endpoints     |
| `checks`           | Historical check results with metrics|
| `incidents`        | Incident/downtime records            |
| `incident_updates` | Timeline updates for each incident   |
| `notifications`    | Notification channel configurations  |
| `settings`         | Key-value system settings            |

## Security

- **Password Hashing** — bcrypt with cost factor 12
- **SQL Injection Prevention** — PDO prepared statements exclusively
- **CSRF Protection** — per-session tokens on all admin forms
- **XSS Prevention** — `htmlspecialchars()` output escaping
- **Session Management** — Redis-backed sessions with automatic rotation
- **Rate Limiting** — brute-force protection on login attempts
- **Input Validation** — all user input validated and sanitized

## Configuration

System configuration is managed via the `.env` file. An example is provided as `.env.example`:

```ini
# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=xinyu_status
DB_USER=root
DB_PASS=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASS=
REDIS_DB=0
```

The installation wizard generates this file automatically. Manual edits are also supported.

## API Reference

### Public API

**`GET /public/api/status.php`**

Returns the current status of all monitored sites.

```json
{
  "sites": [
    {
      "id": 1,
      "name": "Example Site",
      "url": "https://example.com",
      "status": "up",
      "status_code": "200",
      "response_time": 0.23,
      "last_checked": "2026-07-15 16:00:00",
      "ssl_valid": true,
      "ssl_expires": "2026-12-01",
      "dns_resolved": "93.184.216.34"
    }
  ]
}
```

### Admin API

All admin endpoints require authentication and CSRF token. See `admin/api/index.php` for full documentation.

| Endpoint          | Method | Description              |
|-------------------|--------|--------------------------|
| `/admin/api/`     | GET    | Dashboard summary        |
| `?action=check`   | POST   | Check a single site      |
| `?action=check_all`| POST  | Check all sites          |
| `?action=uptime`  | GET    | Get uptime statistics    |
| `?action=trend`   | GET    | Get response time trend  |
| `?action=history` | GET    | Get check history        |

## License

GNU General Public License v3.0 — see [LICENSE](https://github.com/XINYUCloud/XINYUCloud_Status/blob/main/LICENSE) for details.