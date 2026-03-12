<?php
/**
 * Update check (GitHub releases) and upgrade (git pull) endpoint.
 * Returns JSON. Optional: set UPDATE_SECRET in env or a .env to require ?secret= for upgrade.
 *
 * GET  ?action=check  → { currentVersion, latestVersion, updateAvailable, releaseUrl }
 * POST ?action=upgrade [&secret=...] → { success, output, error }
 */
header('Content-Type: application/json; charset=utf-8');

$repoRoot = realpath(__DIR__);
if ($repoRoot === false || !is_dir($repoRoot . '/.git')) {
    json_exit(['error' => 'Not a git repository'], 500);
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

/** Current version string from git (tag or short hash) */
function get_local_version(string $repoRoot): string {
    $cmd = sprintf(
        'cd %s && git describe --tags --always 2>/dev/null || git rev-parse --short HEAD 2>/dev/null',
        escapeshellarg($repoRoot)
    );
    $out = @shell_exec($cmd);
    return $out !== null ? trim($out) : 'unknown';
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

function upgrade_allowed(): bool {
    $secret = getenv('UPDATE_SECRET');
    if ($secret === false || $secret === '') {
        return true; // no secret configured → allow (rely on server access control)
    }
    $given = $_REQUEST['secret'] ?? $_SERVER['HTTP_X_UPDATE_SECRET'] ?? '';
    return $given !== '' && hash_equals($secret, $given);
}

$action = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : '';

if ($action === 'check') {
    $current = get_local_version($repoRoot);
    $github = get_github_repo($repoRoot);
    $latestVersion = null;
    $releaseUrl = null;
    $updateAvailable = false;

    if ($github !== null) {
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
    ]);
}

if ($action === 'upgrade') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_exit(['error' => 'Use POST for upgrade'], 405);
    }
    if (!upgrade_allowed()) {
        json_exit(['error' => 'Unauthorized'], 403);
    }

    $branch = trim((string) ($_REQUEST['branch'] ?? ''));
    if ($branch === '') {
        $ref = @file_get_contents($repoRoot . '/.git/HEAD');
        $branch = 'main';
        if ($ref !== false && preg_match('#ref: refs/heads/(.+)#', trim($ref), $m)) {
            $branch = trim($m[1]);
        }
    }
    $branch = preg_replace('/[^a-zA-Z0-9._\-]/', '', $branch) ?: 'main';

    $cmd = sprintf(
        'cd %s && git fetch origin 2>&1 && git pull --ff-only origin %s 2>&1',
        escapeshellarg($repoRoot),
        escapeshellarg($branch)
    );
    $output = @shell_exec($cmd);
    if ($output === null) {
        json_exit(['success' => false, 'error' => 'git pull failed', 'output' => ''], 500);
    }
    $output = trim($output);
    // Heuristic: "Already up to date" or "Updating ..." with no "error:" / "fatal:"
    $success = (stripos($output, 'Already up to date') !== false)
        || (preg_match('/Updating\s+[a-f0-9]+\s+\.\.\.[a-f0-9]+/i', $output) === 1)
        && (stripos($output, 'fatal:') === false && stripos($output, 'error:') === false);

    json_exit([
        'success' => $success,
        'output' => $output,
        'error' => $success ? null : 'Pull may have failed; check output.',
    ], $success ? 200 : 500);
}

json_exit(['error' => 'Unknown action. Use action=check or action=upgrade'], 400);
