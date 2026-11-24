# Nalo Solutions USSD Laravel Application

A production-ready Laravel application built for seamless integration with the **Nalo Solutions USSD API Gateway** and ready for rapid deployment on **Render** (or any cloud VPS/container platform).

---

## 📌 Features & Menu Flow

This application implements a dynamic mood and reason survey flow with state persistence across requests using Eloquent models and SQLite / PostgreSQL:

```
[User dials shortcode, e.g. *920#]
              │
              ▼
┌──────────────────────────────────────────────┐
│ SCREEN 1 (Welcome Screen)                    │
│ "Welcome! How are you feeling today?"        │
│ 1. Feeling fine                              │
│ 2. Feeling sad                               │
│ 3. Feeling frisky                            │
└──────────────────────────────────────────────┘
              │ User selects 1, 2, or 3
              ▼
┌──────────────────────────────────────────────┐
│ SCREEN 2 (Reason Selection)                  │
│ "You are feeling <feeling> because..."       │
│ 1. Relationship issues                       │
│ 2. Money problems                            │
│ 3. Family issues                             │
└──────────────────────────────────────────────┘
              │ User selects 1, 2, or 3
              ▼
┌──────────────────────────────────────────────┐
│ SCREEN 3 (Final Screen)                      │
│ "You are feeling <feeling> because of        │
│  <reason>. Feel well soon!"                  │
│ [Session Terminated - MSGTYPE: false]        │
└──────────────────────────────────────────────┘
```

---

## 📁 Project Directory Structure

```
nalo_ussd_laravel/
├── app/
│   ├── Console/Commands/
│   │   └── SimulateUssdCommand.php   # Artisan command for interactive phone simulation
│   ├── Http/Controllers/
│   │   └── UssdController.php        # Nalo webhook callback and JSON response handler
│   └── Models/
│       └── UssdSession.php           # Eloquent session state tracking model
├── bootstrap/
│   └── app.php                       # Application configuration & CSRF exemption
├── config/
│   └── ussd.php                      # Nalo USSD settings & menu choices
├── database/
│   └── migrations/
│       └── 2026_11_24_000001_create_ussd_sessions_table.php
├── routes/
│   ├── api.php                       # API endpoint /api/ussd
│   └── web.php                       # Web endpoint /ussd & health check /
├── tests/Feature/
│   └── NaloUssdTest.php              # Automated test suite (8 tests covering all paths)
├── build.sh                          # Render deployment build script
├── env.sh                            # Local environment helper script
├── test.sh                           # Test runner helper script
├── Procfile                          # Process definition for Render / Heroku
├── render.yaml                       # Render Blueprint Infrastructure-as-Code
└── simulate_ussd.php                 # Standalone CLI phone simulator
```

---

## 📡 Nalo USSD Gateway API Protocol

### 1. Inbound Request (from Nalo to your server)

Nalo sends a `POST` request with JSON (or Form URL-Encoded):

```json
{
  "USERID": "NALOTest",
  "MSISDN": "233541234567",
  "USERDATA": "1",
  "MSGTYPE": false,
  "NETWORK": "MTN",
  "SESSIONID": "SESSION-123456"
}
```

| Field | Type | Description |
|---|---|---|
| `USERID` | string | Your Nalo account User ID |
| `MSISDN` | string | Mobile subscriber phone number (international format without `+`) |
| `USERDATA` | string | The character(s) input by the user on their phone |
| `MSGTYPE` | boolean | `true` when user first dials the shortcode; `false` on subsequent inputs |
| `NETWORK` | string | Mobile network operator (e.g., `MTN`, `TELECEL`, `AT`) |
| `SESSIONID` | string | Unique identifier for the USSD session |

### 2. Outbound Response (from your server to Nalo)

Your endpoint returns `200 OK` with JSON:

```json
{
  "USERID": "NALOTest",
  "MSISDN": "233541234567",
  "MSG": "Welcome! How are you feeling today?\n1. Feeling fine\n2. Feeling sad\n3. Feeling frisky",
  "MSGTYPE": true
}
```

| Field | Type | Description |
|---|---|---|
| `USERID` | string | Your Nalo User ID |
| `MSISDN` | string | Mobile subscriber phone number |
| `MSG` | string | Text menu displayed on user's handset screen (newlines `\n` format menus) |
| `MSGTYPE` | boolean | `true` keeps session open and awaits user response; `false` terminates session |

