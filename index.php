<?php
require_once __DIR__ . '/load_config.php';
$repoRoot = realpath(__DIR__);
require_app_access($repoRoot);
$config = load_config($repoRoot);
$serverCustomModules = json_decode($config['custom_modules'] ?? '[]', true);
if (!is_array($serverCustomModules)) {
    $serverCustomModules = [];
}
$presetOrder = json_decode($config['preset_order'] ?? '[]', true);
if (!is_array($presetOrder) || count($presetOrder) === 0) {
    $presetOrder = ['url', 'wifi', 'vcard', 'text', 'email', 'sms', 'bitcoin', 'facebook', 'pdf', 'mp3', 'appstore', 'image', 'custom'];
}

$title = 'QR Code Generator';
$defaultText = 'https://example.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($title); ?></title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <svg xmlns="http://www.w3.org/2000/svg" class="svg-sprite" aria-hidden="true">
    <defs>
      <symbol id="icon-link" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></symbol>
      <symbol id="icon-wifi" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1.5"/></symbol>
      <symbol id="icon-vcard" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 10h20"/><path d="M8 16h2"/><path d="M14 16h2"/><circle cx="7" cy="7" r="2"/></symbol>
      <symbol id="icon-text" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/><path d="M10 9H8"/></symbol>
      <symbol id="icon-email" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/></symbol>
      <symbol id="icon-sms" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></symbol>
      <symbol id="icon-btc" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M12 2c-3 0-4 1.5-4 4s1 4 4 4 4-1.5 4-4-1-4-4-4z"/><path d="M12 10c3 0 4 1.5 4 4s-1 4-4 4-4-1.5-4-4 1-4 4-4z"/></symbol>
      <symbol id="icon-facebook" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></symbol>
      <symbol id="icon-pdf" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 12h1"/><path d="M8 16h1"/><path d="M12 12h4"/><path d="M12 16h2"/></symbol>
      <symbol id="icon-mp3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></symbol>
      <symbol id="icon-appstore" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/></symbol>
      <symbol id="icon-image" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></symbol>
      <symbol id="icon-custom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></symbol>
      <symbol id="icon-phone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></symbol>
      <symbol id="icon-user" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></symbol>
      <symbol id="icon-building" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></symbol>
      <symbol id="icon-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></symbol>
      <symbol id="icon-palette" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.65 0-.43-.18-.84-.44-1.12-.29-.29-.44-.65-.44-1.12a1.65 1.65 0 0 1 1.65-1.65H20c0-1.1-.74-2.07-1.76-2.41a7 7 0 0 0-.38-2.07C17.07 3.3 14.93 2 12 2z"/></symbol>
      <symbol id="icon-size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05"/><path d="M12 22.08V12"/></symbol>
      <symbol id="icon-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></symbol>
      <symbol id="icon-refresh" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></symbol>
      <symbol id="icon-arrow-up" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="m5 12 7-7 7 7"/></symbol>
    </defs>
  </svg>
  <div id="onboarding" class="onboarding" aria-labelledby="onboarding-title">
    <div class="onboarding-inner">
      <h1 id="onboarding-title">Setup</h1>
      <p class="sub">Configure access control to continue. You must set either an IP allowlist or a login (username and password).</p>
      <div id="onboarding-error" class="msg err" style="display:none;"></div>
      <form id="onboarding-form" class="panel">
        <label for="setup-ip">IP allowlist (comma-separated)</label>
        <input type="text" id="setup-ip" name="update_ip_allowlist" placeholder="127.0.0.1, 10.0.0.0/24" autocomplete="off">
        <p class="hint">Allowed IPs can use the app and updates without logging in. Leave empty if you use login only.</p>
        <div class="checkbox-row">
          <label>
            <input type="checkbox" id="setup-allow-app-any-ip" name="update_allow_app_any_ip" value="1" checked>
            Allow app usage from any IP (uncheck to restrict main app to allowlist)
          </label>
        </div>
        <div class="checkbox-row">
          <label>
            <input type="checkbox" id="setup-use-basic" name="update_use_basic_auth" value="1">
            Require username and password (login)
          </label>
        </div>
        <div class="checkbox-row">
          <label>
            <input type="checkbox" id="setup-require-login-always" name="update_require_login_always" value="1">
            Require login even when IP is on allowlist
          </label>
        </div>
        <div id="setup-basic-auth-fields" class="setup-basic-fields">
          <label for="setup-user">Username</label>
          <input type="text" id="setup-user" name="update_auth_user" placeholder="admin" autocomplete="username">
          <label for="setup-password">Password</label>
          <input type="password" id="setup-password" name="update_auth_password" placeholder="" autocomplete="new-password">
        </div>
        <p class="hint">Set at least an IP allowlist or enable login with username and password.</p>
        <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Save and continue</button>
      </form>
    </div>
  </div>
  <div id="app-content" class="hidden">
  <div class="wrap">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p class="tagline">Create QR codes for URLs, text, or any content. No sign-up, no tracking.</p>

    <div class="grid">
      <div class="panel">
        <h2>Content &amp; options</h2>
        <div class="preset-tabs" role="tablist" aria-label="QR code type">
          <button type="button" class="preset-tab" data-preset="url" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-link"/></svg>URL</button>
          <button type="button" class="preset-tab" data-preset="wifi" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-wifi"/></svg>Wi‑Fi</button>
          <button type="button" class="preset-tab" data-preset="vcard" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-vcard"/></svg>vCard</button>
          <button type="button" class="preset-tab active" data-preset="text" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-text"/></svg>Text</button>
          <button type="button" class="preset-tab" data-preset="email" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-email"/></svg>Email</button>
          <button type="button" class="preset-tab" data-preset="sms" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-sms"/></svg>SMS</button>
          <button type="button" class="preset-tab" data-preset="bitcoin" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-btc"/></svg>Bitcoin</button>
          <button type="button" class="preset-tab" data-preset="facebook" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-facebook"/></svg>Facebook</button>
          <button type="button" class="preset-tab" data-preset="pdf" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-pdf"/></svg>PDF</button>
          <button type="button" class="preset-tab" data-preset="mp3" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-mp3"/></svg>MP3</button>
          <button type="button" class="preset-tab" data-preset="appstore" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-appstore"/></svg>App Store</button>
          <button type="button" class="preset-tab" data-preset="image" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-image"/></svg>Image</button>
          <button type="button" class="preset-tab" data-preset="custom" role="tab"><svg class="tab-icon" aria-hidden="true"><use href="#icon-custom"/></svg>Custom</button>
          <span id="custom-modules-tabs" class="settings-gated"></span>
          <button type="button" class="preset-tab preset-tab-add settings-gated" id="btn-add-module" title="Add custom module" aria-label="Add custom module">+</button>
        </div>
        <form id="qr-form" method="get" action="" autocomplete="off">
          <div id="preset-url" class="preset-panel">
            <label for="url"><svg class="label-icon" aria-hidden="true"><use href="#icon-link"/></svg>Website URL</label>
            <input type="url" id="url" placeholder="https://example.com" value="https://example.com" autocomplete="off">
          </div>
          <div id="preset-wifi" class="preset-panel">
            <label for="wifi-ssid"><svg class="label-icon" aria-hidden="true"><use href="#icon-wifi"/></svg>Network name (SSID)</label>
            <input type="text" id="wifi-ssid" placeholder="MyNetwork" autocomplete="off">
            <div class="checkbox-row">
              <input type="checkbox" id="wifi-hidden" aria-describedby="wifi-hidden-desc">
              <label for="wifi-hidden" id="wifi-hidden-desc">Hidden network</label>
            </div>
            <label for="wifi-password"><svg class="label-icon" aria-hidden="true"><use href="#icon-lock"/></svg>Password</label>
            <input type="text" id="wifi-password" placeholder="Leave empty for open networks" autocomplete="off">
            <label for="wifi-encryption"><svg class="label-icon" aria-hidden="true"><use href="#icon-lock"/></svg>Encryption</label>
            <select id="wifi-encryption">
              <option value="nopass">None (open)</option>
              <option value="WPA" selected>WPA / WPA2</option>
              <option value="WEP">WEP</option>
            </select>
          </div>
          <div id="preset-vcard" class="preset-panel">
            <label for="vcard-name"><svg class="label-icon" aria-hidden="true"><use href="#icon-user"/></svg>Full name</label>
            <input type="text" id="vcard-name" placeholder="John Doe" autocomplete="off">
            <label for="vcard-org"><svg class="label-icon" aria-hidden="true"><use href="#icon-building"/></svg>Organization</label>
            <input type="text" id="vcard-org" placeholder="Company" autocomplete="off">
            <label for="vcard-tel"><svg class="label-icon" aria-hidden="true"><use href="#icon-phone"/></svg>Phone</label>
            <input type="tel" id="vcard-tel" placeholder="+1 234 567 8900" autocomplete="off">
            <label for="vcard-email"><svg class="label-icon" aria-hidden="true"><use href="#icon-email"/></svg>Email</label>
            <input type="email" id="vcard-email" placeholder="john@example.com" autocomplete="off">
          </div>
          <div id="preset-text" class="preset-panel active">
            <label for="text"><svg class="label-icon" aria-hidden="true"><use href="#icon-text"/></svg>Plain text</label>
            <textarea id="text" name="text" placeholder="Enter any text..." autocomplete="off"></textarea>
          </div>
          <div id="preset-email" class="preset-panel">
            <label for="email-addr"><svg class="label-icon" aria-hidden="true"><use href="#icon-email"/></svg>Email address</label>
            <input type="email" id="email-addr" placeholder="you@example.com" autocomplete="off">
            <label for="email-subject"><svg class="label-icon" aria-hidden="true"><use href="#icon-text"/></svg>Subject</label>
            <input type="text" id="email-subject" placeholder="Optional" autocomplete="off">
            <label for="email-body"><svg class="label-icon" aria-hidden="true"><use href="#icon-text"/></svg>Body</label>
            <textarea id="email-body" placeholder="Optional" rows="3" autocomplete="off"></textarea>
          </div>
          <div id="preset-sms" class="preset-panel">
            <label for="sms-number"><svg class="label-icon" aria-hidden="true"><use href="#icon-phone"/></svg>Phone number</label>
            <input type="tel" id="sms-number" placeholder="+1234567890" autocomplete="off">
            <label for="sms-message"><svg class="label-icon" aria-hidden="true"><use href="#icon-sms"/></svg>Message</label>
            <textarea id="sms-message" placeholder="Pre-filled message (optional)" rows="3" autocomplete="off"></textarea>
          </div>
          <div id="preset-bitcoin" class="preset-panel">
            <label for="btc-address"><svg class="label-icon" aria-hidden="true"><use href="#icon-btc"/></svg>Bitcoin address</label>
            <input type="text" id="btc-address" placeholder="bc1q... or 1..." autocomplete="off">
            <label for="btc-amount">Amount (BTC, optional)</label>
            <input type="text" id="btc-amount" placeholder="0.01" autocomplete="off">
            <label for="btc-label">Label (optional)</label>
            <input type="text" id="btc-label" placeholder="Payment for..." autocomplete="off">
          </div>
          <div id="preset-facebook" class="preset-panel">
            <label for="facebook-url"><svg class="label-icon" aria-hidden="true"><use href="#icon-facebook"/></svg>Facebook page or profile URL</label>
            <input type="url" id="facebook-url" placeholder="https://www.facebook.com/..." autocomplete="off">
          </div>
          <div id="preset-pdf" class="preset-panel">
            <label for="pdf-url"><svg class="label-icon" aria-hidden="true"><use href="#icon-pdf"/></svg>Link to PDF file</label>
            <input type="url" id="pdf-url" placeholder="https://example.com/document.pdf" autocomplete="off">
          </div>
          <div id="preset-mp3" class="preset-panel">
            <label for="mp3-url"><svg class="label-icon" aria-hidden="true"><use href="#icon-mp3"/></svg>Link to audio file (MP3, etc.)</label>
            <input type="url" id="mp3-url" placeholder="https://example.com/audio.mp3" autocomplete="off">
          </div>
          <div id="preset-appstore" class="preset-panel">
            <label for="appstore-url"><svg class="label-icon" aria-hidden="true"><use href="#icon-appstore"/></svg>App store or play store URL</label>
            <input type="url" id="appstore-url" placeholder="https://apps.apple.com/... or https://play.google.com/..." autocomplete="off">
          </div>
          <div id="preset-image" class="preset-panel">
            <label for="image-url"><svg class="label-icon" aria-hidden="true"><use href="#icon-image"/></svg>Link to image</label>
            <input type="url" id="image-url" placeholder="https://example.com/image.png" autocomplete="off">
          </div>
          <div id="preset-custom" class="preset-panel">
            <label for="custom-text"><svg class="label-icon" aria-hidden="true"><use href="#icon-custom"/></svg>Raw content (URL, vCard, or any string)</label>
            <textarea id="custom-text" placeholder="Paste or type any content to encode" autocomplete="off"></textarea>
          </div>
          <div id="custom-modules-panels" class="settings-gated"></div>

          <div class="row">
            <div class="field">
              <label for="size"><svg class="label-icon" aria-hidden="true"><use href="#icon-size"/></svg>Module size (pixels)</label>
              <input type="number" id="size" name="size" value="6" min="1" max="20" step="1" autocomplete="off">
            </div>
            <div class="field">
              <label for="margin"><svg class="label-icon" aria-hidden="true"><use href="#icon-size"/></svg>Margin (modules)</label>
              <input type="number" id="margin" name="margin" value="4" min="0" max="20" step="1" autocomplete="off">
            </div>
          </div>

          <label for="level">Error correction</label>
          <select id="level" name="level" autocomplete="off">
            <option value="L">L – Low (~7%)</option>
            <option value="M" selected>M – Medium (~15%)</option>
            <option value="Q">Q – Quartile (~25%)</option>
            <option value="H">H – High (~30%)</option>
          </select>

          <div class="row">
            <div class="field">
              <label><svg class="label-icon" aria-hidden="true"><use href="#icon-palette"/></svg>Foreground color</label>
              <div class="color-wrap">
                <input type="color" id="fg-color" value="#000000" aria-label="Foreground color">
                <input type="text" id="fg" name="fg" value="#000000" maxlength="7" placeholder="#000000" autocomplete="off">
              </div>
            </div>
            <div class="field">
              <label><svg class="label-icon" aria-hidden="true"><use href="#icon-palette"/></svg>Background color</label>
              <div class="color-wrap">
                <input type="color" id="bg-color" value="#ffffff" aria-label="Background color">
                <input type="text" id="bg" name="bg" value="#ffffff" maxlength="7" placeholder="#ffffff" autocomplete="off">
              </div>
            </div>
          </div>
        </form>
      </div>

      <div class="panel">
        <h2>Preview</h2>
        <div class="preview-wrap">
          <img id="preview" src="" alt="QR code preview">
          <span id="preview-placeholder" class="preview-placeholder">Enter content to see preview</span>
        </div>
        <div class="actions">
          <div class="btn-group">
            <a id="dl-png" class="btn btn-primary" href="#" download="qrcode.png"><svg class="btn-icon" aria-hidden="true"><use href="#icon-download"/></svg>Download PNG</a>
            <a id="dl-svg" class="btn btn-secondary" href="#" download="qrcode.svg"><svg class="btn-icon" aria-hidden="true"><use href="#icon-download"/></svg>Download SVG</a>
          </div>
        </div>
      </div>
    </div>

    <p class="foot">
      Uses <a href="https://github.com/chillerlan/php-qrcode" target="_blank" rel="noopener">chillerlan/php-qrcode</a> (MIT).
      No data is stored on the server. For very long content, use the download buttons.
    </p>
    <div class="modal-overlay" id="custom-module-modal" role="dialog" aria-labelledby="custom-module-title" aria-modal="true">
      <div class="modal">
        <h3 id="custom-module-title">Add custom module</h3>
        <p class="text-muted modal-desc">Define a preset with a format string. Use <code>%s</code> for each field (e.g. <code>tel:%s</code> or <code>https://example.com?id=%s</code>).</p>
        <form id="add-module-form">
          <input type="hidden" id="module-edit-id" value="">
          <label for="module-name">Name</label>
          <input type="text" id="module-name" placeholder="e.g. Phone" required autocomplete="off">
          <label for="module-icon">Icon (optional — emoji or sprite name, e.g. 📞 or icon-phone)</label>
          <input type="text" id="module-icon" placeholder="📞 or icon-phone" autocomplete="off">
          <label for="module-format">Format</label>
          <input type="text" id="module-format" placeholder="tel:%s" required autocomplete="off">
          <label for="module-labels">Field labels (comma-separated, one per %s)</label>
          <input type="text" id="module-labels" placeholder="e.g. Phone number" autocomplete="off">
          <div class="modal-actions">
            <button type="button" class="btn btn-danger modal-delete-module" id="btn-delete-module" style="display:none;">Delete</button>
            <button type="button" class="btn btn-secondary" id="btn-cancel-module">Cancel</button>
            <button type="submit" class="btn btn-primary" id="btn-module-submit">Add</button>
          </div>
        </form>
      </div>
    </div>

    <div id="settings-gate-message" class="settings-gate-message" style="display:none;" aria-live="polite"></div>
    <div class="foot updates-row settings-gated" id="updates-row" aria-live="polite">
      <span class="version" id="current-version">—</span>
      <button type="button" class="btn btn-secondary" id="btn-check-updates" aria-label="Check for updates">
        <svg class="btn-icon" aria-hidden="true"><use href="#icon-refresh"/></svg>Check for updates
      </button>
      <span class="update-msg" id="update-msg"></span>
      <button type="button" class="btn btn-primary" id="btn-upgrade"><svg class="btn-icon" aria-hidden="true"><use href="#icon-arrow-up"/></svg>Upgrade (git pull)</button>
      <a href="admin.php" class="btn btn-secondary admin-link">Admin</a>
      <button type="button" class="btn btn-secondary" id="btn-logout" style="display:none;">Log out</button>
    </div>
  </div>
  </div>

  <script>window.SERVER_CUSTOM_MODULES = <?php echo json_encode($serverCustomModules); ?>;</script>
  <script>window.PRESET_ORDER = <?php echo json_encode($presetOrder); ?>;</script>
  <script>
(function() {
  var form = document.getElementById('qr-form');
  var size = document.getElementById('size');
  var margin = document.getElementById('margin');
  var level = document.getElementById('level');
  var fg = document.getElementById('fg');
  var bg = document.getElementById('bg');
  var fgColor = document.getElementById('fg-color');
  var bgColor = document.getElementById('bg-color');
  var preview = document.getElementById('preview');
  var placeholder = document.getElementById('preview-placeholder');
  var dlPng = document.getElementById('dl-png');
  var dlSvg = document.getElementById('dl-svg');

  var currentPreset = 'wifi';

  var PRESET_IDS = ['url', 'wifi', 'vcard', 'text', 'email', 'sms', 'bitcoin', 'facebook', 'pdf', 'mp3', 'appstore', 'image', 'custom'];
  var PRESET_ORDER = window.PRESET_ORDER && window.PRESET_ORDER.length === PRESET_IDS.length ? window.PRESET_ORDER : PRESET_IDS.slice();
  var PRESET_LABELS = { url: 'URL', wifi: 'Wi‑Fi', vcard: 'vCard', text: 'Text', email: 'Email', sms: 'SMS', bitcoin: 'Bitcoin', facebook: 'Facebook', pdf: 'PDF', mp3: 'MP3', appstore: 'App Store', image: 'Image', custom: 'Custom' };
  var STORAGE_KEY = 'qr-preset';
  var CUSTOM_MODULES_KEY = 'qr-custom-modules';
  var hiddenPresetsFromServer = [];
  var serverModules = window.SERVER_CUSTOM_MODULES || [];
  var serverModuleIds = serverModules.map(function(m) { return m.id; });
  function getHiddenPresets() {
    return hiddenPresetsFromServer;
  }
  function applyPresetOrder() {
    var order = PRESET_ORDER;
    var tabsContainer = document.querySelector('.preset-tabs');
    var ref = document.getElementById('custom-modules-tabs');
    if (tabsContainer && ref) {
      for (var i = 0; i < order.length; i++) {
        var tab = tabsContainer.querySelector('.preset-tab[data-preset="' + order[i] + '"]');
        if (tab) tabsContainer.insertBefore(tab, ref);
      }
    }
    var form = document.getElementById('qr-form');
    if (form) {
      var firstPanel = form.querySelector('.preset-panel');
      if (firstPanel) {
        for (var j = order.length - 1; j >= 0; j--) {
          var panel = document.getElementById('preset-' + order[j]);
          if (panel) {
            form.insertBefore(panel, firstPanel);
            firstPanel = panel;
          }
        }
      }
    }
  }
  function getLocalCustomModules() {
    try {
      var raw = localStorage.getItem(CUSTOM_MODULES_KEY);
      if (!raw) return [];
      var arr = JSON.parse(raw);
      return Array.isArray(arr) ? arr : [];
    } catch (e) { return []; }
  }
  function getCustomModules() {
    var local = getLocalCustomModules().filter(function(m) {
      return serverModuleIds.indexOf(m.id) === -1;
    });
    return serverModules.concat(local);
  }
  function setCustomModules(arr) {
    try {
      var local = arr.filter(function(m) { return serverModuleIds.indexOf(m.id) === -1; });
      localStorage.setItem(CUSTOM_MODULES_KEY, JSON.stringify(local));
    } catch (e) {}
  }
  function applyDefaultPresetsVisibility() {
    var hidden = getHiddenPresets();
    PRESET_ORDER.forEach(function(id) {
      var tab = document.querySelector('.preset-tabs > .preset-tab[data-preset="' + id + '"]');
      var panel = document.getElementById('preset-' + id);
      var isHidden = hidden.indexOf(id) !== -1;
      if (tab) tab.classList.toggle('preset-tab-hidden', isHidden);
      if (panel) panel.classList.toggle('preset-panel-hidden', isHidden);
    });
  }
  function getCustomModuleIds() {
    return getCustomModules().map(function(m) { return m.id; });
  }
  function nextCustomId() {
    var ids = getCustomModuleIds();
    var n = 1;
    while (ids.indexOf('custom-' + n) !== -1) n++;
    return 'custom-' + n;
  }

  function setPreset(id) {
    currentPreset = id;
    var tabs = document.querySelectorAll('.preset-tab');
    var panelsEls = document.querySelectorAll('.preset-panel');
    tabs.forEach(function(btn) {
      btn.classList.toggle('active', btn.getAttribute('data-preset') === id);
    });
    panelsEls.forEach(function(panel) {
      panel.classList.toggle('active', panel.id === 'preset-' + id);
    });
    try { sessionStorage.setItem(STORAGE_KEY, id); } catch (e) {}
    update();
  }

  document.querySelector('.preset-tabs').addEventListener('click', function(e) {
    var tab = e.target.closest('.preset-tab');
    if (tab && tab.getAttribute('data-preset')) setPreset(tab.getAttribute('data-preset'));
  });

  function hexFromInput(val) {
    val = (val || '').trim();
    if (/^#[0-9A-Fa-f]{3,6}$/.test(val)) return val;
    if (/^[0-9A-Fa-f]{3}$/.test(val)) return '#' + val[0]+val[0] + val[1]+val[1] + val[2]+val[2];
    if (/^[0-9A-Fa-f]{6}$/.test(val)) return '#' + val;
    return null;
  }

  function syncColorToPicker(hexInput, picker) {
    var hex = hexFromInput(hexInput.value);
    if (hex && hex.length === 7) picker.value = hex;
  }

  fg.addEventListener('input', function() { syncColorToPicker(fg, fgColor); });
  fg.addEventListener('change', function() { syncColorToPicker(fg, fgColor); });
  bg.addEventListener('input', function() { syncColorToPicker(bg, bgColor); });
  bg.addEventListener('change', function() { syncColorToPicker(bg, bgColor); });
  fgColor.addEventListener('input', function() { fg.value = fgColor.value; update(); });
  bgColor.addEventListener('input', function() { bg.value = bgColor.value; update(); });

  function escapeWifiField(s) {
    return (s || '').toString().replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/:/g, '\\:').replace(/"/g, '\\"');
  }

  function renderCustomModuleIcon(container, icon) {
    if (!icon) return;
    var s = (icon || '').trim();
    if (s.indexOf('icon-') === 0) {
      var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('class', 'tab-icon');
      svg.setAttribute('aria-hidden', 'true');
      var use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
      use.setAttribute('href', '#' + s);
      svg.appendChild(use);
      container.appendChild(svg);
    } else if (s.length > 0) {
      var span = document.createElement('span');
      span.className = 'tab-icon tab-icon-emoji';
      span.textContent = s.length <= 2 ? s : s.charAt(0);
      container.appendChild(span);
    }
  }
  function renderCustomModules() {
    var tabsContainer = document.getElementById('custom-modules-tabs');
    var panelsContainer = document.getElementById('custom-modules-panels');
    if (!tabsContainer || !panelsContainer) return;
    tabsContainer.textContent = '';
    panelsContainer.textContent = '';
    var modules = getCustomModules();
    modules.forEach(function(m) {
      var wrap = document.createElement('span');
      wrap.className = 'preset-tab-custom-wrap';
      var tab = document.createElement('button');
      tab.type = 'button';
      tab.className = 'preset-tab';
      tab.setAttribute('data-preset', m.id);
      tab.setAttribute('role', 'tab');
      renderCustomModuleIcon(tab, m.icon);
      tab.appendChild(document.createTextNode(m.name));
      wrap.appendChild(tab);
      var isServerModule = serverModuleIds.indexOf(m.id) !== -1;
      if (!isServerModule) {
        var editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'preset-tab-custom-edit';
        editBtn.setAttribute('aria-label', 'Edit ' + m.name);
        editBtn.textContent = '\u270e';
        editBtn.addEventListener('click', function(ev) {
          ev.stopPropagation();
          openModuleModalForEdit(m);
        });
        wrap.appendChild(editBtn);
      }
      tabsContainer.appendChild(wrap);
      var panel = document.createElement('div');
      panel.id = 'preset-' + m.id;
      panel.className = 'preset-panel';
      m.fields.forEach(function(f, i) {
        var label = document.createElement('label');
        label.htmlFor = 'custom-mod-' + m.id + '-' + i;
        label.textContent = f.label || ('Field ' + (i + 1));
        panel.appendChild(label);
        var input = document.createElement('input');
        input.type = 'text';
        input.id = 'custom-mod-' + m.id + '-' + i;
        input.placeholder = f.placeholder || '';
        input.autocomplete = 'off';
        panel.appendChild(input);
      });
      panelsContainer.appendChild(panel);
    });
  }

  function buildPayload() {
    var v = function(id) { return (document.getElementById(id) && document.getElementById(id).value) || ''; };
    var trim = function(s) { return (s || '').trim(); };
    switch (currentPreset) {
      case 'url':
        return trim(v('url')) || '';
      case 'wifi': {
        var ssid = trim(v('wifi-ssid'));
        if (!ssid) return '';
        var enc = v('wifi-encryption');
        var pass = v('wifi-password');
        var hidden = document.getElementById('wifi-hidden') && document.getElementById('wifi-hidden').checked;
        var parts = ['WIFI:T:' + enc + ';S:' + escapeWifiField(ssid)];
        if (enc !== 'nopass' && pass !== undefined) parts.push('P:' + escapeWifiField(pass));
        if (hidden) parts.push('H:true');
        parts.push(';;');
        return parts.join(';');
      }
      case 'vcard': {
        var name = trim(v('vcard-name'));
        if (!name) return '';
        var org = trim(v('vcard-org'));
        var tel = trim(v('vcard-tel'));
        var email = trim(v('vcard-email'));
        var lines = ['BEGIN:VCARD', 'VERSION:3.0', 'FN:' + name, 'N:' + name];
        if (org) lines.push('ORG:' + org);
        if (tel) lines.push('TEL:' + tel);
        if (email) lines.push('EMAIL:' + email);
        lines.push('END:VCARD');
        return lines.join("\r\n");
      }
      case 'text':
        return trim(v('text')) || '';
      case 'email': {
        var addr = trim(v('email-addr'));
        if (!addr) return '';
        var subj = trim(v('email-subject'));
        var body = trim(v('email-body'));
        var mailto = 'mailto:' + encodeURIComponent(addr);
        var params = [];
        if (subj) params.push('subject=' + encodeURIComponent(subj));
        if (body) params.push('body=' + encodeURIComponent(body));
        if (params.length) mailto += '?' + params.join('&');
        return mailto;
      }
      case 'sms': {
        var num = trim(v('sms-number'));
        if (!num) return '';
        var msg = trim(v('sms-message'));
        return 'smsto:' + num + (msg ? ':' + msg : '');
      }
      case 'bitcoin': {
        var addr = trim(v('btc-address'));
        if (!addr) return '';
        var amount = trim(v('btc-amount'));
        var label = trim(v('btc-label'));
        var btc = 'bitcoin:' + addr;
        var q = [];
        if (amount) q.push('amount=' + encodeURIComponent(amount));
        if (label) q.push('label=' + encodeURIComponent(label));
        if (q.length) btc += '?' + q.join('&');
        return btc;
      }
      case 'facebook':
        return trim(v('facebook-url')) || '';
      case 'pdf':
        return trim(v('pdf-url')) || '';
      case 'mp3':
        return trim(v('mp3-url')) || '';
      case 'appstore':
        return trim(v('appstore-url')) || '';
      case 'image':
        return trim(v('image-url')) || '';
      case 'custom':
        return trim(v('custom-text')) || '';
      default:
        if (String(currentPreset).indexOf('custom-') === 0) {
          var mods = getCustomModules();
          for (var i = 0; i < mods.length; i++) {
            if (mods[i].id === currentPreset) {
              var fmt = mods[i].format;
              var vals = [];
              for (var j = 0; j < mods[i].fields.length; j++) {
                vals.push(trim(v('custom-mod-' + currentPreset + '-' + j)) || '');
              }
              var idx = 0;
              return fmt.replace(/%s/g, function() { return vals[idx++] ?? ''; });
            }
          }
        }
        return '';
    }
  }

  function buildParams() {
    var t = buildPayload();
    var fgVal = hexFromInput(fg.value) || '#000000';
    var bgVal = hexFromInput(bg.value) || '#ffffff';
    return {
      text: t,
      size: size.value,
      margin: margin.value,
      level: level.value,
      fg: fgVal,
      bg: bgVal
    };
  }

  function buildUrl(format, download) {
    var p = buildParams();
    if (!p.text) return '';
    var base = 'generate.php?';
    var q = 'text=' + encodeURIComponent(p.text) +
      '&size=' + encodeURIComponent(p.size) +
      '&margin=' + encodeURIComponent(p.margin) +
      '&level=' + encodeURIComponent(p.level) +
      '&fg=' + encodeURIComponent(p.fg) +
      '&bg=' + encodeURIComponent(p.bg) +
      '&format=' + format;
    if (download) q += '&download=1';
    return base + q;
  }

  function update() {
    var p = buildParams();
    if (!p.text) {
      preview.style.display = 'none';
      placeholder.style.display = 'block';
      dlPng.href = '#';
      dlSvg.href = '#';
      return;
    }
    var url = buildUrl('png') + '&_=' + Date.now();
    preview.src = url;
    preview.style.display = 'block';
    placeholder.style.display = 'none';
    dlPng.href = buildUrl('png', true);
    dlSvg.href = buildUrl('svg', true);
  }

  var presetInputs = [
    'url', 'wifi-ssid', 'wifi-password', 'wifi-encryption', 'wifi-hidden',
    'vcard-name', 'vcard-org', 'vcard-tel', 'vcard-email',
    'text', 'email-addr', 'email-subject', 'email-body',
    'sms-number', 'sms-message', 'btc-address', 'btc-amount', 'btc-label',
    'facebook-url', 'pdf-url', 'mp3-url', 'appstore-url', 'image-url', 'custom-text'
  ];
  presetInputs.forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('input', update);
      el.addEventListener('change', update);
    }
  });
  [size, margin, level, fg, bg].forEach(function(el) {
    if (el) {
      el.addEventListener('input', update);
      el.addEventListener('change', update);
    }
  });
  form.addEventListener('input', update);
  form.addEventListener('change', update);

  renderCustomModules();
  applyPresetOrder();
  function initPresetsVisibility() {
    var hidden = getHiddenPresets();
    if (!Array.isArray(hidden)) hidden = [];
    if (hidden.length >= PRESET_IDS.length) hidden = [];
    applyDefaultPresetsVisibility();
    var saved = null;
    try { saved = sessionStorage.getItem(STORAGE_KEY); } catch (e) {}
    var visibleDefaults = PRESET_ORDER.filter(function(id) { return hidden.indexOf(id) === -1; });
    var allIds = visibleDefaults.concat(getCustomModuleIds());
    var initial = (saved && allIds.indexOf(saved) !== -1) ? saved : (visibleDefaults[0] || 'text');
    setPreset(initial);
  }
  fetch('updates.php?action=hidden-presets', { credentials: 'include' })
    .then(function(r) { return r.json(); })
    .then(function(d) {
      hiddenPresetsFromServer = (d && d.hiddenPresets) && Array.isArray(d.hiddenPresets) ? d.hiddenPresets : [];
      initPresetsVisibility();
    })
    .catch(function() {
      hiddenPresetsFromServer = [];
      initPresetsVisibility();
    });

  function openModuleModalForEdit(m) {
    var titleEl = document.getElementById('custom-module-title');
    var editIdEl = document.getElementById('module-edit-id');
    var nameEl = document.getElementById('module-name');
    var iconEl = document.getElementById('module-icon');
    var formatEl = document.getElementById('module-format');
    var labelsEl = document.getElementById('module-labels');
    var submitBtn = document.getElementById('btn-module-submit');
    var btnDelete = document.getElementById('btn-delete-module');
    if (titleEl) titleEl.textContent = 'Edit custom module';
    if (submitBtn) submitBtn.textContent = 'Save';
    if (editIdEl) editIdEl.value = m.id;
    if (nameEl) nameEl.value = m.name || '';
    if (iconEl) iconEl.value = m.icon || '';
    if (formatEl) formatEl.value = m.format || '';
    if (labelsEl) labelsEl.value = (m.fields || []).map(function(f) { return f.label || ''; }).join(', ');
    if (btnDelete) { btnDelete.style.display = ''; btnDelete.dataset.editId = m.id; }
    document.getElementById('custom-module-modal').classList.add('visible');
  }
  (function customModuleModal() {
    var modal = document.getElementById('custom-module-modal');
    var addForm = document.getElementById('add-module-form');
    var btnAdd = document.getElementById('btn-add-module');
    var btnCancel = document.getElementById('btn-cancel-module');
    var titleEl = document.getElementById('custom-module-title');
    if (!modal || !addForm || !btnAdd) return;
    var btnDelete = document.getElementById('btn-delete-module');
    function show() {
      if (titleEl) titleEl.textContent = 'Add custom module';
      var submitBtn = document.getElementById('btn-module-submit');
      if (submitBtn) submitBtn.textContent = 'Add';
      var editIdEl = document.getElementById('module-edit-id');
      if (editIdEl) editIdEl.value = '';
      if (btnDelete) btnDelete.style.display = 'none';
      addForm.reset();
      modal.classList.add('visible');
    }
    function hide() {
      modal.classList.remove('visible');
      addForm.reset();
      var e = document.getElementById('module-edit-id');
      if (e) e.value = '';
      if (btnDelete) btnDelete.style.display = 'none';
    }
    btnAdd.addEventListener('click', show);
    btnCancel.addEventListener('click', hide);
    if (btnDelete) {
      btnDelete.addEventListener('click', function() {
        var editId = (btnDelete.dataset.editId || (document.getElementById('module-edit-id') && document.getElementById('module-edit-id').value) || '').trim();
        if (!editId || !confirm('Remove this module?')) return;
        var mods = getCustomModules().filter(function(x) { return x.id !== editId; });
        setCustomModules(mods);
        renderCustomModules();
        if (currentPreset === editId) setPreset('text');
        hide();
      });
    }
    modal.addEventListener('click', function(e) { if (e.target === modal) hide(); });
    addForm.addEventListener('submit', function(e) {
      e.preventDefault();
      var editId = (document.getElementById('module-edit-id').value || '').trim();
      var name = (document.getElementById('module-name').value || '').trim();
      var icon = (document.getElementById('module-icon').value || '').trim();
      var format = (document.getElementById('module-format').value || '').trim();
      var labelsStr = (document.getElementById('module-labels').value || '').trim();
      if (!name || !format) return;
      var placeholders = (format.match(/%s/g) || []);
      var numFields = placeholders.length;
      if (numFields === 0) { alert('Format must contain at least one %s'); return; }
      var labels = labelsStr ? labelsStr.split(',').map(function(s) { return s.trim(); }) : [];
      while (labels.length < numFields) labels.push('Field ' + (labels.length + 1));
      var modules = getCustomModules();
      var fields = labels.slice(0, numFields).map(function(l) { return { label: l, placeholder: '' }; });
      if (editId) {
        var idx = modules.findIndex(function(x) { return x.id === editId; });
        if (idx !== -1) {
          modules[idx] = { id: editId, name: name, icon: icon, format: format, fields: fields };
          setCustomModules(modules);
          renderCustomModules();
          setPreset(editId);
        }
      } else {
        var newId = nextCustomId();
        modules.push({ id: newId, name: name, icon: icon, format: format, fields: fields });
        setCustomModules(modules);
        renderCustomModules();
        setPreset(newId);
      }
      hide();
    });
  })();

})();

