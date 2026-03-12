# QR Code Generator

A self-contained QR code generator that runs on any Apache or Nginx server with PHP and the GD extension. No Composer, no package managers—drop the files and run.

![QR Code Generator](qrcode.png)

## Features

- **Presets**: Choose a content type and fill in the form; the correct QR payload is built for you.
  - **URL** – Website link
  - **Wi‑Fi** – Network name (SSID), password, encryption (None / WPA-WPA2 / WEP), hidden network
  - **vCard** – Contact (name, organization, phone, email)
  - **Text** – Plain text
  - **Email** – mailto with optional subject and body
  - **SMS** – smsto with number and optional message
  - **Bitcoin** – Address with optional amount and label
  - **Facebook** – Page or profile URL
  - **PDF / MP3 / Image** – Link to file (URL)
  - **App Store** – Link to app (iOS/Android store URL)
  - **Custom** – Raw string (e.g. your own vCard or URL)
- **Customization**: Module size, margin, error correction (L/M/Q/H), foreground and background colors.
- **Preview**: Live preview as you type.
- **Download**: PNG and SVG.

## Requirements

- PHP 8.2+
- GD extension (for PNG; SVG does not require GD)
- [Composer](https://getcomposer.org/) (for dependency installation)

## Installation

1. Copy the project into your web root (e.g. `htdocs/qr` or `/var/www/html/qr`).
2. Run `composer install` in the project directory (installs [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode)).
3. Ensure PHP has the GD extension enabled (default on most LAMP/LEMP stacks).
4. Open `https://your-server/qr/` (or `index.php`) in a browser.

### Apache

Document root should point to the folder containing `index.php`. No extra config required. Optional: if you want to allow long URLs for the generator, you can set a larger `LimitRequestLine` in server config (not required for normal use).

### Nginx

Example location:

```nginx
location /qr {
    alias /var/www/html/php-qrcodegenerator;
    index index.php;
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
}
```

Or serve the project as the root of a vhost; then `index index.php` and `try_files $uri $uri/ /index.php?$args;` as usual for PHP.

## Files

| File           | Purpose |
|----------------|--------|
| `index.php`    | Main page: form, preview, download links |
| `generate.php` | Endpoint that outputs QR as PNG or SVG |
| `composer.json`| PHP dependencies ([chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode)) |
| `VERSION`     | App version (first line only; for zip installs; in git, version is computed from `git describe`) |
| `updates.php`  | Update check (GitHub releases) and upgrade (git pull or release-page link) |
| `update-config.php` | Optional: repo, IP allowlist, Basic Auth, upgrade secret (copy from `update-config.sample.php`) |
| `update-version.php` | CLI: writes current git version to VERSION (run before release zip, or from a git hook) |

## Version

- **Git clone:** Version is computed at runtime: exact tag (e.g. `1.0.0`) or `1.0.0-<shortcommit>` when not on a tag.
- **Zip install:** Version is read from the `VERSION` file (first line).
- To refresh `VERSION` from git (e.g. before building a release zip), run from the project root:  
  `php update-version.php`  
  To keep `VERSION` in sync with git: copy the sample hooks into `.git/hooks/` and `chmod +x` them.  
  - **post-commit** — after each commit (so `VERSION` is updated before you push)  
  - **post-checkout** — after `git checkout`  
  - **post-merge** — after `git pull` / merge  
  E.g. `cp scripts/post-commit.sample .git/hooks/post-commit && chmod +x .git/hooks/post-commit`  
  Release steps are in [AGENTS.md](AGENTS.md#releasing-eg-101).

## Update endpoint access (updates.php)

By default, `updates.php` is open. To lock it down, copy `update-config.sample.php` to `update-config.php` and set:

- **IP allowlist:** `UPDATE_IP_ALLOWLIST` — comma-separated IPs or CIDR (e.g. `127.0.0.1, 10.0.0.0/24`). Requests from other IPs get 403.
- **Basic Auth:** `UPDATE_USE_BASIC_AUTH`, `UPDATE_AUTH_USER`, `UPDATE_AUTH_PASSWORD` — browser will prompt for username/password. You can set the password in server env only (`UPDATE_AUTH_PASSWORD`) for security.
- **Upgrade secret:** `UPDATE_SECRET` (env) — when set, the upgrade action also requires this value in the POST body or `X-Update-Secret` header.

You can use IP allowlist and Basic Auth together.

## API (generate.php)

Query or POST parameters:

| Parameter  | Description                    | Default   |
|-----------|--------------------------------|-----------|
| `text`    | Content to encode              | (required)|
| `size`    | Module size in pixels (1–20)   | 6         |
| `margin`  | Quiet zone in modules (0–20)   | 4         |
| `level`   | Error correction: L, M, Q, H   | L         |
| `fg`      | Foreground color (e.g. #000000)| #000000   |
| `bg`      | Background color (e.g. #ffffff)| #ffffff   |
| `format`  | `png` or `svg`                 | png       |
| `download`| Any value → attachment         | inline    |

Example:  
`generate.php?text=https%3A%2F%2Fexample.com&size=6&format=png`

## License

- This project: use as you like.
- chillerlan/php-qrcode: MIT (see [vendor/chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode)).  
- “QR Code” is a registered trademark of DENSO WAVE INCORPORATED.
