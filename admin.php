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
security_headers();
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

$customIds = array_filter(array_map(function ($m) { return isset($m['id']) ? $m['id'] : null; }, $adminModules));
$expectedFullCount = count($defaultPresetIds) + count($customIds);
$moduleOrderRaw = json_decode($config['module_order'] ?? '[]', true);
$fullOrder = [];
if (is_array($moduleOrderRaw) && count($moduleOrderRaw) === $expectedFullCount) {
    $validIds = array_merge($defaultPresetIds, $customIds);
    $fullOrder = array_values(array_intersect($moduleOrderRaw, $validIds));
    if (count($fullOrder) === $expectedFullCount) {
        $fullOrder = array_merge($fullOrder, array_diff($validIds, $fullOrder));
    } else {
        $fullOrder = array_merge($presetOrder, $customIds);
    }
} else {
    $fullOrder = array_merge($presetOrder, $customIds);
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
    if (!csrf_verify('admin_csrf')) {
        $error = 'Invalid security token. Please try again.';
    } else {
    // Module delete
    if (isset($_POST['delete_module_id']) && is_string($_POST['delete_module_id']) && $_POST['delete_module_id'] !== '') {
        $toDelete = $_POST['delete_module_id'];
        $adminModules = array_values(array_filter($adminModules, function ($m) use ($toDelete) {
            return (isset($m['id']) ? $m['id'] : '') !== $toDelete;
        }));
        $newFullOrder = array_values(array_filter($fullOrder, function ($id) use ($toDelete) { return $id !== $toDelete; }));
        $updates = ['custom_modules' => json_encode($adminModules), 'module_order' => json_encode($newFullOrder)];
        $saveResult = save_config($repoRoot, $updates);
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
                    $newId = 'custom-' . $n;
                    $adminModules[] = ['id' => $newId, 'name' => $name, 'icon' => $icon, 'format' => $format, 'fields' => $fields];
                    $fullOrder[] = $newId;
                }
            }
            if ($error === '') {
                $updates = ['custom_modules' => json_encode($adminModules)];
                if (isset($newId)) {
                    $updates['module_order'] = json_encode($fullOrder);
                }
                $saveResult = save_config($repoRoot, $updates);
                if ($saveResult === true) {
                    $config['custom_modules'] = json_encode($adminModules);
                    if (isset($newId)) {
                        $config['module_order'] = json_encode($fullOrder);
                    }
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
    // Custom module visibility only (Section 1: Custom modules)
    elseif (isset($_POST['save_custom_modules_visibility']) && isset($_POST['visible_custom_modules']) && is_array($_POST['visible_custom_modules'])) {
        $allIds = array_filter(array_map(function ($m) { return isset($m['id']) ? $m['id'] : null; }, $adminModules));
        $visible = array_values(array_filter(array_map('trim', $_POST['visible_custom_modules'])));
        $hidden = array_values(array_diff($allIds, $visible));
        $saveResult = save_config($repoRoot, ['hidden_custom_modules' => json_encode($hidden)]);
        if ($saveResult === true) {
            $config['hidden_custom_modules'] = json_encode($hidden);
            header('Location: ' . $baseUrl . '&tab=modules');
            exit;
        }
        $error = is_string($saveResult) ? $saveResult : 'Could not save.';
    }
    // Full module order (Section 3: Order)
    elseif (isset($_POST['save_module_order']) && isset($_POST['full_order']) && is_array($_POST['full_order'])) {
        $order = array_values(array_filter(array_map('trim', $_POST['full_order'])));
        $validIds = array_merge($defaultPresetIds, $customIds);
        $order = array_values(array_intersect($order, $validIds));
        $order = array_merge($order, array_diff($validIds, $order));
        if (count($order) === count($validIds)) {
            $newPresetOrder = array_values(array_intersect($order, $defaultPresetIds));
            $byId = [];
            foreach ($adminModules as $m) {
                if (isset($m['id'])) {
                    $byId[$m['id']] = $m;
                }
            }
            $reorderedCustom = [];
            foreach ($order as $id) {
                if (isset($byId[$id])) {
                    $reorderedCustom[] = $byId[$id];
                }
            }
            $updates = [
                'module_order' => json_encode($order),
                'preset_order' => json_encode($newPresetOrder),
                'custom_modules' => json_encode($reorderedCustom),
            ];
            $saveResult = save_config($repoRoot, $updates);
            if ($saveResult === true) {
                $config = array_merge($config, $updates);
                $presetOrder = $newPresetOrder;
                $adminModules = $reorderedCustom;
                $fullOrder = $order;
                header('Location: ' . $baseUrl . '&tab=modules');
                exit;
            }
            $error = is_string($saveResult) ? $saveResult : 'Could not save.';
        }
    }
    $fromPresetsForm = isset($_POST['save_default_visibility']) && isset($_POST['visible_presets']) && is_array($_POST['visible_presets']);
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
        $saveResult = save_config($repoRoot, ['hidden_presets' => $hiddenPresets]);
        if ($saveResult === true) {
            $config['hidden_presets'] = $hiddenPresets;
            header('Location: ' . $baseUrl . '&tab=modules');
            exit;
        }
        $error = is_string($saveResult) ? $saveResult : 'Could not save.';
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
}

$pageTitle = 'Admin — Config';
$tab = isset($_POST['tab']) ? $_POST['tab'] : (isset($_GET['tab']) ? $_GET['tab'] : 'updates');
$validTabs = ['updates', 'auth', 'modules'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'updates';
}

// Start session before any output so the CSRF token cookie is sent and survives the next POST
csrf_token('admin_csrf');
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
      <input type="hidden" name="key" id="admin-key" value="<?php echo htmlspecialchars($key); ?>">
      <input type="hidden" name="admin_csrf" value="<?php echo htmlspecialchars(csrf_token('admin_csrf')); ?>">
      <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">

      <section class="admin-section" id="admin-updates" aria-hidden="<?php echo $tab !== 'updates' ? 'true' : 'false'; ?>">
        <div class="panel" id="admin-check-updates-panel">
          <h2>Check for updates</h2>
          <p class="admin-update-version" id="admin-current-version">Current version: —</p>
          <p class="admin-update-actions">
            <button type="button" class="btn btn-secondary" id="admin-btn-check-updates" aria-label="Check for updates">Check for updates</button>
            <span class="admin-update-msg" id="admin-update-msg" aria-live="polite"></span>
            <button type="button" class="btn btn-primary" id="admin-btn-upgrade" style="display:none;">Upgrade</button>
          </p>
        </div>
        <div class="panel">
          <h2>Updates</h2>
          <label for="update_repo">GitHub repo (owner/repo) — for zip installs</label>
          <input type="text" id="update_repo" name="update_repo" value="<?php echo htmlspecialchars($config['update_repo'] ?? ''); ?>" placeholder="Darknetzz/php-qrcodegenerator">
          <p class="hint">Git clones use .git/config instead.</p>
        </div>
      </section>

      <section class="admin-section" id="admin-auth" aria-hidden="<?php echo $tab !== 'auth' ? 'true' : 'false'; ?>">
        <div class="panel">
          <h2>Access control</h2>
          <label for="update_ip_allowlist">IP allowlist (comma-separated, optional)</label>
          <input type="text" id="update_ip_allowlist" name="update_ip_allowlist" value="<?php echo htmlspecialchars($config['update_ip_allowlist'] ?? ''); ?>" placeholder="127.0.0.1, 10.0.0.0/24">
          <p class="hint">Allowed IPs can use the app and updates without logging in. Leave empty if you use login only.</p>
          <div class="checkbox-row">
            <label>
              <input type="checkbox" name="update_allow_app_any_ip" value="1" <?php echo empty($config['update_allow_app_any_ip']) || $config['update_allow_app_any_ip'] === '0' ? '' : 'checked'; ?>>
              Allow app usage from any IP (uncheck to restrict main app to allowlist)
            </label>
          </div>
        </div>
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
      <?php
      $hiddenCustomList = json_decode($config['hidden_custom_modules'] ?? '[]', true);
      if (!is_array($hiddenCustomList)) {
          $hiddenCustomList = [];
      }
      $hiddenList = json_decode($config['hidden_presets'] ?? '[]', true);
      if (!is_array($hiddenList)) {
          $hiddenList = [];
      }
      ?>

      <!-- 1. Custom modules: add, edit, delete + visibility -->
      <div class="panel">
        <h2 class="admin-module-heading">1. Custom modules <button type="button" class="admin-btn-add-module" id="admin-btn-add-module" aria-label="Add custom module">+ Add</button></h2>
        <p class="sub">Add, edit, or remove custom modules. Uncheck <strong>Show</strong> to hide from the tab bar. Each has a name, optional icon (emoji or <code>icon-phone</code>), a format string with <code>%s</code> placeholders, and field labels.</p>
        <?php if (count($adminModules) > 0) { ?>
        <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=modules'); ?>" id="admin-custom-modules-form">
          <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
          <input type="hidden" name="admin_csrf" value="<?php echo htmlspecialchars(csrf_token('admin_csrf')); ?>">
          <input type="hidden" name="save_custom_modules_visibility" value="1">
          <ul class="admin-module-list" id="admin-custom-modules-list" aria-label="Custom modules">
          <?php foreach ($adminModules as $m) {
              $mid = isset($m['id']) ? $m['id'] : '';
              $mname = isset($m['name']) ? $m['name'] : '';
              $mformat = isset($m['format']) ? $m['format'] : '';
              $labelsPreview = isset($m['fields']) && is_array($m['fields']) ? implode(', ', array_column($m['fields'], 'label')) : '';
              $visible = !in_array($mid, $hiddenCustomList, true);
          ?>
          <li class="admin-module-item">
            <label class="admin-module-visible">
              <input type="checkbox" name="visible_custom_modules[]" value="<?php echo htmlspecialchars($mid); ?>"<?php echo $visible ? ' checked' : ''; ?>>
              <span class="admin-module-visible-label">Show</span>
            </label>
            <span class="admin-module-info"><strong><?php echo htmlspecialchars($mname); ?></strong> — <code><?php echo htmlspecialchars($mformat); ?></code><?php if ($labelsPreview !== '') { ?> (<?php echo htmlspecialchars($labelsPreview); ?>)<?php } ?></span>
            <span class="admin-module-actions">
              <a href="<?php echo $baseUrl; ?>&amp;tab=modules&amp;edit=<?php echo rawurlencode($mid); ?>" class="admin-module-link">Edit</a>
              <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=modules'); ?>" class="admin-module-delete-form" onsubmit="return confirm('Remove this module?');">
                <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
                <input type="hidden" name="admin_csrf" value="<?php echo htmlspecialchars(csrf_token('admin_csrf')); ?>">
                <input type="hidden" name="delete_module_id" value="<?php echo htmlspecialchars($mid); ?>">
                <button type="submit" class="admin-module-delete">Delete</button>
              </form>
            </span>
          </li>
          <?php } ?>
          </ul>
          <button type="submit" class="btn admin-save-modules">Save visibility</button>
        </form>
        <?php } else { ?>
        <p class="sub">No custom modules yet. Click <strong>+ Add</strong> to create one.</p>
        <?php } ?>
        <?php if ($editModule) { ?>
        <h3 class="admin-module-form-title">Edit module <a href="<?php echo $baseUrl; ?>&amp;tab=modules" class="admin-module-cancel">Cancel</a></h3>
        <?php if ($error !== '' && isset($_POST['module_name'])) { echo '<p class="msg err">' . htmlspecialchars($error) . '</p>'; } ?>
        <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=modules'); ?>" class="admin-module-form">
          <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
          <input type="hidden" name="admin_csrf" value="<?php echo htmlspecialchars(csrf_token('admin_csrf')); ?>">
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
            <input type="hidden" name="admin_csrf" value="<?php echo htmlspecialchars(csrf_token('admin_csrf')); ?>">
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

      <!-- 2. Default modules: hide/show toggles only -->
      <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=modules'); ?>" autocomplete="off">
        <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
        <input type="hidden" name="admin_csrf" value="<?php echo htmlspecialchars(csrf_token('admin_csrf')); ?>">
        <input type="hidden" name="save_default_visibility" value="1">
        <div class="panel">
          <h2>2. Default modules</h2>
          <p class="sub">Show or hide built-in preset tabs for <strong>all users</strong>. At least one must remain visible.</p>
          <ul class="admin-preset-visibility-list" aria-label="Default presets visibility">
          <?php foreach ($defaultPresetIds as $pid) {
              $visible = !in_array($pid, $hiddenList, true);
              $label = $defaultPresetLabels[$pid] ?? $pid;
          ?>
            <li class="admin-preset-visibility-item">
              <label class="admin-preset-checkbox">
                <input type="checkbox" name="visible_presets[]" value="<?php echo htmlspecialchars($pid); ?>"<?php echo $visible ? ' checked' : ''; ?>>
                <?php echo htmlspecialchars($label); ?>
              </label>
            </li>
          <?php } ?>
          </ul>
          <button type="submit" class="btn admin-save-modules">Save visibility</button>
        </div>
      </form>

      <!-- 3. Order: combined list with drag-and-drop -->
      <form method="post" action="<?php echo htmlspecialchars($baseUrl . '&tab=modules'); ?>" id="admin-module-order-form" autocomplete="off">
        <input type="hidden" name="key" value="<?php echo htmlspecialchars($key); ?>">
        <input type="hidden" name="admin_csrf" value="<?php echo htmlspecialchars(csrf_token('admin_csrf')); ?>">
        <input type="hidden" name="save_module_order" value="1">
        <div class="panel">
          <h2>3. Order</h2>
          <p class="sub">Drag rows to set the tab order in the main app. Order applies to both default and custom modules.</p>
          <ul class="admin-module-order-list admin-draggable-list" id="admin-module-order-list" aria-label="Module order">
          <?php
          $customById = [];
          foreach ($adminModules as $m) {
              if (isset($m['id'])) {
                  $customById[$m['id']] = $m;
              }
          }
          foreach ($fullOrder as $oid) {
              $isDefault = in_array($oid, $defaultPresetIds, true);
              $label = $isDefault ? ($defaultPresetLabels[$oid] ?? $oid) : (isset($customById[$oid]) ? $customById[$oid]['name'] : $oid);
          ?>
            <li class="admin-module-order-item admin-draggable-item" data-module-id="<?php echo htmlspecialchars($oid); ?>" draggable="true">
              <span class="admin-drag-handle" aria-label="Drag to reorder">⋮⋮</span>
              <input type="hidden" name="full_order[]" value="<?php echo htmlspecialchars($oid); ?>">
              <span class="admin-module-order-label"><?php echo htmlspecialchars($label); ?><?php if (!$isDefault) { ?> <em>(custom)</em><?php } ?></span>
            </li>
          <?php } ?>
          </ul>
          <button type="submit" class="btn admin-save-modules">Save order</button>
        </div>
      </form>
      <script>
        (function dragDrop() {
          var list = document.getElementById('admin-module-order-list');
          if (!list) return;
          var items = list.querySelectorAll('.admin-draggable-item');
          var dragged = null;
          function clearDropHighlight() {
            var all = list.querySelectorAll('.admin-draggable-item');
            for (var i = 0; i < all.length; i++) {
              if (all[i] && all[i].classList) all[i].classList.remove('admin-drag-over');
            }
          }
          for (var i = 0; i < items.length; i++) {
            var item = items[i];
            if (!item) continue;
            item.setAttribute('draggable', 'true');
            item.addEventListener('dragstart', function(e) {
              var el = e.currentTarget;
              dragged = el;
              if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', (el.getAttribute && el.getAttribute('data-module-id')) || '');
              }
              if (el.classList) el.classList.add('admin-dragging');
            });
            item.addEventListener('dragend', function(e) {
              var el = e.currentTarget;
              if (el && el.classList) el.classList.remove('admin-dragging');
              clearDropHighlight();
              dragged = null;
            });
          }
          list.addEventListener('dragover', function(e) {
            e.preventDefault();
            if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
            if (!dragged) return;
            var target = e.target && e.target.closest ? e.target.closest('.admin-draggable-item') : null;
            if (target && target !== dragged) {
              clearDropHighlight();
              target.classList.add('admin-drag-over');
            }
          });
          list.addEventListener('dragleave', function(e) {
            if (!e.relatedTarget || !list.contains(e.relatedTarget)) clearDropHighlight();
          });
          list.addEventListener('drop', function(e) {
            e.preventDefault();
            clearDropHighlight();
            var target = e.target && e.target.closest ? e.target.closest('.admin-draggable-item') : null;
            if (dragged && target && target !== dragged) {
              list.insertBefore(dragged, target);
            }
            dragged = null;
          });
        })();
      </script>
    </section>
  </div>
  <script>
  (function adminUpdatesUi() {
    var keyEl = document.getElementById('admin-key');
    var versionEl = document.getElementById('admin-current-version');
    var msgEl = document.getElementById('admin-update-msg');
    var checkBtn = document.getElementById('admin-btn-check-updates');
    var upgradeBtn = document.getElementById('admin-btn-upgrade');
    if (!keyEl || !checkBtn) return;

    function getKey() { return keyEl ? keyEl.value : ''; }
    function setMsg(text, className) {
      if (!msgEl) return;
      msgEl.textContent = text || '';
      msgEl.className = 'admin-update-msg' + (className ? ' ' + className : '');
    }

    function checkUrl() {
      var k = getKey();
      return 'updates.php?action=check' + (k ? '&key=' + encodeURIComponent(k) : '');
    }

    checkBtn.addEventListener('click', function() {
      checkBtn.disabled = true;
      setMsg('Checking…', 'loading');
      upgradeBtn.style.display = 'none';
      fetch(checkUrl(), { credentials: 'include' })
        .then(function(r) { return r.json().then(function(d) { return { status: r.status, data: d }; }); })
        .then(function(r) {
          if (r.status === 401 || r.status === 403) {
            setMsg(r.data && r.data.error ? r.data.error : 'Access denied.', 'error');
            return;
          }
          var d = r.data;
          if (d && d.error) {
            setMsg(d.error, 'error');
            return;
          }
          if (d && d.currentVersion) {
            versionEl.textContent = 'Current version: ' + d.currentVersion;
          }
          if (d && d.updateAvailable && d.latestVersion) {
            setMsg('Update available: ' + d.latestVersion, 'has-update');
            upgradeBtn.textContent = d.installType === 'zip' ? 'Download latest' : 'Upgrade (git pull)';
            upgradeBtn.dataset.installType = d.installType || 'git';
            upgradeBtn.dataset.releaseUrl = d.releaseUrl || '';
            upgradeBtn.style.display = 'inline-block';
          } else {
            setMsg('You’re up to date.', '');
          }
        })
        .catch(function() { setMsg('Check failed.', 'error'); })
        .finally(function() { checkBtn.disabled = false; });
    });

    upgradeBtn.addEventListener('click', function() {
      if (upgradeBtn.dataset.installType === 'zip' && upgradeBtn.dataset.releaseUrl) {
        window.open(upgradeBtn.dataset.releaseUrl, '_blank', 'noopener,noreferrer');
        setMsg('Open the release page, download the zip, and replace the files.', 'has-update');
        return;
      }
      upgradeBtn.disabled = true;
      setMsg('Upgrading…', 'loading');
      var form = new FormData();
      form.append('action', 'upgrade');
      var k = getKey();
      if (k) form.append('key', k);
      fetch('updates.php', { method: 'POST', body: form, credentials: 'include' })
        .then(function(r) { return r.json().then(function(d) { return { status: r.status, data: d }; }); })
        .then(function(r) {
          var d = r.data;
          if (r.status === 401 || r.status === 403) {
            setMsg(d && d.error ? d.error : 'Unauthorized.', 'error');
            return;
          }
          if (d && d.noGit && d.releaseUrl) {
            window.open(d.releaseUrl, '_blank', 'noopener,noreferrer');
            setMsg('Open the release page, download the zip, and replace the files.', 'has-update');
            return;
          }
          if (d && d.success) {
            setMsg('Upgrade complete. Reload the page.', 'has-update');
            upgradeBtn.style.display = 'none';
            if (versionEl && d.currentVersion) versionEl.textContent = 'Current version: ' + d.currentVersion;
          } else {
            setMsg((d && d.error ? d.error : 'Upgrade failed.') + (d && d.output ? ' ' + d.output : ''), 'error');
          }
        })
        .catch(function() { setMsg('Upgrade request failed.', 'error'); })
        .finally(function() { upgradeBtn.disabled = false; });
    });

    // Load current version on Updates tab when visible
    if (versionEl && getKey()) {
      fetch(checkUrl(), { credentials: 'include' })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(d) {
          if (d && d.currentVersion) versionEl.textContent = 'Current version: ' + d.currentVersion;
        })
        .catch(function() {});
    }
  })();
  </script>
</body>
</html>
