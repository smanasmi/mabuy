<?php
require_once __DIR__ . '/includes/auth.php';
require_auth();
$me = current_user();

$success = '';
$error   = '';

// $mode controls the slide-over panel: null = closed, 'add' or 'edit' = open
// with the matching form. $formValues is what repopulates the panel's inputs.
$mode = null;
$formValues = ['id' => null, 'username' => '', 'display_name' => ''];

// ── Handle form submissions ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username    = strtolower(trim($_POST['username'] ?? ''));
        $displayName = trim($_POST['display_name'] ?? '');
        $password    = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif (get_user_by_username($username)) {
            $error = 'Username sudah digunakan.';
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                create_user($username, $hash, $displayName ?: $username);
                $success = "Pengguna \"$username\" berhasil dibuat.";
            } catch (Exception $e) {
                $error = 'Gagal membuat pengguna: ' . $e->getMessage();
            }
        }

        if ($error !== '') {
            // Keep the panel open with what the admin already typed.
            $mode = 'add';
            $formValues = ['id' => null, 'username' => $username, 'display_name' => $displayName];
        }
    }

    if ($action === 'update') {
        $id          = (int)($_POST['id'] ?? 0);
        $username    = strtolower(trim($_POST['username'] ?? ''));
        $displayName = trim($_POST['display_name'] ?? '');
        $password    = $_POST['password'] ?? '';

        $existing = get_user_by_id($id);
        if (!$existing) {
            $error = 'Pengguna tidak ditemukan.';
        } elseif ($username === '') {
            $error = 'Username wajib diisi.';
        } elseif ($password !== '' && strlen($password) < 8) {
            $error = 'Password baru minimal 8 karakter (atau kosongkan untuk tidak mengubah).';
        } else {
            $collision = get_user_by_username($username);
            if ($collision && (int)$collision['id'] !== $id) {
                $error = 'Username sudah digunakan oleh pengguna lain.';
            } else {
                try {
                    $hash = $password !== '' ? password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]) : null;
                    update_user($id, $username, $displayName ?: $username, $hash);
                    if ($id === (int)$me['id']) {
                        $_SESSION['username']     = $username;
                        $_SESSION['display_name'] = $displayName ?: $username;
                    }
                    $success = "Pengguna \"$username\" berhasil diperbarui.";
                } catch (Exception $e) {
                    $error = 'Gagal memperbarui pengguna: ' . $e->getMessage();
                }
            }
        }

        if ($error !== '') {
            // Keep the panel open on the same user with what was typed.
            $mode = 'edit';
            $formValues = ['id' => $id, 'username' => $username, 'display_name' => $displayName];
        }
    }
}

// ── Open the panel from a link: /users.php?edit=ID or /users.php?panel=add ──
if ($mode === null && isset($_GET['edit'])) {
    $editUser = get_user_by_id((int)$_GET['edit']);
    if ($editUser) {
        $mode = 'edit';
        $formValues = [
            'id' => $editUser['id'],
            'username' => $editUser['username'],
            'display_name' => $editUser['display_name'] ?? '',
        ];
    }
} elseif ($mode === null && isset($_GET['panel']) && $_GET['panel'] === 'add') {
    $mode = 'add';
}

$users = get_all_users();

