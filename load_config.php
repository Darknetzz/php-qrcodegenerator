<?php
/**
 * Load and save app config from SQLite. On first run (empty DB), seeds from config.php if present.
 * Usage: $config = load_config($repoRoot); then $config['update_repo'], etc.
 */

function load_config(string $repoRoot): array {
    $dataDir = $repoRoot . '/data';
    $dbPath = $dataDir . '/config.sqlite';
    if (!is_dir($dataDir)) {
        if (!@mkdir($dataDir, 0750, true)) {
            return get_default_config();
        }
        $htaccess = $dataDir . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\n");
        }
    }
    try {
        $db = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (Throwable $e) {
        return get_default_config();
    }
    $db->exec(
        "CREATE TABLE IF NOT EXISTS config (k TEXT PRIMARY KEY, v TEXT NOT NULL DEFAULT '')"
    );
    $stmt = $db->query("SELECT k, v FROM config");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
    if ($rows === []) {
        $seed = get_seed_from_config_php($repoRoot);
        $merged = array_merge(get_default_config(), $seed);
        $ins = $db->prepare("INSERT OR REPLACE INTO config (k, v) VALUES (?, ?)");
        foreach ($merged as $k => $v) {
            $ins->execute([$k, (string) $v]);
        }
        $rows = $merged;
    }
    return array_merge(get_default_config(), $rows);
}

function get_default_config(): array {
    return [
        'update_repo' => 'Darknetzz/php-qrcodegenerator',
        'update_ip_allowlist' => '',
        'update_use_basic_auth' => '0',
        'update_auth_user' => '',
        'update_auth_password' => '',
        'update_secret' => '',
        'admin_secret' => '',
    ];
}

function get_seed_from_config_php(string $repoRoot): array {
    $file = $repoRoot . '/config.php';
    if (!is_file($file) || !is_readable($file)) {
        return [];
    }
    $config = @include $file;
    if (!is_array($config)) {
        return [];
    }
    $allowed = array_keys(get_default_config());
    $out = [];
    foreach ($config as $k => $v) {
        if (in_array($k, $allowed, true)) {
            $out[$k] = $v === true ? '1' : ($v === false ? '0' : (string) $v);
        }
    }
    return $out;
}

function save_config(string $repoRoot, array $updates): bool {
    $dbPath = $repoRoot . '/data/config.sqlite';
    if (!is_file($dbPath)) {
        load_config($repoRoot);
    }
    if (!is_file($dbPath)) {
        return false;
    }
    try {
        $db = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (Throwable $e) {
        return false;
    }
    $allowed = array_keys(get_default_config());
    $stmt = $db->prepare("INSERT OR REPLACE INTO config (k, v) VALUES (?, ?)");
    foreach ($updates as $k => $v) {
        if (!in_array($k, $allowed, true)) {
            continue;
        }
        $stmt->execute([$k, $v === true ? '1' : ($v === false ? '0' : (string) $v)]);
    }
    return true;
}

function get_config_db_path(string $repoRoot): string {
    return $repoRoot . '/data/config.sqlite';
}
