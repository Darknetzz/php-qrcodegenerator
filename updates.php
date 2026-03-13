<?php
/**
 * Update check (GitHub releases) and upgrade (git pull or release page) endpoint.
 * Config is loaded from SQLite (data/config.sqlite), seeded from config.php on first run.
 * Access control: IP allowlist, login (username/password), and upgrade secret are set in admin or config.php.
 *
 * GET  ?action=hidden-presets → { hiddenPresets } — ids of default presets to hide (for all users)
 * GET  ?action=config-status → { configured } — whether IP/login is set; if set, requires auth (session)
 * POST ?action=login (username, password) → session login; returns { success } or 401
 * POST ?action=logout → clear session
 * POST ?action=save-initial-config → save first-time setup (only when not yet configured)
 * GET  ?action=check  → { currentVersion, latestVersion, updateAvailable, releaseUrl, installType, updateChannel }
 * POST ?action=upgrade [&secret=...] → { success, output, error } or { noGit, releaseUrl } for zip
 */
require_once __DIR__ . '/load_config.php';
security_headers();
header('Content-Type: application/json; charset=utf-8');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $isHttps,
    ]);
}

$repoRoot = realpath(__DIR__);
if ($repoRoot === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid app root'], JSON_UNESCAPED_SLASHES);
    exit;
}
$config = load_config($repoRoot);
$isGit = is_dir($repoRoot . '/.git');

/** Whether access control (IP allowlist or login) is configured. */
function is_access_configured(array $config): bool {
    $allowlist = trim($config['update_ip_allowlist'] ?? '');
    $useBasic = !empty($config['update_use_basic_auth']) && $config['update_use_basic_auth'] !== '0';
    return $allowlist !== '' || $useBasic;
}

/** True if request has valid admin or update secret in key= (allows check/upgrade from Admin panel). */
function has_admin_key(array $config): bool {
    $key = trim($_REQUEST['key'] ?? '');
    if ($key === '') {
        return false;
    }
    $admin = trim($config['admin_secret'] ?? '');
    $update = trim($config['update_secret'] ?? '');
    return ($admin !== '' && hash_equals($admin, $key)) || ($update !== '' && hash_equals($update, $key));
}

