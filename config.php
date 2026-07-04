<?php
// ── Gerbang Inbox configuration ─────────────────────────────────────────────
// Fill these in for your environment. On shared hosting, get the DB host/name/
// user/pass from your hosting control panel (same credentials phpMyAdmin uses).

// --- Database (MySQL / phpMyAdmin) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'gerbang_inbox');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- Session cookie name ---
define('SESSION_COOKIE_NAME', 'gerbang_sid');

// --- Telegram Bot API ---
// Create a bot with @BotFather on Telegram to get a token.
define('TELEGRAM_BOT_TOKEN', ''); // e.g. '123456:ABC-DEF...'

// --- WhatsApp Cloud API (Meta) ---
// whatsapp-web.js (QR-code login) is a Node/Puppeteer library and cannot run in
// PHP. The Cloud API is Meta's official HTTP-based API and works from PHP.
// Get these from https://developers.facebook.com/apps -> your app -> WhatsApp -> API Setup.
define('WHATSAPP_TOKEN', '');            // permanent/system-user access token
define('WHATSAPP_PHONE_NUMBER_ID', '');  // the "Phone number ID", not the phone number itself
define('WHATSAPP_VERIFY_TOKEN', 'change-me'); // any string you choose; used when registering the webhook

date_default_timezone_set('Asia/Jakarta');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
