<?php
/**
 * Admin panel: edit app config (SQLite). Access: ?key=<admin_secret or update_secret>.
 * If both secrets are empty, access allowed for first-time setup.
 */
$repoRoot = realpath(__DIR__);
if ($repoRoot === false) {
    http_response_code(500);
    exit('Invalid app root');
}
require_once $repoRoot . '/load_config.php';
$config = load_config($repoRoot);

$adminSecret = trim($config['admin_secret'] ?? '');
$updateSecret = trim($config['update_secret'] ?? '');
$key = trim($_REQUEST['key'] ?? '');
$allowed = false;
if ($adminSecret !== '' && $key !== '' && hash_equals($adminSecret, $key)) {
    $allowed = true;
} elseif ($updateSecret !== '' && $key !== '' && hash_equals($updateSecret, $key)) {
    $allowed = true;
} elseif ($adminSecret === '' && $updateSecret === '') {
    $allowed = true;
}

if (!$allowed) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admin</title></head><body style="font-family:sans-serif;padding:2rem;background:#0f0f12;color:#e4e4e7;">';
    echo '<h1>Access denied</h1><p>Use <code>?key=</code> with your admin or update secret.</p>';
    echo '<p><a href="index.php" style="color:#22c55e;">Back to app</a></p></body></html>';
    exit;
}

$saved = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = trim($_POST['update_auth_password'] ?? '');
    $updates = [
        'update_repo' => trim($_POST['update_repo'] ?? ''),
        'update_ip_allowlist' => trim($_POST['update_ip_allowlist'] ?? ''),
        'update_allow_app_any_ip' => !empty($_POST['update_allow_app_any_ip']) ? '1' : '0',
        'update_use_basic_auth' => !empty($_POST['update_use_basic_auth']) ? '1' : '0',
        'update_require_login_always' => !empty($_POST['update_require_login_always']) ? '1' : '0',
        'update_auth_user' => trim($_POST['update_auth_user'] ?? ''),
        'update_auth_password' => $newPass !== '' ? $newPass : ($config['update_auth_password'] ?? ''),
        'update_secret' => ($s = trim($_POST['update_secret'] ?? '')) !== '' ? $s : ($config['update_secret'] ?? ''),
        'admin_secret' => ($a = trim($_POST['admin_secret'] ?? '')) !== '' ? $a : ($config['admin_secret'] ?? ''),
    ];
    $saveResult = save_config($repoRoot, $updates);
    if ($saveResult === true) {
        $config = array_merge($config, $updates);
        $saved = true;
    } else {
        $error = is_string($saveResult) ? $saveResult : 'Could not save (check data/ is writable).';
    }
}

$pageTitle = 'Admin — Config';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <div class="wrap">
    <h1>Admin — Config</h1>
    <p class="sub">Settings are stored in <code>data/config.sqlite</code>. On first run, values are seeded from <code>config.php</code> if present.</p>
    <p class="sub"><a href="index.php">← Back to QR generator</a></p>

    <?php if ($saved) { echo '<p class="msg ok">Settings saved.</p>'; } ?>
    <?php if ($error !== '') { echo '<p class="msg err">' . htmlspecialchars($error) . '</p>'; } ?>

    <form method="post" action="">
      <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
      <div class="panel">
        <h2>Updates</h2>
        <label for="update_repo">GitHub repo (owner/repo) — for zip installs</label>
        <input type="text" id="update_repo" name="update_repo" value="<?php echo htmlspecialchars($config['update_repo'] ?? ''); ?>" placeholder="Darknetzz/php-qrcodegenerator">
        <p class="hint">Git clones use .git/config instead.</p>

        <label for="update_ip_allowlist">IP allowlist (comma-separated, optional)</label>
        <input type="text" id="update_ip_allowlist" name="update_ip_allowlist" value="<?php echo htmlspecialchars($config['update_ip_allowlist'] ?? ''); ?>" placeholder="127.0.0.1, 10.0.0.0/24">
        <div class="checkbox-row">
          <label>
            <input type="checkbox" name="update_allow_app_any_ip" value="1" <?php echo empty($config['update_allow_app_any_ip']) || $config['update_allow_app_any_ip'] === '0' ? '' : 'checked'; ?>>
            Allow app usage from any IP (uncheck to restrict main app to allowlist)
          </label>
        </div>

        <div class="checkbox-row">
          <label>
            <input type="checkbox" name="update_use_basic_auth" value="1" <?php echo !empty($config['update_use_basic_auth']) && $config['update_use_basic_auth'] !== '0' ? 'checked' : ''; ?>>
            Require login (username and password) for check/upgrade
          </label>
        </div>
        <div class="checkbox-row">
          <label>
            <input type="checkbox" name="update_require_login_always" value="1" <?php echo !empty($config['update_require_login_always']) && $config['update_require_login_always'] !== '0' ? 'checked' : ''; ?>>
            Require login even when IP is on allowlist
          </label>
        </div>
        <label for="update_auth_user">Login username</label>
        <input type="text" id="update_auth_user" name="update_auth_user" value="<?php echo htmlspecialchars($config['update_auth_user'] ?? ''); ?>" autocomplete="off">
        <label for="update_auth_password">Login password</label>
        <input type="password" id="update_auth_password" name="update_auth_password" value="" autocomplete="new-password" placeholder="Leave blank to keep current">
        <p class="hint">Leave blank to keep current value.</p>

        <label for="update_secret">Upgrade secret (optional)</label>
        <input type="password" id="update_secret" name="update_secret" value="" autocomplete="off" placeholder="Leave blank to keep current">
        <p class="hint">When set, POST ?action=upgrade must send this (body or X-Update-Secret header). Also accepted as admin key.</p>
      </div>

      <div class="panel">
        <h2>Admin access</h2>
        <label for="admin_secret">Admin secret (optional)</label>
        <input type="password" id="admin_secret" name="admin_secret" value="<?php echo htmlspecialchars($config['admin_secret'] ?? ''); ?>" autocomplete="off">
        <p class="hint">Use ?key=<strong>this_value</strong> to open this page. If empty, the upgrade secret can be used as the key.</p>
      </div>

      <button type="submit" class="btn">Save</button>
    </form>
  </div>
</body>
</html>
