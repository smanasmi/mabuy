<?php
require_once __DIR__ . '/../config.php';

function whatsapp_configured(): bool {
    return WHATSAPP_TOKEN !== '' && WHATSAPP_PHONE_NUMBER_ID !== '';
}

function send_whatsapp_message(string $toPhone, string $text): bool {
    if (!whatsapp_configured()) {
        throw new Exception('WhatsApp not configured (set WHATSAPP_TOKEN / WHATSAPP_PHONE_NUMBER_ID in config.php)');
    }
    $url = 'https://graph.facebook.com/v19.0/' . WHATSAPP_PHONE_NUMBER_ID . '/messages';
    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to' => $toPhone,
        'type' => 'text',
        'text' => ['body' => $text],
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . WHATSAPP_TOKEN,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $ok = curl_errno($ch) === 0;
    curl_close($ch);
    if (!$ok) throw new Exception('Failed to reach WhatsApp Cloud API');
    $decoded = json_decode($response, true);
    if (isset($decoded['error'])) throw new Exception($decoded['error']['message'] ?? 'WhatsApp API error');
    return true;
}
