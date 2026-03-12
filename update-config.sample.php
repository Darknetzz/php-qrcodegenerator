<?php
/**
 * Copy to update-config.php and adjust. Used by updates.php for repo, access control, and upgrade secret.
 */

// Repo for zip installs (owner/repo). Git clones use .git/config.
// define('UPDATE_REPO', 'owner/repo');

// --- Access control (check + upgrade) ---

// Restrict to these IPs (comma-separated). CIDR allowed (e.g. 10.0.0.0/24).
// define('UPDATE_IP_ALLOWLIST', '127.0.0.1, ::1');

// Require HTTP Basic Auth for check and upgrade.
// define('UPDATE_USE_BASIC_AUTH', true);
// define('UPDATE_AUTH_USER', 'admin');
// define('UPDATE_AUTH_PASSWORD', 'your-password');  // or set env UPDATE_AUTH_PASSWORD

// --- Upgrade only (optional) ---
// When set, POST ?action=upgrade must also send secret (body or X-Update-Secret header).
// putenv('UPDATE_SECRET=your-secret');  // or set in server env
