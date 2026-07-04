<?php
require_once __DIR__ . '/includes/auth.php';
gi_session_start();
if (current_user()) {
    header('Location: /index.php');
    exit;
}
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!rate_limit('login_' . $ip, 20, 15 * 60)) {
        $error = 'Terlalu banyak percobaan. Coba lagi nanti.';
    } else {
        $user = verify_login($_POST['username'] ?? '', $_POST['password'] ?? '');
        if ($user) {
            login_session($user);
            header('Location: /index.php');
            exit;
        }
        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Masuk — Gerbang Inbox</title>
  <?php require __DIR__ . '/includes/head_meta.php'; ?>
</head>
<body class="gi-root">
  <div class="gi-auth-screen">
    <form class="gi-auth-card" method="post" autocomplete="on">
      <div class="gi-auth-arch">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10.5C4 6.36 7.58 3 12 3s8 3.36 8 7.5V21"/><path d="M4 21h16"/><path d="M8.5 21v-8" stroke-width="1.5" opacity=".55"/><path d="M15.5 21v-8" stroke-width="1.5" opacity=".55"/></svg>
      </div>
      <h1 class="gi-auth-title gi-display">Gerbang Inbox</h1>
      <p class="gi-auth-tagline">Masuk untuk membuka dashboard Anda</p>

      <label class="gi-login-label" for="gi-username">Username</label>
      <input class="gi-login-input" id="gi-username" name="username" autocomplete="username" required autofocus />

      <label class="gi-login-label" for="gi-password">Password</label>
      <input class="gi-login-input" id="gi-password" name="password" type="password" autocomplete="current-password" required />

      <button class="gi-login-btn" type="submit">Masuk</button>
      <?php if ($error): ?>
        <p class="gi-login-error"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
    </form>
  </div>
</body>
</html>
