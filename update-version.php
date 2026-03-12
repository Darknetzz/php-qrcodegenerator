#!/usr/bin/env php
<?php
/**
 * Writes the current version to VERSION (from git describe).
 * Run from project root: php update-version.php
 * Use before creating a release zip, or from a git hook (post-checkout / post-merge).
 */
$repoRoot = __DIR__;

if (!is_dir($repoRoot . '/.git')) {
    fwrite(STDERR, "Not a git repo; VERSION unchanged.\n");
    exit(0);
}

$cmd = sprintf(
    'cd %s && git describe --tags --always 2>/dev/null || git rev-parse --short HEAD 2>/dev/null',
    escapeshellarg($repoRoot)
);
$out = trim((string) shell_exec($cmd));
if ($out === '') {
    $out = 'unknown';
} else {
    $out = preg_replace('/^v/i', '', $out);
    if (preg_match('/^(.+)-(\d+)-g([a-f0-9]+)$/i', $out, $m)) {
        $out = $m[1] . '-' . $m[3];
    }
}

$versionFile = $repoRoot . '/VERSION';
$content = $out . "\n# App version (used when not a git clone; update when releasing)\n";
if (file_put_contents($versionFile, $content) === false) {
    fwrite(STDERR, "Could not write VERSION.\n");
    exit(1);
}
echo "VERSION set to {$out}\n";
