# Security

## Assessment summary

This document summarizes the security analysis performed on the QR Code Generator application and the improvements that were implemented.

### What was already in good shape

- **Authentication**: Admin and update secrets are compared with `hash_equals()` to avoid timing attacks. Login uses the same for username and password.
- **SQL**: Config is stored in SQLite with PDO and prepared statements; no raw user input in queries.
- **Output encoding**: Admin and main UI use `htmlspecialchars()` when echoing config or user-facing strings. Tab and error messages are validated/escaped.
- **Config**: Only allowed keys from `get_default_config()` are written to the database. `config.php` is in `.gitignore`; seeding is from a fixed allowlist.
- **Shell**: `updates.php` uses `escapeshellarg()` for repo path and branch in `git` commands; branch is further restricted with `preg_replace`.
- **Data directory**: `load_config.php` creates `data/.htaccess` with "Require all denied" for Apache.

### Issues found and fixed

1. **Security headers**  
   No `X-Frame-Options`, `X-Content-Type-Options`, or `Referrer-Policy` were set.  
   **Fix**: Added `security_headers()` in `load_config.php` and call it at the start of `index.php`, `admin.php`, `generate.php`, and `updates.php`. Headers set: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (restrictive).

2. **Session fixation**  
   After login in `updates.php`, the session ID was not regenerated.  
   **Fix**: Call `session_regenerate_id(true)` immediately after successful login.

3. **JSON in HTML (XSS)**  
   `index.php` embeds `SERVER_CUSTOM_MODULES` and `PRESET_ORDER` in `<script>` with plain `json_encode()`. Malicious config could break out of the script tag.  
   **Fix**: Use `json_encode(..., JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)` so embedded JSON cannot close the script tag or inject HTML.

4. **CSRF on admin**  
   Admin state-changing actions (save settings, add/delete modules, reorder) relied only on the key in URL/POST; no CSRF token.  
   **Fix**: Session-based CSRF token for admin: `csrf_token('admin_csrf')` / `csrf_verify('admin_csrf')` in `load_config.php`. All admin POST forms include the token; POST handling checks it before applying changes.

5. **CSRF on first-time setup**  
   `save-initial-config` could be submitted by a third-party site if the user had the setup page open.  
   **Fix**: `config-status` returns a one-time `setupToken` when not configured. Frontend stores it and sends it as `setup_csrf` when posting `save-initial-config`. Backend verifies with `csrf_verify('setup_csrf')`.

6. **generate.php DoS**  
   No limit on `text` length; very long content could cause high CPU/memory.  
   **Fix**: Reject requests with `text` longer than 4000 characters (400 response and clear message).

7. **Session cookie over HTTP**  
   Session cookie was not set with `Secure`, so it could be sent over HTTP.  
   **Fix**: `session_start()` in `updates.php` now uses `cookie_secure => true` when the request is considered HTTPS (including `X-Forwarded-Proto` / `X-Forwarded-SSL`).

### Recommendations (not changed in code)

- **Admin URL**: The admin key appears in the URL (`?key=...`). It can leak via Referrer; `Referrer-Policy` limits that. For higher security, consider moving to session-based admin auth after first key use (key logs you in, then session carries auth).
- **Nginx**: If you use Nginx, ensure `data/` (and ideally `config.php` if present) is not served; `.htaccess` only applies to Apache.
- **IPv6**: IP allowlist uses `ip2long()` (IPv4 only). IPv6 clients will not match allowlist entries; document or extend if you need IPv6.
- **Rate limiting**: There is no rate limiting on login, upgrade, or generate. Consider rate limiting or fail2ban for public deployments.

## Reporting vulnerabilities

If you find a security issue, please report it privately (e.g. via the repository’s security policy or maintainer contact) rather than in a public issue.
