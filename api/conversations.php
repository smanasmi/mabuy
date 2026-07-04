<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth();
header('Content-Type: application/json');

try {
    if (isset($_GET['id'])) {
        $conv = get_conversation($_GET['id']);
        if (!$conv) {
            http_response_code(404);
            echo json_encode(['error' => 'not_found']);
            exit;
        }
        echo json_encode($conv);
    } else {
        echo json_encode(get_conversations());
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'detail' => $e->getMessage()]);
}
