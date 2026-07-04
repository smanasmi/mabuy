<?php
// Meta calls this URL directly. Register it in your Meta App Dashboard ->
// WhatsApp -> Configuration -> Webhook, using WHATSAPP_VERIFY_TOKEN from config.php.
require_once __DIR__ . '/includes/db.php';

// --- Step 1: verification handshake (Meta sends a GET when you save the webhook URL) ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';
    if ($mode === 'subscribe' && $token === WHATSAPP_VERIFY_TOKEN) {
        echo $challenge;
        exit;
    }
    http_response_code(403);
    exit;
}

// --- Step 2: incoming message events (POST) ---
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

http_response_code(200); // acknowledge quickly; Meta retries aggressively on non-200

$entries = $body['entry'] ?? [];
foreach ($entries as $entry) {
    foreach (($entry['changes'] ?? []) as $change) {
        $value = $change['value'] ?? [];
        $messages = $value['messages'] ?? [];
        $contacts = $value['contacts'] ?? [];

        foreach ($messages as $msg) {
            if (($msg['type'] ?? '') !== 'text') continue; // extend for media as needed
            $from = $msg['from']; // phone number, no '+'
            $convId = 'whatsapp:' . $from;
            $name = $contacts[0]['profile']['name'] ?? $from;

            try {
                upsert_conversation($convId, $name, 'whatsapp', $from, $from);
                add_message($convId, 'them', $msg['text']['body'] ?? '');
            } catch (Exception $e) {
                error_log('[whatsapp_webhook] ' . $e->getMessage());
            }
        }
    }
}
