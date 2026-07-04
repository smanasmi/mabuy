<?php
require_once __DIR__ . '/db.php';

function gi_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_COOKIE_NAME);
        session_set_cookie_params([
            'lifetime' => 30 * 24 * 60 * 60, // 30 days
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function current_user(): ?array {
    gi_session_start();
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'displayName' => $_SESSION['display_name'],
    ];
}

// Redirects browser pages to /login.php, or returns a 401 JSON for /api/* requests.
function require_auth(): void {
    if (current_user()) return;
    $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
    if ($isApi) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
    header('Location: /login.php');
    exit;
}

function verify_login(string $username, string $password): ?array {
    if (!$username || !$password) return null;
    $user = get_user_by_username(strtolower(trim($username)));
    if (!$user) return null;
    if (!password_verify($password, $user['password_hash'])) return null;
    return ['id' => $user['id'], 'username' => $user['username'], 'displayName' => $user['display_name']];
}

function login_session(array $user): void {
    gi_session_start();
    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['display_name'] = $user['displayName'];
}

function logout_session(): void {
    gi_session_start();
    $_SESSION = [];
    session_destroy();
    setcookie(SESSION_COOKIE_NAME, '', time() - 3600, '/');
}

// Very small fixed-window rate limiter, backed by a file in the system temp dir.
// Good enough to slow down password guessing on a single small server; if you're
// behind a shared IP/load balancer, tighten or replace with something IP+account aware.
function rate_limit(string $key, int $maxAttempts, int $windowSeconds): bool {
    $file = sys_get_temp_dir() . '/gi_rl_' . md5($key) . '.json';
    $now = time();
    $data = ['count' => 0, 'reset' => $now + $windowSeconds];
    if (is_file($file)) {
        $decoded = json_decode(file_get_contents($file), true);
        if (is_array($decoded) && $decoded['reset'] > $now) $data = $decoded;
    }
    $data['count']++;
    file_put_contents($file, json_encode($data));
    return $data['count'] <= $maxAttempts;
}
