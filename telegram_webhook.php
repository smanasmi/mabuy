<?php
// Telegram calls this URL directly (no login/session involved — Telegram isn't a browser).
// Register it once, after deploying, by visiting in your own browser (replace values):
//   https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://yourdomain.com/telegram_webhook.php
require_once __DIR__ . '/includes/db.php';

$raw = file_get_contents('php://input');
$update = json_decode($raw, true);

if (!$update || empty($update['message']['text'])) {
    http_response_code(200); // acknowledge non-text updates so Telegram doesn't retry
    exit;
}

$msg = $update['message'];
$chatId = (string)$msg['chat']['id'];
$convId = 'telegram:' . $chatId;
$name = trim(($msg['chat']['first_name'] ?? '') . ' ' . ($msg['chat']['last_name'] ?? ''));
if ($name === '') $name = $msg['chat']['username'] ?? ('Chat ' . $chatId);
$handle = isset($msg['chat']['username']) ? '@' . $msg['chat']['username'] : $chatId;

try {
    upsert_conversation($convId, $name, 'telegram', $handle, $chatId);
    add_message($convId, 'them', $msg['text']);
    http_response_code(200);
    echo 'ok';
} catch (Exception $e) {
    error_log('[telegram_webhook] ' . $e->getMessage());
    http_response_code(200); // still 200 so Telegram doesn't hammer retries; error is logged
}
