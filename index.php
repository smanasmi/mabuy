<?php
require_once __DIR__ . '/includes/auth.php';
require_auth();
$me = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Gerbang Inbox — WhatsApp & Telegram</title>
  <?php require __DIR__ . '/includes/head_meta.php'; ?>
</head>
<body class="gi-root">
  <div id="app"></div>
  <script>window.__ME__ = <?= json_encode(['username' => $me['username'], 'displayName' => $me['displayName']]) ?>;</script>
  <script src="/assets/app.js"></script>
</body>
</html>