(function updatesUi() {
  var versionEl = document.getElementById('current-version');
  var msgEl = document.getElementById('update-msg');
  var checkBtn = document.getElementById('btn-check-updates');
  var upgradeBtn = document.getElementById('btn-upgrade');
  var gateMsgEl = document.getElementById('settings-gate-message');
  var onboardingEl = document.getElementById('onboarding');
  var appContentEl = document.getElementById('app-content');
  var onboardingForm = document.getElementById('onboarding-form');
  var onboardingError = document.getElementById('onboarding-error');

  function setMsg(text, className) {
    msgEl.textContent = text || '';
    msgEl.className = 'update-msg' + (className ? ' ' + className : '');
  }

  function setGateMessage(html, className) {
    if (!gateMsgEl) return;
    gateMsgEl.innerHTML = html || '';
    gateMsgEl.className = 'settings-gate-message' + (className ? ' ' + className : '');
    gateMsgEl.style.display = html ? 'block' : 'none';
  }

  function setGatedVisible(visible) {
    document.querySelectorAll('.settings-gated').forEach(function(el) {
      el.classList.toggle('hidden', !visible);
    });
  }

  function showOnboarding() {
    if (onboardingEl) onboardingEl.classList.add('visible');
    if (appContentEl) appContentEl.classList.add('hidden');
  }

  function showApp() {
    if (onboardingEl) onboardingEl.classList.remove('visible');
    if (appContentEl) appContentEl.classList.remove('hidden');
    setGateMessage('');
    setGatedVisible(true);
    loadVersion();
  }

  function applyConfigStatus() {
    fetch('updates.php?action=config-status', { credentials: 'include' })
      .then(function(r) {
        if (r.status === 401 || r.status === 403) {
          var btnLogout = document.getElementById('btn-logout');
          if (btnLogout) btnLogout.style.display = 'none';
          if (onboardingEl) onboardingEl.classList.remove('visible');
          if (appContentEl) appContentEl.classList.remove('hidden');
          setGateMessage('');
          setGatedVisible(false);
          if (versionEl) versionEl.textContent = 'Version — (login to check)';
          setGateMessage(
            'Access control is enabled. Log in or use an allowed IP to enable <strong>Check for updates</strong> and <strong>custom modules</strong>. ' +
            '<div class="login-form"><form id="login-form"><label for="login-username">Username</label><input type="text" id="login-username" name="username" autocomplete="username" required> ' +
            '<label for="login-password">Password</label><input type="password" id="login-password" name="password" autocomplete="current-password" required> ' +
            '<div class="login-actions"><button type="submit" class="btn btn-primary">Log in</button></div><div id="login-form-error" class="login-error"></div></form></div>',
            ''
          );
          var loginForm = document.getElementById('login-form');
          if (loginForm) {
            loginForm.addEventListener('submit', function(ev) {
              ev.preventDefault();
              var errEl = document.getElementById('login-form-error');
              if (errEl) errEl.textContent = '';
              var fd = new FormData(loginForm);
              fd.append('action', 'login');
              fetch('updates.php', { method: 'POST', body: fd, credentials: 'include' })
                .then(function(res) { return res.json().then(function(d) { return { status: res.status, data: d }; }); })
                .then(function(r) {
                  if (r.status === 200 && r.data && r.data.success) {
                    setGatedVisible(true);
                    setGateMessage('');
                    applyConfigStatus();
                  } else {
                    if (errEl) errEl.textContent = (r.data && r.data.error) || 'Login failed.';
                  }
                })
                .catch(function() { if (errEl) errEl.textContent = 'Login failed.'; });
            });
          }
          var activeTab = document.querySelector('.preset-tab.active');
          if (activeTab && (activeTab.getAttribute('data-preset') || '').indexOf('custom-') === 0) {
            var textTab = document.querySelector('.preset-tab[data-preset="text"]');
            if (textTab) textTab.click();
          }
          return;
        }
        return r.json();
      })
      .then(function(d) {
        if (d === undefined) return;
        var btnLogout = document.getElementById('btn-logout');
        if (d.configured) {
          showApp();
          if (btnLogout) btnLogout.style.display = (d.loggedIn ? '' : 'none');
        } else {
          showOnboarding();
          if (btnLogout) btnLogout.style.display = 'none';
        }
      })
      .catch(function() {
        showApp();
        var btnLogout = document.getElementById('btn-logout');
        if (btnLogout) btnLogout.style.display = 'none';
      });
  }

  (function setupBasicAuthToggle() {
    var useBasicCb = document.getElementById('setup-use-basic');
    var basicFields = document.getElementById('setup-basic-auth-fields');
    if (!useBasicCb || !basicFields) return;
    function toggle() {
      basicFields.classList.toggle('visible', useBasicCb.checked);
    }
    useBasicCb.addEventListener('change', toggle);
    toggle();
  })();

  if (onboardingForm) {
    onboardingForm.addEventListener('submit', function(e) {
      e.preventDefault();
      if (onboardingError) {
        onboardingError.style.display = 'none';
        onboardingError.textContent = '';
      }
      var useBasic = document.getElementById('setup-use-basic') && document.getElementById('setup-use-basic').checked;
      var ip = (document.getElementById('setup-ip') && document.getElementById('setup-ip').value || '').trim();
      var user = (document.getElementById('setup-user') && document.getElementById('setup-user').value || '').trim();
      var pass = (document.getElementById('setup-password') && document.getElementById('setup-password').value || '').trim();
      if (ip === '' && (!useBasic || user === '' || pass === '')) {
        if (onboardingError) {
          onboardingError.textContent = 'Set at least an IP allowlist or enable login with username and password.';
          onboardingError.style.display = 'block';
        }
        return;
      }
      var formData = new FormData(onboardingForm);
      formData.append('action', 'save-initial-config');
      fetch('updates.php', { method: 'POST', body: formData, credentials: 'include' })
        .then(function(r) { return r.json().then(function(d) { return { status: r.status, data: d }; }); })
        .then(function(res) {
          if (res.status >= 400 && res.data && res.data.error) {
            if (onboardingError) {
              onboardingError.textContent = res.data.error;
              onboardingError.style.display = 'block';
            }
            return;
          }
          if (res.data && res.data.success) {
            showApp();
          }
        })
        .catch(function() {
          if (onboardingError) {
            onboardingError.textContent = 'Save failed. Try again.';
            onboardingError.style.display = 'block';
          }
        });
    });
  }

  function loadVersion() {
    fetch('updates.php?action=check', { credentials: 'include' })
      .then(function(r) {
        if (r.status === 401) {
          versionEl.textContent = 'Version — (login to check)';
          return null;
        }
        return r.json();
      })
      .then(function(d) {
        if (d && d.currentVersion) versionEl.textContent = 'Version ' + d.currentVersion;
      })
      .catch(function() { versionEl.textContent = 'Version —'; });
  }

  checkBtn.addEventListener('click', function() {
    checkBtn.disabled = true;
    setMsg('Checking…', 'loading');
    upgradeBtn.style.display = 'none';
    fetch('updates.php?action=check', { credentials: 'include' })
      .then(function(r) {
        if (r.status === 401) {
          var link = document.createElement('a');
          link.href = 'updates.php?action=check';
          link.target = '_blank';
          link.rel = 'noopener';
          link.textContent = 'Log in';
          msgEl.textContent = '';
          msgEl.innerHTML = 'Authentication required. ';
          msgEl.appendChild(link);
          msgEl.appendChild(document.createTextNode(' to log in, then retry.'));
          msgEl.className = 'update-msg error';
          return null;
        }
        return r.json();
      })
      .then(function(d) {
        if (d === null) return;
        if (d.error) {
          setMsg(d.error, 'error');
          return;
        }
        if (d.updateAvailable && d.latestVersion) {
          setMsg('Update available: ' + d.latestVersion, 'has-update');
          upgradeBtn.textContent = d.installType === 'zip' ? 'Download latest' : 'Upgrade (git pull)';
          upgradeBtn.dataset.installType = d.installType || 'git';
          upgradeBtn.dataset.releaseUrl = d.releaseUrl || '';
          upgradeBtn.style.display = 'inline-flex';
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
    fetch('updates.php', { method: 'POST', body: form, credentials: 'include' })
      .then(function(r) {
        if (r.status === 401) {
          setMsg('Authentication required. Log in via the updates page, then retry.', 'error');
          return null;
        }
        return r.json();
      })
      .then(function(d) {
        if (d === null) return;
        if (d.noGit && d.releaseUrl) {
          window.open(d.releaseUrl, '_blank', 'noopener,noreferrer');
          setMsg('Open the release page, download the zip, and replace the files.', 'has-update');
          return;
        }
        if (d.success) {
          setMsg('Upgrade complete. Reload the page.', 'has-update');
          upgradeBtn.style.display = 'none';
          loadVersion();
        } else {
          setMsg((d.error || 'Upgrade failed.') + (d.output ? ' ' + d.output : ''), 'error');
        }
      })
      .catch(function() { setMsg('Upgrade request failed.', 'error'); })
      .finally(function() { upgradeBtn.disabled = false; });
  });

  document.addEventListener('click', function(e) {
    var btn = e.target && e.target.closest ? e.target.closest('#btn-logout') : null;
    if (!btn) return;
    e.preventDefault();
    if (btn.disabled) return;
    btn.disabled = true;
    var fd = new FormData();
    fd.append('action', 'logout');
    fetch('updates.php?action=logout', { method: 'POST', body: fd, credentials: 'include' })
      .then(function() { applyConfigStatus(); })
      .catch(function() { applyConfigStatus(); })
      .finally(function() { btn.disabled = false; });
  });

  applyConfigStatus();
})();
  </script>
</body>
</html>
