<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth();
header('Content-Type: application/json');

$id = $_GET['id'] ?? '';
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_id']);
    exit;
}

mark_read($id);
echo json_encode(['ok' => true]);
