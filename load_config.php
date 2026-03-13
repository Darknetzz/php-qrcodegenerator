<?php
/**
 * Load and save app config from SQLite. On first run (empty DB), seeds from config.php if present.
 * Usage: $config = load_config($repoRoot); then $config['update_repo'], etc.
 */

/** Send security-related HTTP headers. Call once per request before any output. */
function security_headers(): void {
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/** Generate a CSRF token and store in session. Start session if needed. Returns token. */
function csrf_token(string $name = 'csrf_token'): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
    }
    if (empty($_SESSION[$name])) {
        $_SESSION[$name] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$name];
}

/** Verify CSRF token from request. Returns true if valid. */
function csrf_verify(string $name = 'csrf_token'): bool {
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    $token = $_SESSION[$name] ?? '';
    $given = trim((string) ($_POST[$name] ?? ''));
    return $token !== '' && $given !== '' && hash_equals($token, $given);
}

/** Check if IP matches a CIDR or exact address (e.g. "10.0.0.0/24" or "127.0.0.1") */
function ip_in_list(string $ip, string $list): bool {
    $ip = trim($ip);
    $addrs = array_map('trim', explode(',', $list));
    foreach ($addrs as $addr) {
        if ($addr === '') {
            continue;
        }
        if ($addr === $ip) {
            return true;
        }
        if (strpos($addr, '/') !== false) {
            [$subnet, $bits] = explode('/', $addr, 2);
            $bits = (int) $bits;
            $ipLong = ip2long($ip);
            $subnetLong = ip2long(trim($subnet));
            if ($ipLong === false || $subnetLong === false) {
                continue;
            }
            $mask = -1 << (32 - $bits);
            if (($ipLong & $mask) === ($subnetLong & $mask)) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Enforce app-level IP allowlist when "allow app any IP" is off.
 * Call from index.php and generate.php. Exits with 403 if denied.
 */
function require_app_access(string $repoRoot): void {
    $config = load_config($repoRoot);
    $allowAny = !empty($config['update_allow_app_any_ip']) && $config['update_allow_app_any_ip'] !== '0';
    if ($allowAny) {
        return;
    }
    $allowlist = trim($config['update_ip_allowlist'] ?? '');
    if ($allowlist === '') {
        return;
    }
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (ip_in_list($remote, $allowlist)) {
        return;
    }
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Access denied (IP not allowed)';
    exit;
}

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
        'update_channel' => 'stable',
        'update_ip_allowlist' => '',
        'update_allow_app_any_ip' => '1',
        'update_use_basic_auth' => '0',
        'update_require_login_always' => '0',
        'update_auth_user' => '',
        'update_auth_password' => '',
        'update_secret' => '',
        'admin_secret' => '',
        'hidden_presets' => '[]',
        'hidden_custom_modules' => '[]',
        'preset_order' => '[]',
        'module_order' => '[]',
        'custom_modules' => '[]',
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

/**
 * @return bool|string true on success, or an error message string on failure
 */
function save_config(string $repoRoot, array $updates) {
    $dataDir = $repoRoot . '/data';
    $dbPath = $dataDir . '/config.sqlite';
    if (!is_file($dbPath)) {
        if (!is_dir($dataDir) && !@mkdir($dataDir, 0750, true)) {
            return 'data/ directory could not be created';
        }
        if (!is_dir($dataDir)) {
            return 'data/ directory is missing';
        }
        load_config($repoRoot);
    }
    if (!is_file($dbPath)) {
        return 'Config database could not be created (check data/ is writable)';
    }
    try {
        $db = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (Throwable $e) {
        return 'Config database is not writable';
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
