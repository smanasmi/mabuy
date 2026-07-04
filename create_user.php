<?php
// Creates (or resets the password of) a dashboard login. Run from the command line:
//
//   php create_user.php <username> <password> ["Display Name"]
//
// This is a CLI-only, admin-run script — there's no public signup form, since this
// is an internal support-team tool, not a product with self-serve accounts.
require_once __DIR__ . '/includes/db.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

$username = $argv[1] ?? null;
$password = $argv[2] ?? null;
$displayName = $argv[3] ?? null;

if (!$username || !$password) {
    fwrite(STDERR, "Usage: php create_user.php <username> <password> [\"Display Name\"]\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Password must be at least 8 characters.\n");
    exit(1);
}

try {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $user = create_user(strtolower(trim($username)), $hash, $displayName);
    echo "\n[create_user] Saved login for \"{$user['username']}\" ({$user['display_name']}).\n\n";
} catch (Exception $e) {
    fwrite(STDERR, '[create_user] Failed: ' . $e->getMessage() . "\n");
    exit(1);
}