---

## 🚀 Quick Start & Local Development

### 1. Configure Local Environment

```bash
# Source environment helper (loads PHP extensions & Composer)
source env.sh

# Run migrations
php artisan migrate
```

### 2. Run Local Development Server

```bash
php artisan serve
# Server runs at http://127.0.0.1:8000
```

Verify health check:
```bash
curl http://127.0.0.1:8000/api/ussd
```

Expected output:
```json
{
  "status": "healthy",
  "service": "Nalo USSD Menu Service (Laravel)",
  "menu": "Mood & Reasons Flow",
  "endpoint": "api/ussd"
}
```

---

## 📱 Testing with the Interactive Phone Simulator

An interactive terminal phone simulator is included to test the complete user experience without needing actual telco infrastructure.

### Option A: Via Artisan Command
```bash
# Internal kernel dispatch (fastest, no server required)
php artisan ussd:simulate

# Or simulate against a running server
php artisan ussd:simulate --url=http://127.0.0.1:8000/api/ussd
```

### Option B: Via Standalone Script
```bash
# Internal kernel mode
php simulate_ussd.php

# Or with custom endpoint
php simulate_ussd.php http://127.0.0.1:8000/api/ussd
```

Sample simulator output:
```
============================================================
📱 NALO USSD PHONE SIMULATOR (LARAVEL)
Target: Internal Laravel Kernel
User ID: NALOTest
Phone (MSISDN): 233241234567
Network: MTN
Session ID: SIM-1795534522-8d2658
============================================================
Dialing *920# ...

┌──────────────────────────────────────────────┐
│ Welcome! How are you feeling today?          │
│ 1. Feeling fine                              │
│ 2. Feeling sad                               │
│ 3. Feeling frisky                            │
└──────────────────────────────────────────────┘

Phone Input > 1
┌──────────────────────────────────────────────┐
│ You are feeling fine because...              │
│ 1. Relationship issues                       │
│ 2. Money problems                            │
│ 3. Family issues                             │
└──────────────────────────────────────────────┘

Phone Input > 3
┌──────────────────────────────────────────────┐
│ You are feeling fine because of family issues. │
│ Feel well soon!                              │
└──────────────────────────────────────────────┘

[Session Terminated (MSGTYPE=false)]
```

---

## 🧪 Running Automated Tests

Run the full automated test suite covering all paths, input retries, and session isolation:

```bash
./test.sh
# or
php artisan test --filter=NaloUssdTest
```

Expected output:
```
   PASS  Tests\Feature\NaloUssdTest
  ✓ health check get returns healthy
  ✓ web route health check get
  ✓ complete flow feeling fine family issues
  ✓ complete flow feeling sad relationship issues
  ✓ complete flow feeling frisky money problems
  ✓ invalid feeling choice prompts retry
  ✓ invalid reason choice prompts retry
  ✓ session isolation between different users

  Tests:    8 passed (48 assertions)
  Duration: 0.41s
```

---

## ☁️ Deploying to Render

This project includes `render.yaml` Blueprint configuration, `build.sh`, and `Procfile`.

### Method 1: Deploy with Git & Render Dashboard
1. Push this repository to GitHub or GitLab.
2. Go to [Render Dashboard](https://dashboard.render.com).
3. Click **New +** -> **Web Service**.
4. Connect your repository.
5. Render will automatically detect `render.yaml` and configure:
   - **Environment**: PHP
   - **Build Command**: `./build.sh`
   - **Start Command**: `heroku-php-apache2 public/`
6. Set your environment variables:
   - `NALO_USER_ID`: Your Nalo User ID
   - `APP_KEY`: Your Laravel app key (or Render auto-generates)
   - `APP_URL`: Your Render service URL (e.g. `https://your-app.onrender.com`)

### Method 2: Configure Webhook on Nalo Solutions Dashboard
1. Log in to your [Nalo Solutions USSD Portal](https://nalosolutions.com).
2. Navigate to **USSD Management** -> **Application URLs / Webhook**.
3. Set Callback URL:
   ```
   https://your-service.onrender.com/api/ussd
   ```
   *(or `https://your-service.onrender.com/ussd`)*
4. Set HTTP Method to `POST`.
5. Test dialing your assigned USSD shortcode (e.g., `*920*XXX#`).