/** Enforce IP allowlist and/or login (session). Exits with 401/403 if denied. */
function require_updates_access(array $config): void {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    $allowlist = trim($config['update_ip_allowlist'] ?? '');
    $ipAllowed = $allowlist === '' || ip_in_list($remote, $allowlist);
    if ($allowlist !== '' && !$ipAllowed) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied (IP not allowed)'], JSON_UNESCAPED_SLASHES);
        exit;
    }
    $useLogin = !empty($config['update_use_basic_auth']) && $config['update_use_basic_auth'] !== '0';
    $requireLoginAlways = !empty($config['update_require_login_always']) && $config['update_require_login_always'] !== '0';
    $mustLogin = $useLogin && ($requireLoginAlways || $allowlist === '' || !$ipAllowed);
    if ($mustLogin) {
        $user = trim($config['update_auth_user'] ?? '');
        $pass = trim($config['update_auth_password'] ?? '');
        if ($pass === '' && getenv('UPDATE_AUTH_PASSWORD') !== false) {
            $pass = (string) getenv('UPDATE_AUTH_PASSWORD');
        }
        if ($user === '' || $pass === '') {
            http_response_code(500);
            echo json_encode(['error' => 'Login configured but user/password not set'], JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (empty($_SESSION['qr_authenticated'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required'], JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

$action = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : '';

if ($action === 'hidden-presets') {
    $raw = $config['hidden_presets'] ?? '[]';
    $list = json_decode($raw, true);
    if (!is_array($list)) {
        $list = [];
    }
    $rawCustom = $config['hidden_custom_modules'] ?? '[]';
    $listCustom = json_decode($rawCustom, true);
    if (!is_array($listCustom)) {
        $listCustom = [];
    }
    json_exit(['hiddenPresets' => $list, 'hiddenCustomModules' => $listCustom]);
}

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_exit(['error' => 'Use POST'], 405);
    }
    $user = trim($config['update_auth_user'] ?? '');
    $pass = trim($config['update_auth_password'] ?? '');
    if ($pass === '' && getenv('UPDATE_AUTH_PASSWORD') !== false) {
        $pass = (string) getenv('UPDATE_AUTH_PASSWORD');
    }
    $givenUser = trim($_POST['username'] ?? '');
    $givenPass = (string) ($_POST['password'] ?? '');
    if ($user === '' || $pass === '' || $givenUser === '' || $givenPass === '' || !hash_equals($user, $givenUser) || !hash_equals($pass, $givenPass)) {
        http_response_code(401);
        json_exit(['error' => 'Invalid username or password']);
    }
    session_regenerate_id(true);
    $_SESSION['qr_authenticated'] = true;
    json_exit(['success' => true]);
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    json_exit(['success' => true]);
}

if ($action === 'config-status') {
    if (is_access_configured($config)) {
        require_updates_access($config);
    }
    $configured = is_access_configured($config);
    $loggedIn = !empty($_SESSION['qr_authenticated']);
    $out = ['configured' => $configured, 'loggedIn' => $loggedIn];
    if (!$configured) {
        $out['setupToken'] = csrf_token('setup_csrf');
    }
    json_exit($out);
}

if ($action === 'save-initial-config') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_exit(['error' => 'Use POST'], 405);
    }
    if (!csrf_verify('setup_csrf')) {
        json_exit(['error' => 'Invalid security token. Reload the page and try again.'], 403);
    }
    if (is_access_configured($config)) {
        json_exit(['error' => 'Already configured'], 400);
    }
    $allowlist = trim($_POST['update_ip_allowlist'] ?? '');
    $useBasic = !empty($_POST['update_use_basic_auth']);
    $authUser = trim($_POST['update_auth_user'] ?? '');
    $authPass = trim($_POST['update_auth_password'] ?? '');
    if ($allowlist === '' && (!$useBasic || $authUser === '' || $authPass === '')) {
        json_exit(['error' => 'Set at least an IP allowlist or enable login with username and password'], 400);
    }
    $requireLoginAlways = !empty($_POST['update_require_login_always']);
    $allowAppAnyIp = !empty($_POST['update_allow_app_any_ip']);
    $updates = [
        'update_repo' => trim($config['update_repo'] ?? ''),
        'update_ip_allowlist' => $allowlist,
        'update_allow_app_any_ip' => $allowAppAnyIp ? '1' : '0',
        'update_use_basic_auth' => $useBasic ? '1' : '0',
        'update_require_login_always' => $requireLoginAlways ? '1' : '0',
        'update_auth_user' => $authUser,
        'update_auth_password' => $authPass,
        'update_secret' => trim($config['update_secret'] ?? ''),
        'admin_secret' => trim($config['admin_secret'] ?? ''),
    ];
    $saveResult = save_config($repoRoot, $updates);
    if ($saveResult !== true) {
        json_exit(['error' => 'Could not save config. ' . (is_string($saveResult) ? $saveResult : 'Check data/ is writable.')], 500);
    }
    json_exit(['success' => true, 'configured' => true]);
}

if (in_array($action, ['check', 'upgrade'], true)) {
    if (!has_admin_key($config)) {
        require_updates_access($config);
    }
}

/** Parse origin URL from .git/config → [owner, repo] for GitHub, or null */
function get_github_repo(string $repoRoot): ?array {
    $config = $repoRoot . '/.git/config';
    if (!is_readable($config)) {
        return null;
    }
    $content = @file_get_contents($config);
    if ($content === false) {
        return null;
    }
    if (!preg_match('/^\s*url\s*=\s*(.+)$/m', $content, $m)) {
        return null;
    }
    $url = trim($m[1]);
    // git@github.com:owner/repo.git or https://github.com/owner/repo.git
    if (preg_match('#(?:git@github\.com:|https?://(?:[^/]+\.)?github\.com/)([^/]+)/([^/]+?)(?:\.git)?$#', $url, $m)) {
        return [$m[1], preg_replace('/\.git$/', '', $m[2])];
    }
    return null;
}

/** Normalize git describe to "1.0.0" on tag or "1.0.0-<shortcommit>" when not on a tag */
function normalize_git_version(string $describe): string {
    $describe = trim($describe);
    if ($describe === '') {
        return 'unknown';
    }
    $describe = preg_replace('/^v/i', '', $describe);
    // v1.0.0-2-gabc1234 → 1.0.0-abc1234
    if (preg_match('/^(.+)-(\d+)-g([a-f0-9]+)$/i', $describe, $m)) {
        return $m[1] . '-' . $m[3];
    }
    return $describe;
}

/** Read version from VERSION file (first non-comment line) or return null */
function read_version_file(string $repoRoot): ?string {
    $versionFile = $repoRoot . '/VERSION';
    if (!is_file($versionFile) || !is_readable($versionFile)) {
        return null;
    }
    $raw = @file_get_contents($versionFile);
    if ($raw === false) {
        return null;
    }
    $firstLine = strtok($raw, "\n");
    $v = $firstLine !== false ? trim($firstLine) : '';
    if ($v === '' || $v[0] === '#') {
        return null;
    }
    return $v;
}

/** Current version: from git if available (1.0.0 or 1.0.0-<commit>), else from VERSION file */
function get_local_version(string $repoRoot, bool $isGit): string {
    if ($isGit) {
        $cmd = sprintf(
            'cd %s && git describe --tags --always 2>/dev/null || git rev-parse --short HEAD 2>/dev/null',
            escapeshellarg($repoRoot)
        );
        $out = @shell_exec($cmd);
        if ($out !== null) {
            $v = normalize_git_version(trim($out));
            if ($v !== 'unknown') {
                return $v;
            }
        }
    }
    return read_version_file($repoRoot) ?? 'unknown';
}

/** Fetch latest release from GitHub API; fallback to latest tag. Returns [tag_name, html_url] or null */
function get_latest_release(string $owner, string $repo): ?array {
    $url = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: PHP-QR-Updater\r\nAccept: application/vnd.github.v3+json\r\n",
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw !== false) {
        $data = json_decode($raw, true);
        if (is_array($data) && isset($data['tag_name'])) {
            return [
                $data['tag_name'],
                $data['html_url'] ?? "https://github.com/{$owner}/{$repo}/releases",
            ];
        }
    }
    // No releases: try tags (newest first by creator date not guaranteed; we take first as fallback)
    $tagsUrl = "https://api.github.com/repos/{$owner}/{$repo}/tags";
    $raw = @file_get_contents($tagsUrl, false, $ctx);
    if ($raw !== false) {
        $tags = json_decode($raw, true);
        if (is_array($tags) && isset($tags[0]['name'])) {
            $tag = $tags[0]['name'];
            return [$tag, "https://github.com/{$owner}/{$repo}/releases/tag/{$tag}"];
        }
    }
    return null;
}

/** Normalize version string for comparison (strip prefix, allow semver) */
function normalize_version(string $v): string {
    $v = trim($v);
    $v = preg_replace('/^v/i', '', $v);
    return $v;
}

/** True if $remote is considered newer than $local (simple semver/numeric compare) */
function is_newer(string $remote, string $local): bool {
    $r = normalize_version($remote);
    $l = normalize_version(explode('-', $local)[0]); // ignore -2-gabc1234
    if ($r === $l) {
        return false;
    }
    return version_compare($r, $l, '>');
}

function json_exit(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function upgrade_allowed(array $config): bool {
    if (has_admin_key($config)) {
        return true;
    }
    $secret = trim($config['update_secret'] ?? '');
    if ($secret === '') {
        $envSecret = getenv('UPDATE_SECRET');
        if ($envSecret === false || $envSecret === '') {
            return true;
        }
        $secret = $envSecret;
    }
    $given = $_REQUEST['secret'] ?? $_REQUEST['key'] ?? $_SERVER['HTTP_X_UPDATE_SECRET'] ?? '';
    $given = trim(is_string($given) ? $given : '');
    return $given !== '' && hash_equals($secret, $given);
}

/** Get default branch (e.g. main) from origin. */
function get_default_branch(string $repoRoot): string {
    $head = $repoRoot . '/.git/refs/remotes/origin/HEAD';
    if (is_readable($head)) {
        $content = @file_get_contents($head);
        if ($content !== false && preg_match('#ref: refs/remotes/origin/(.+)#', trim($content), $m)) {
            $b = trim($m[1]);
            if ($b !== '') {
                return $b;
            }
        }
    }
    return 'main';
}

/**
 * For dev channel: fetch origin and return [version_describe, commit_short] of origin/<branch>.
 * Returns null if fetch or describe fails.
 */
function get_dev_latest(string $repoRoot, string $branch): ?array {
    $safeBranch = preg_replace('/[^a-zA-Z0-9._\-]/', '', $branch) ?: 'main';
    $ref = 'origin/' . $safeBranch;
    $escRoot = escapeshellarg($repoRoot);
    $escRef = escapeshellarg($ref);
    @shell_exec("cd {$escRoot} && git fetch origin 2>/dev/null");
    $cmd = "cd {$escRoot} && git describe --tags --always {$escRef} 2>/dev/null";
    $out = @shell_exec($cmd);
    if ($out === null) {
        return null;
    }
    $describe = trim($out);
    if ($describe === '') {
        return null;
    }
    $cmd2 = "cd {$escRoot} && git rev-parse --short {$escRef} 2>/dev/null";
    $out2 = @shell_exec($cmd2);
    $short = ($out2 !== null && trim($out2) !== '') ? trim($out2) : null;
    return [$describe, $short];
}

/** Resolve [owner, repo] for GitHub API: from git config or from update_repo (e.g. zip install) */
function resolve_repo(string $repoRoot, bool $isGit, array $config): ?array {
    if ($isGit) {
        return get_github_repo($repoRoot);
    }
    $repo = trim($config['update_repo'] ?? '');
    if ($repo === '') {
        return null;
    }
    $slug = preg_replace('/\s+/', '', $repo);
    if (preg_match('#^([^/]+)/([^/]+)$#', $slug, $m)) {
        return [$m[1], $m[2]];
    }
    return null;
}

if ($action === 'check') {
    $current = get_local_version($repoRoot, $isGit);
    $channel = trim($config['update_channel'] ?? 'stable');
    if ($channel !== 'stable' && $channel !== 'dev') {
        $channel = 'stable';
    }
    $github = resolve_repo($repoRoot, $isGit, $config);
    $latestVersion = null;
    $releaseUrl = null;
    $updateAvailable = false;

    if ($channel === 'dev' && $isGit) {
        $branch = get_default_branch($repoRoot);
        $devLatest = get_dev_latest($repoRoot, $branch);
        if ($devLatest !== null) {
            [$latestVersion] = $devLatest;
            $releaseUrl = null;
            $currentRev = @shell_exec(sprintf(
                'cd %s && git rev-parse --short HEAD 2>/dev/null',
                escapeshellarg($repoRoot)
            ));
            $originRev = $devLatest[1] ?? null;
            if ($currentRev !== null && $originRev !== null) {
                $updateAvailable = trim($currentRev) !== trim($originRev);
            } else {
                $updateAvailable = $current !== $latestVersion;
            }
        }
    } elseif ($github !== null) {
        [$owner, $repo] = $github;
        $release = get_latest_release($owner, $repo);
        if ($release !== null) {
            [$latestVersion, $releaseUrl] = $release;
            $updateAvailable = is_newer($latestVersion, $current);
        }
    }

    json_exit([
        'currentVersion' => $current,
        'latestVersion' => $latestVersion,
        'updateAvailable' => $updateAvailable,
        'releaseUrl' => $releaseUrl,
        'installType' => $isGit ? 'git' : 'zip',
        'updateChannel' => $channel,
    ]);
}

if ($action === 'upgrade') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_exit(['error' => 'Use POST for upgrade'], 405);
    }
    if (!upgrade_allowed($config)) {
        json_exit(['error' => 'Unauthorized'], 403);
    }

    if (!$isGit) {
        $github = resolve_repo($repoRoot, false, $config);
        $releaseUrl = null;
        if ($github !== null) {
            [$owner, $repo] = $github;
            $release = get_latest_release($owner, $repo);
            if ($release !== null) {
                $releaseUrl = $release[1];
            }
        }
        json_exit([
            'success' => false,
            'noGit' => true,
            'releaseUrl' => $releaseUrl,
            'message' => 'Not a git clone. Download the latest release and replace the files.',
        ], 200);
    }

    $channel = trim($config['update_channel'] ?? 'stable');
    if ($channel !== 'stable' && $channel !== 'dev') {
        $channel = 'stable';
    }

    if ($channel === 'stable') {
        $github = resolve_repo($repoRoot, true, $config);
        if ($github === null) {
            json_exit(['success' => false, 'error' => 'Could not resolve repo for latest release', 'output' => ''], 500);
        }
        [$owner, $repo] = $github;
        $release = get_latest_release($owner, $repo);
        if ($release === null) {
            json_exit(['success' => false, 'error' => 'Could not fetch latest release', 'output' => ''], 500);
        }
        [$tagName] = $release;
        $tagName = preg_replace('/[^a-zA-Z0-9._\-]/', '', $tagName) ?: 'v0.0.0';
        $cmd = sprintf(
            'cd %s && git fetch origin --tags 2>&1; git checkout %s 2>&1; echo __EXIT__$?',
            escapeshellarg($repoRoot),
            escapeshellarg($tagName),
            escapeshellarg($tagName)
        );
        $output = @shell_exec($cmd);
        if ($output === null) {
            json_exit(['success' => false, 'error' => 'git checkout failed', 'output' => ''], 500);
        }
        $exitCode = 1;
        if (preg_match('/__EXIT__(\d+)\s*$/', $output, $m)) {
            $exitCode = (int) $m[1];
            $output = trim(preg_replace('/__EXIT__\d+\s*$/', '', $output));
        } else {
            $output = trim($output);
        }
        $success = $exitCode === 0;
        json_exit([
            'success' => $success,
            'output' => $output,
            'error' => $success ? null : 'Checkout failed; check output.',
        ], $success ? 200 : 500);
    }

    $branch = get_default_branch($repoRoot);
    $branch = preg_replace('/[^a-zA-Z0-9._\-]/', '', $branch) ?: 'main';

    $cmd = sprintf(
        'cd %s && git fetch origin 2>&1; git pull --ff-only origin %s 2>&1; echo __EXIT__$?',
        escapeshellarg($repoRoot),
        escapeshellarg($branch)
    );
    $output = @shell_exec($cmd);
    if ($output === null) {
        json_exit(['success' => false, 'error' => 'git pull failed', 'output' => ''], 500);
    }
    $exitCode = 1;
    if (preg_match('/__EXIT__(\d+)\s*$/', $output, $m)) {
        $exitCode = (int) $m[1];
        $output = trim(preg_replace('/__EXIT__\d+\s*$/', '', $output));
    } else {
        $output = trim($output);
    }
    $success = $exitCode === 0;

    json_exit([
        'success' => $success,
        'output' => $output,
        'error' => $success ? null : 'Pull failed; check output.',
    ], $success ? 200 : 500);
}

json_exit(['error' => 'Unknown action. Use action=check or action=upgrade'], 400);
