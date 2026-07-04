<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/telegram.php';
require_once __DIR__ . '/../includes/whatsapp.php';
require_auth();
header('Content-Type: application/json');

$me = current_user();
$id = $_GET['id'] ?? '';
$body = json_decode(file_get_contents('php://input'), true) ?: [];
$text = trim($body['text'] ?? '');

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_id']);
    exit;
}

// 20 outgoing messages per minute per agent — keeps well under WhatsApp/Telegram abuse thresholds.
if (!rate_limit('send_' . $me['id'], 20, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'rate_limited', 'detail' => 'Terlalu banyak pesan dikirim — coba lagi sebentar.']);
    exit;
}

$conv = get_conversation($id);
if (!$conv) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}
if ($text === '') {
    http_response_code(400);
    echo json_encode(['error' => 'empty_message']);
    exit;
}
if (strlen($text) > 4096) {
    http_response_code(400);
    echo json_encode(['error' => 'message_too_long']);
    exit;
}

try {
    if ($conv['channel'] === 'whatsapp') {
        send_whatsapp_message($conv['externalId'], $text);
    } elseif ($conv['channel'] === 'telegram') {
        send_telegram_message($conv['externalId'], $text);
    }
    $saved = add_message($conv['id'], 'me', $text, 'sent', $me['displayName']);
    echo json_encode(['ok' => true, 'message' => $saved]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'send_failed', 'detail' => $e->getMessage()]);
}
