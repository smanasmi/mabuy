<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/telegram.php';
require_once __DIR__ . '/../includes/whatsapp.php';
require_auth();
header('Content-Type: application/json');

echo json_encode([
    'whatsapp' => ['ready' => whatsapp_configured()],
    'telegram' => ['ready' => telegram_configured()],
]);
