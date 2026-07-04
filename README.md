# Gerbang Inbox — PHP + MySQL version

A PHP/MySQL port of the original Node.js/Postgres app. No Node, no Socket.io —
plain PHP files you can drop onto any shared host with PHP 8+ and MySQL/MariaDB.

## What changed from the Node version (and why)

| Original (Node) | This version (PHP) | Why |
|---|---|---|
| Postgres | MySQL, imported via phpMyAdmin | you asked for phpMyAdmin/MySQL |
| Socket.io (real-time push) | polling every 3 seconds | PHP (on typical shared hosting) has no long-running process to hold websocket connections open |
| `whatsapp-web.js` (Puppeteer, QR-code login) | **WhatsApp Cloud API** (Meta's official HTTP API) | `whatsapp-web.js` automates a real Chrome browser — that's a Node-only approach and can't run inside PHP. The Cloud API is just HTTP calls, which PHP handles natively. This does mean you need a Meta Developer account + WhatsApp Business number instead of scanning a QR code. |
| `node-telegram-bot-api` (polling) | Telegram Bot API via **webhook** | more natural fit for PHP — Telegram pushes updates to your URL instead of you polling Telegram |
| express-session + connect-pg-simple | native PHP sessions (file-based) | simplest option for shared hosting |

## Setup

### 1. Database
In phpMyAdmin: create a database (e.g. `gerbang_inbox`), then **Import** `schema.sql`.

### 2. Configure
Edit `config.php`:
- `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` — same credentials phpMyAdmin uses
- `TELEGRAM_BOT_TOKEN` — from [@BotFather](https://t.me/BotFather)
- `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_VERIFY_TOKEN` — from your Meta App's WhatsApp > API Setup page (leave blank to skip WhatsApp)

### 3. Create a dashboard login
For the very first login, use the CLI script (there's no way to sign up before you can log in):

```
php create_user.php myusername mypassword "My Display Name"
```

After that, any logged-in agent can create and edit accounts from **Kelola Pengguna** (`/users.php`), linked from the sidebar — no CLI needed for day-to-day use.

### 4. Upload everything to your web root
Point your domain at the folder containing `index.php`.

### 5. Register the webhooks (so incoming messages reach your app)
- **Telegram**: visit once in a browser (replace values):
  `https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://yourdomain.com/telegram_webhook.php`
- **WhatsApp**: in your Meta App dashboard, set the webhook URL to
  `https://yourdomain.com/whatsapp_webhook.php` and the verify token to whatever you put in `WHATSAPP_VERIFY_TOKEN`. Subscribe to the `messages` field.

Both webhook URLs must be reachable over HTTPS from the internet — Telegram and Meta call them directly.

### 6. Log in
Go to `https://yourdomain.com/login.php`.

## File map
```
config.php              database + API credentials
schema.sql               import this into phpMyAdmin
create_user.php          CLI: add/reset a dashboard login
login.php / logout.php   session auth
index.php                dashboard shell
users.php                in-app user management (create/edit agent logins)
includes/db.php          all database queries
includes/auth.php        session + login helpers
includes/telegram.php    send messages via Telegram Bot API
includes/whatsapp.php    send messages via WhatsApp Cloud API
telegram_webhook.php     receives incoming Telegram messages
whatsapp_webhook.php     receives incoming WhatsApp messages
api/conversations.php    GET conversation list / single conversation
api/send_message.php     POST an outgoing message
api/mark_read.php        POST mark a conversation read
api/status.php           GET whether WhatsApp/Telegram are configured
assets/app.js            dashboard frontend (polling, no websockets)
assets/styles.css        unchanged from the original
```

## Limitations to know about
- **No true real-time push.** Updates appear within ~3 seconds (polling interval in `assets/app.js`), not instantly.
- **WhatsApp requires a Meta Business/Developer account.** There's no QR-code "just scan your personal WhatsApp" option in this version — that's inherent to switching away from browser automation.
- **Sessions are file-based** by default (PHP's normal behavior). Fine for a single server; if you run multiple app servers behind a load balancer, you'd need shared session storage (e.g. `session.save_handler = memcached` or a DB-backed handler).
