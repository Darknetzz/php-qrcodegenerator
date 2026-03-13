<?php
/**
 * Admin panel: edit app config (SQLite). Access: ?key=<admin_secret or update_secret>.
 * A valid key is always required; no unauthenticated access.
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
}

if (!$allowed) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    $noSecrets = ($adminSecret === '' && $updateSecret === '');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admin</title></head><body style="font-family:sans-serif;padding:2rem;background:#0f0f12;color:#e4e4e7;">';
    echo '<h1>Access denied</h1>';
    echo '<p>Use <code>?key=</code> with your admin or update secret.</p>';
    if ($noSecrets) {
        echo '<p>No admin or upgrade secret is set. Set <code>admin_secret</code> in <code>config.php</code> (then reload once so it is seeded into the database), or set it directly in <code>data/config.sqlite</code>, then open this page with <code>?key=your_secret</code>.</p>';
    }
    echo '<p><a href="index.php" style="color:#22c55e;">Back to app</a></p></body></html>';
    exit;
}

$baseUrl = 'admin.php?key=' . rawurlencode($key);
$defaultPresetIds = ['url', 'wifi', 'vcard', 'text', 'email', 'sms', 'bitcoin', 'facebook', 'pdf', 'mp3', 'appstore', 'image', 'custom'];
$defaultPresetLabels = ['url' => 'URL', 'wifi' => 'Wi‑Fi', 'vcard' => 'vCard', 'text' => 'Text', 'email' => 'Email', 'sms' => 'SMS', 'bitcoin' => 'Bitcoin', 'facebook' => 'Facebook', 'pdf' => 'PDF', 'mp3' => 'MP3', 'appstore' => 'App Store', 'image' => 'Image', 'custom' => 'Custom'];

$adminModules = json_decode($config['custom_modules'] ?? '[]', true);
if (!is_array($adminModules)) {
    $adminModules = [];
}

$presetOrder = json_decode($config['preset_order'] ?? '[]', true);
if (!is_array($presetOrder) || count($presetOrder) !== count($defaultPresetIds)) {
    $presetOrder = $defaultPresetIds;
} else {
    $presetOrder = array_values(array_intersect($presetOrder, $defaultPresetIds));
    $presetOrder = array_merge($presetOrder, array_diff($defaultPresetIds, $presetOrder));
}

// Handle move via GET (avoids nested forms)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['move_module_id'], $_GET['move_module_direction']) && is_string($_GET['move_module_id']) && $_GET['move_module_id'] !== '') {
    $moveId = $_GET['move_module_id'];
    $dir = $_GET['move_module_direction'] === 'down' ? 1 : -1;
    $idx = null;
    foreach ($adminModules as $i => $m) {
        if (isset($m['id']) && $m['id'] === $moveId) {
            $idx = $i;
            break;
        }
    }
    if ($idx !== null && (($dir === -1 && $idx > 0) || ($dir === 1 && $idx < count($adminModules) - 1))) {
        $swap = $idx + $dir;
        $tmp = $adminModules[$idx];
        $adminModules[$idx] = $adminModules[$swap];
        $adminModules[$swap] = $tmp;
        $saveResult = save_config($repoRoot, ['custom_modules' => json_encode($adminModules)]);
        if ($saveResult === true) {
            header('Location: ' . $baseUrl . '&tab=modules');
            exit;
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['move_preset_id'], $_GET['move_preset_direction']) && is_string($_GET['move_preset_id']) && $_GET['move_preset_id'] !== '') {
    $moveId = $_GET['move_preset_id'];
    $dir = $_GET['move_preset_direction'] === 'down' ? 1 : -1;
    $order = json_decode($config['preset_order'] ?? '[]', true);
    if (!is_array($order) || count($order) !== count($defaultPresetIds)) {
        $order = $defaultPresetIds;
    }
    $idx = array_search($moveId, $order, true);
    if ($idx !== false && (($dir === -1 && $idx > 0) || ($dir === 1 && $idx < count($order) - 1))) {
        $swap = $idx + $dir;
        $tmp = $order[$idx];
        $order[$idx] = $order[$swap];
        $order[$swap] = $tmp;
        $saveResult = save_config($repoRoot, ['preset_order' => json_encode($order)]);
        if ($saveResult === true) {
            header('Location: ' . $baseUrl . '&tab=modules');
            exit;
        }
    }
}

$editModule = null;
if (isset($_GET['edit']) && is_string($_GET['edit']) && $_GET['edit'] !== '') {
    foreach ($adminModules as $m) {
        if (isset($m['id']) && $m['id'] === $_GET['edit']) {
            $editModule = $m;
            break;
        }
    }
}

$saved = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Module delete
    if (isset($_POST['delete_module_id']) && is_string($_POST['delete_module_id']) && $_POST['delete_module_id'] !== '') {
        $toDelete = $_POST['delete_module_id'];
        $adminModules = array_values(array_filter($adminModules, function ($m) use ($toDelete) {
            return (isset($m['id']) ? $m['id'] : '') !== $toDelete;
        }));
        $saveResult = save_config($repoRoot, ['custom_modules' => json_encode($adminModules)]);
        if ($saveResult === true) {
            header('Location: ' . $baseUrl . '&tab=modules');
            exit;
        }
        $error = is_string($saveResult) ? $saveResult : 'Could not save.';
    }
    // Module add or edit
    elseif (isset($_POST['module_name']) && trim($_POST['module_name']) !== '') {
        $name = trim($_POST['module_name']);
        $icon = trim($_POST['module_icon'] ?? '');
        $format = trim($_POST['module_format'] ?? '');
        $labelsStr = trim($_POST['module_labels'] ?? '');
        $editId = isset($_POST['module_edit_id']) ? trim($_POST['module_edit_id']) : '';
        $numPlaceholders = substr_count($format, '%s');
        if ($numPlaceholders < 1) {
            $error = 'Format must contain at least one %s.';
        } else {
            $labels = $labelsStr !== '' ? array_map('trim', explode(',', $labelsStr)) : [];
            while (count($labels) < $numPlaceholders) {
                $labels[] = 'Field ' . (count($labels) + 1);
            }
            $fields = array_slice(array_map(function ($l) {
                return ['label' => $l, 'placeholder' => ''];
            }, $labels), 0, $numPlaceholders);
            if ($error === '') {
                if ($editId !== '') {
                    $found = false;
                    foreach ($adminModules as $i => $m) {
                        if (isset($m['id']) && $m['id'] === $editId) {
                            $adminModules[$i] = ['id' => $editId, 'name' => $name, 'icon' => $icon, 'format' => $format, 'fields' => $fields];
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $error = 'Module not found.';
                    }
                } else {
                    $ids = array_filter(array_map(function ($m) {
                        return isset($m['id']) ? $m['id'] : null;
                    }, $adminModules));
                    $n = 1;
                    while (in_array('custom-' . $n, $ids, true)) {
                        $n++;
                    }
                    $adminModules[] = ['id' => 'custom-' . $n, 'name' => $name, 'icon' => $icon, 'format' => $format, 'fields' => $fields];
                }
            }
            if ($error === '') {
                $saveResult = save_config($repoRoot, ['custom_modules' => json_encode($adminModules)]);
                if ($saveResult === true) {
                    $config['custom_modules'] = json_encode($adminModules);
                    header('Location: ' . $baseUrl . '&tab=modules');
                    exit;
                }
                $error = is_string($saveResult) ? $saveResult : 'Could not save.';
            }
        }
        if ($error !== '' && $editId !== '' && isset($fields)) {
            $editModule = ['id' => $editId, 'name' => $name, 'icon' => $icon, 'format' => $format, 'fields' => array_map(function ($f) {
                return ['label' => $f['label'] ?? '', 'placeholder' => ''];
            }, $fields)];
        }
    }
    $fromPresetsForm = isset($_POST['visible_presets']) && is_array($_POST['visible_presets']) && !isset($_POST['update_repo']);
    $newPass = trim($_POST['update_auth_password'] ?? '');
    $hiddenPresets = $config['hidden_presets'] ?? '[]';
    if ($fromPresetsForm || (isset($_POST['tab']) && $_POST['tab'] === 'modules') || (isset($_GET['tab']) && $_GET['tab'] === 'modules')) {
        $visible = isset($_POST['visible_presets']) && is_array($_POST['visible_presets']) ? $_POST['visible_presets'] : [];
        $hidden = array_values(array_diff($defaultPresetIds, $visible));
        if (count($hidden) < count($defaultPresetIds)) {
            $hiddenPresets = json_encode($hidden);
        }
    }
    if ($fromPresetsForm) {
        $presetOrder = isset($_POST['preset_order']) && is_array($_POST['preset_order']) ? $_POST['preset_order'] : [];
        $presetOrder = array_values(array_filter(array_map('trim', $presetOrder)));
        $presetOrder = array_values(array_intersect($presetOrder, $defaultPresetIds));
        if (count($presetOrder) !== count($defaultPresetIds)) {
            $presetOrder = $defaultPresetIds;
        }
        $updates = array_merge($config, [
            'hidden_presets' => $hiddenPresets,
            'preset_order' => json_encode($presetOrder),
        ]);
    } else {
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
            'hidden_presets' => $hiddenPresets,
        ];
    }
    $saveResult = save_config($repoRoot, $updates);
    if ($saveResult === true) {
        $config = array_merge($config, $updates);
        $saved = true;
    } else {
        $error = is_string($saveResult) ? $saveResult : 'Could not save (check data/ is writable).';
    }
}

$pageTitle = 'Admin — Config';
$tab = isset($_POST['tab']) ? $_POST['tab'] : (isset($_GET['tab']) ? $_GET['tab'] : 'updates');
$validTabs = ['updates', 'auth', 'modules'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'updates';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link rel="stylesheet" href="css/admin.css">
</head>
<body class="admin-tab-<?php echo htmlspecialchars($tab); ?>">
  <div class="wrap">
    <h1>Admin — Config</h1>
    <p class="sub">Settings are stored in <code>data/config.sqlite</code>. On first run, values are seeded from <code>config.php</code> if present.</p>
    <p class="sub"><a href="index.php">← Back to QR generator</a></p>

    <nav class="admin-pills" aria-label="Admin sections">
      <a href="<?php echo $baseUrl; ?>&amp;tab=updates" class="admin-pill<?php echo $tab === 'updates' ? ' is-active' : ''; ?>">Updates</a>
      <a href="<?php echo $baseUrl; ?>&amp;tab=auth" class="admin-pill<?php echo $tab === 'auth' ? ' is-active' : ''; ?>">Authentication</a>
      <a href="<?php echo $baseUrl; ?>&amp;tab=modules" class="admin-pill<?php echo $tab === 'modules' ? ' is-active' : ''; ?>">Modules</a>
    </nav>

    <?php if ($saved) { echo '<p class="msg ok">Settings saved.</p>'; } ?>
    <?php if ($error !== '') { echo '<p class="msg err">' . htmlspecialchars($error) . '</p>'; } ?>

    <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=' . $tab); ?>">
      <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
      <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">

      <section class="admin-section" id="admin-updates" aria-hidden="<?php echo $tab !== 'updates' ? 'true' : 'false'; ?>">
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
        </div>
      </section>

      <section class="admin-section" id="admin-auth" aria-hidden="<?php echo $tab !== 'auth' ? 'true' : 'false'; ?>">
        <div class="panel">
          <h2>Update / check login</h2>
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
      </section>

      <button type="submit" class="btn admin-save-main">Save</button>
    </form>

    <section class="admin-section" id="admin-modules" aria-hidden="<?php echo $tab !== 'modules' ? 'true' : 'false'; ?>">
      <div class="panel">
        <h2 class="admin-module-heading">Custom modules <button type="button" class="admin-btn-add-module" id="admin-btn-add-module" aria-label="Add custom module">+ Add</button></h2>
        <p class="sub">These modules appear in the main app for all users. Each has a name, optional icon (emoji or <code>icon-phone</code>), a format string with <code>%s</code> placeholders, and field labels.</p>
        <?php if (count($adminModules) > 0) { ?>
        <ul class="admin-module-list">
          <?php foreach ($adminModules as $idx => $m) {
              $mid = isset($m['id']) ? $m['id'] : '';
              $mname = isset($m['name']) ? $m['name'] : '';
              $mformat = isset($m['format']) ? $m['format'] : '';
              $labelsPreview = isset($m['fields']) && is_array($m['fields']) ? implode(', ', array_column($m['fields'], 'label')) : '';
              $canUp = $idx > 0;
              $canDown = $idx < count($adminModules) - 1;
          ?>
          <li class="admin-module-item">
            <span class="admin-module-order">
              <?php if ($canUp) { ?><a href="<?php echo $baseUrl; ?>&amp;tab=modules&amp;move_module_id=<?php echo rawurlencode($mid); ?>&amp;move_module_direction=up" class="admin-move-btn" aria-label="Move up">↑</a><?php } else { ?><span class="admin-move-placeholder"></span><?php } ?>
              <?php if ($canDown) { ?><a href="<?php echo $baseUrl; ?>&amp;tab=modules&amp;move_module_id=<?php echo rawurlencode($mid); ?>&amp;move_module_direction=down" class="admin-move-btn" aria-label="Move down">↓</a><?php } else { ?><span class="admin-move-placeholder"></span><?php } ?>
            </span>
            <span class="admin-module-info"><strong><?php echo htmlspecialchars($mname); ?></strong> — <code><?php echo htmlspecialchars($mformat); ?></code><?php if ($labelsPreview !== '') { ?> (<?php echo htmlspecialchars($labelsPreview); ?>)<?php } ?></span>
            <span class="admin-module-actions">
              <a href="<?php echo $baseUrl; ?>&amp;tab=modules&amp;edit=<?php echo rawurlencode($mid); ?>" class="admin-module-link">Edit</a>
              <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=modules'); ?>" class="admin-module-delete-form" onsubmit="return confirm('Remove this module?');">
                <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
                <input type="hidden" name="delete_module_id" value="<?php echo htmlspecialchars($mid); ?>">
                <button type="submit" class="admin-module-delete">Delete</button>
              </form>
            </span>
          </li>
          <?php } ?>
        </ul>
        <?php } else { ?>
        <p class="sub">No custom modules yet. Click <strong>+ Add</strong> to create one.</p>
        <?php } ?>
        <?php if ($editModule) { ?>
        <h3 class="admin-module-form-title">Edit module <a href="<?php echo $baseUrl; ?>&amp;tab=modules" class="admin-module-cancel">Cancel</a></h3>
        <?php if ($error !== '' && isset($_POST['module_name'])) { echo '<p class="msg err">' . htmlspecialchars($error) . '</p>'; } ?>
        <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=modules'); ?>" class="admin-module-form">
          <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
          <input type="hidden" name="module_edit_id" value="<?php echo htmlspecialchars($editModule['id'] ?? ''); ?>">
          <label for="module_name">Name</label>
          <input type="text" id="module_name" name="module_name" value="<?php echo htmlspecialchars($editModule['name'] ?? ''); ?>" placeholder="e.g. Phone" required autocomplete="off">
          <label for="module_icon">Icon (optional)</label>
          <input type="text" id="module_icon" name="module_icon" value="<?php echo htmlspecialchars($editModule['icon'] ?? ''); ?>" placeholder="&#x1F4DE; or icon-phone" autocomplete="off">
          <label for="module_format">Format (use %s for each field)</label>
          <input type="text" id="module_format" name="module_format" value="<?php echo htmlspecialchars($editModule['format'] ?? ''); ?>" placeholder="tel:%s" required autocomplete="off">
          <label for="module_labels">Field labels (comma-separated)</label>
          <input type="text" id="module_labels" name="module_labels" value="<?php echo $editModule && !empty($editModule['fields']) ? htmlspecialchars(implode(', ', array_column($editModule['fields'], 'label'))) : ''; ?>" placeholder="e.g. Phone number" autocomplete="off">
          <button type="submit" class="btn">Update module</button>
        </form>
        <?php } elseif ($error !== '' && isset($_POST['module_name'])) { ?>
        <p class="msg err"><?php echo htmlspecialchars($error); ?></p>
        <?php } ?>
      </div>

      <div class="admin-modal-overlay" id="admin-module-modal" role="dialog" aria-labelledby="admin-module-modal-title" aria-modal="true" hidden>
        <div class="admin-modal">
          <h3 id="admin-module-modal-title">Add custom module</h3>
          <p class="sub">Use <code>%s</code> in the format for each field (e.g. <code>tel:%s</code>).</p>
          <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=modules'); ?>" id="admin-module-modal-form">
            <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
            <label for="admin_modal_module_name">Name</label>
            <input type="text" id="admin_modal_module_name" name="module_name" placeholder="e.g. Phone" required autocomplete="off">
            <label for="admin_modal_module_icon">Icon (optional — emoji or icon-phone)</label>
            <input type="text" id="admin_modal_module_icon" name="module_icon" placeholder="&#x1F4DE; or icon-phone" autocomplete="off">
            <label for="admin_modal_module_format">Format (use %s for each field)</label>
            <input type="text" id="admin_modal_module_format" name="module_format" placeholder="tel:%s" required autocomplete="off">
            <label for="admin_modal_module_labels">Field labels (comma-separated)</label>
            <input type="text" id="admin_modal_module_labels" name="module_labels" placeholder="e.g. Phone number" autocomplete="off">
            <div class="admin-modal-actions">
              <button type="button" class="btn admin-modal-cancel" id="admin-module-modal-cancel">Cancel</button>
              <button type="submit" class="btn">Add module</button>
            </div>
          </form>
        </div>
      </div>
      <script>
        (function() {
          var btn = document.getElementById('admin-btn-add-module');
          var modal = document.getElementById('admin-module-modal');
          var cancel = document.getElementById('admin-module-modal-cancel');
          if (!btn || !modal) return;
          function openModal() { modal.removeAttribute('hidden'); }
          function closeModal() { modal.setAttribute('hidden', ''); }
          btn.addEventListener('click', openModal);
          cancel.addEventListener('click', closeModal);
          modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
        })();
      </script>
      <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=modules'); ?>">
        <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
        <input type="hidden" name="tab" value="modules">
        <div class="panel">
          <h2>Default preset tabs</h2>
          <p class="sub">Order with ↑↓. Uncheck to hide from the tab bar for <strong>all users</strong>. At least one must remain visible.</p>
          <ul class="admin-preset-order-list">
          <?php
          $hiddenList = json_decode($config['hidden_presets'] ?? '[]', true);
          if (!is_array($hiddenList)) $hiddenList = [];
          foreach ($presetOrder as $pidx => $pid) {
              $visible = !in_array($pid, $hiddenList, true);
              $label = $defaultPresetLabels[$pid] ?? $pid;
              $canUp = $pidx > 0;
              $canDown = $pidx < count($presetOrder) - 1;
          ?>
            <li class="admin-preset-order-item">
              <span class="admin-module-order">
                <?php if ($canUp) { ?><a href="<?php echo $baseUrl; ?>&amp;tab=modules&amp;move_preset_id=<?php echo rawurlencode($pid); ?>&amp;move_preset_direction=up" class="admin-move-btn" aria-label="Move up">↑</a><?php } else { ?><span class="admin-move-placeholder"></span><?php } ?>
                <?php if ($canDown) { ?><a href="<?php echo $baseUrl; ?>&amp;tab=modules&amp;move_preset_id=<?php echo rawurlencode($pid); ?>&amp;move_preset_direction=down" class="admin-move-btn" aria-label="Move down">↓</a><?php } else { ?><span class="admin-move-placeholder"></span><?php } ?>
              </span>
              <label class="admin-preset-checkbox">
                <input type="checkbox" name="visible_presets[]" value="<?php echo htmlspecialchars($pid); ?>"<?php echo $visible ? ' checked' : ''; ?>>
                <?php echo htmlspecialchars($label); ?>
              </label>
              <input type="hidden" name="preset_order[]" value="<?php echo htmlspecialchars($pid); ?>">
            </li>
          <?php } ?>
          </ul>
        </div>
        <button type="submit" class="btn admin-save-modules">Save</button>
      </form>
    </section>
  </div>
</body>
</html>