function gi_initials(string $displayName, string $username): string {
    $source = trim($displayName) !== '' ? $displayName : $username;
    $words = preg_split('/\s+/', trim($source));
    $letters = '';
    foreach (array_slice($words, 0, 2) as $w) {
        if ($w !== '') { $letters .= mb_strtoupper(mb_substr($w, 0, 1)); }
    }
    return $letters !== '' ? $letters : '?';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kelola Pengguna — Gerbang Inbox</title>
  <?php require __DIR__ . '/includes/head_meta.php'; ?>
</head>
<body class="gi-root">
<div class="gi-shell">
  <div class="gi-topbar">
    <div class="gi-topbar-brand">
      <div class="gi-gate-mark">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10.5C4 6.36 7.58 3 12 3s8 3.36 8 7.5V21"/><path d="M4 21h16"/><path d="M8.5 21v-8" stroke-width="1.5" opacity=".55"/><path d="M15.5 21v-8" stroke-width="1.5" opacity=".55"/></svg>
      </div>
      <div>
        <div class="gi-topbar-name gi-display">Gerbang Inbox</div>
        <div class="gi-topbar-tagline">Kelola Pengguna</div>
      </div>
    </div>
    <div class="gi-topbar-actions">
      <span class="gi-agent-name"><?= htmlspecialchars($me['display_name'] ?? $me['username']) ?></span>
      <a class="gi-manage-btn" href="/index.php" style="text-decoration:none;display:inline-flex;align-items:center">&larr; Dashboard</a>
      <a class="gi-logout-btn" href="/logout.php" style="text-decoration:none;display:inline-flex;align-items:center">Keluar</a>
    </div>
  </div>

  <div class="gi-page">
  <div class="gi-page-inner">

    <div class="gi-page-head">
      <div>
        <p class="gi-page-eyebrow gi-mono">Pengaturan tim</p>
        <h1 class="gi-page-title gi-display">Kelola Pengguna</h1>
      </div>
      <a class="gi-btn-primary gi-btn-with-icon" href="/users.php?panel=add">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
        Tambah Pengguna
      </a>
    </div>

    <?php if ($success): ?><div class="gi-alert gi-alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error && $mode === null): ?><div class="gi-alert gi-alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="gi-card">
      <h2>Semua Pengguna (<?= count($users) ?>)</h2>
      <p class="gi-card-hint">Setiap orang yang masuk membalas dari akun sendiri, jadi riwayat percakapan tetap jelas siapa mengatakan apa.</p>

      <?php if (empty($users)): ?>
        <div class="gi-table-empty">Belum ada pengguna.</div>
      <?php else: ?>
        <table class="gi-user-table">
          <thead>
            <tr><th>Pengguna</th><th>Dibuat</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td>
                  <div class="gi-user-cell">
                    <div class="gi-avatar"><?= htmlspecialchars(gi_initials($u['display_name'] ?? '', $u['username'])) ?></div>
                    <div class="gi-user-cell-text">
                      <div class="gi-user-cell-name">
                        <?= htmlspecialchars($u['display_name'] ?: $u['username']) ?>
                        <?php if ((int)$u['id'] === (int)$me['id']): ?><span class="gi-you-pill">Anda</span><?php endif; ?>
                      </div>
                      <div class="gi-user-cell-username gi-mono">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                  </div>
                </td>
                <td class="gi-col-muted"><?= $u['created_at'] ? date('d M Y', (int)($u['created_at'] / 1000)) : '-' ?></td>
                <td class="gi-col-action"><a class="gi-edit-link" href="/users.php?edit=<?= (int)$u['id'] ?>">Edit</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
  </div>
</div>

<!-- ── Slide-over panel: add / edit user. Kept out of the page flow so
     editing never pushes the rest of the screen around. ─────────────── -->
<div class="gi-panel-overlay <?= $mode ? 'open' : '' ?>" id="giPanelOverlay"></div>
<aside class="gi-panel <?= $mode ? 'open' : '' ?>" id="giPanel" aria-hidden="<?= $mode ? 'false' : 'true' ?>">
  <div class="gi-panel-head">
    <h2><?= $mode === 'edit' ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?></h2>
    <a class="gi-panel-close" href="/users.php" aria-label="Tutup">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    </a>
  </div>
  <p class="gi-card-hint gi-panel-hint">
    <?= $mode === 'edit' ? 'Perbarui detail login untuk akun ini.' : 'Buat login untuk agen baru yang akan membalas percakapan.' ?>
  </p>

  <?php if ($error && $mode !== null): ?><div class="gi-alert gi-alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="post" class="gi-panel-form">
    <input type="hidden" name="action" value="<?= $mode === 'edit' ? 'update' : 'create' ?>" />
    <?php if ($mode === 'edit'): ?>
      <input type="hidden" name="id" value="<?= (int)$formValues['id'] ?>" />
    <?php endif; ?>

    <div class="gi-field">
      <label>Username</label>
      <input name="username" value="<?= htmlspecialchars($formValues['username']) ?>" required autocomplete="off" />
    </div>
    <div class="gi-field">
      <label>Nama Tampilan</label>
      <input name="display_name" value="<?= htmlspecialchars($formValues['display_name']) ?>" autocomplete="off" />
    </div>
    <div class="gi-field">
      <label>Password<?= $mode === 'edit' ? ' Baru' : '' ?></label>
      <input type="password" name="password" placeholder="<?= $mode === 'edit' ? 'Kosongkan jika tidak ingin mengubah' : '' ?>" <?= $mode === 'edit' ? '' : 'required' ?> autocomplete="new-password" />
      <small><?= $mode === 'edit' ? 'Minimal 8 karakter jika diisi.' : 'Minimal 8 karakter.' ?></small>
    </div>

    <div class="gi-panel-actions">
      <a class="gi-btn-secondary" href="/users.php">Batal</a>
      <button class="gi-btn-primary" type="submit"><?= $mode === 'edit' ? 'Simpan Perubahan' : 'Buat Pengguna' ?></button>
    </div>
  </form>
</aside>

<script>
  // Small progressive enhancements around the slide-over panel. The panel
  // itself works with plain links/forms (no JS required), this just makes
  // closing it feel nicer: click the backdrop or press Esc.
  (function () {
    var overlay = document.getElementById('giPanelOverlay');
    if (!overlay) return;
    overlay.addEventListener('click', function () { window.location.href = '/users.php'; });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('open')) {
        window.location.href = '/users.php';
      }
    });
  })();
</script>
</body>
</html>
