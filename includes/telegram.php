<?php
require_once __DIR__ . '/../config.php';

function telegram_configured(): bool {
    return TELEGRAM_BOT_TOKEN !== '';
}

function send_telegram_message(string $chatId, string $text): bool {
    if (!telegram_configured()) {
        throw new Exception('Telegram bot not configured (set TELEGRAM_BOT_TOKEN in config.php)');
    }
    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $chatId, 'text' => $text]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $ok = curl_errno($ch) === 0;
    curl_close($ch);
    if (!$ok) throw new Exception('Failed to reach Telegram API');
    $decoded = json_decode($response, true);
    if (empty($decoded['ok'])) throw new Exception($decoded['description'] ?? 'Telegram API error');
    return true;
}
