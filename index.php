<?php
$title = 'QR Code Generator';
$defaultText = 'https://example.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($title); ?></title>
  <style>
    :root {
      --bg: #0f0f12;
      --surface: #18181c;
      --border: #2a2a32;
      --text: #e4e4e7;
      --muted: #71717a;
      --accent: #22c55e;
      --accent-hover: #16a34a;
      --input-bg: #27272a;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
      min-height: 100vh;
    }
    .wrap {
      max-width: 56rem;
      margin: 0 auto;
      padding: 2rem 1.5rem;
    }
    h1 {
      font-size: 1.75rem;
      font-weight: 700;
      margin: 0 0 0.5rem;
      letter-spacing: -0.02em;
    }
    .tagline {
      color: var(--muted);
      margin: 0 0 2rem;
      font-size: 0.95rem;
    }
    .grid {
      display: grid;
      gap: 2rem;
    }
    @media (min-width: 768px) {
      .grid { grid-template-columns: 1fr 320px; }
    }
    .panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.5rem;
    }
    .panel h2 {
      font-size: 1rem;
      font-weight: 600;
      margin: 0 0 1rem;
      color: var(--text);
    }
    label {
      display: block;
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--muted);
      margin-bottom: 0.35rem;
    }
    input[type="text"],
    input[type="number"],
    textarea {
      width: 100%;
      padding: 0.6rem 0.75rem;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      font-size: 0.95rem;
      margin-bottom: 1rem;
    }
    textarea {
      min-height: 100px;
      resize: vertical;
    }
    input:focus, textarea:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
    }
    .row {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .field { flex: 1 1 120px; min-width: 0; }
    .color-wrap {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    input[type="color"] {
      width: 2.5rem;
      height: 2.25rem;
      padding: 2px;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: var(--input-bg);
      cursor: pointer;
    }
    input[type="color"] + input[type="text"] {
      flex: 1;
      margin-bottom: 0;
      font-family: ui-monospace, monospace;
    }
    select {
      width: 100%;
      padding: 0.6rem 0.75rem;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text);
      font-size: 0.95rem;
      margin-bottom: 1rem;
      cursor: pointer;
    }
    .preview-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 240px;
      padding: 1.5rem;
      background: #fff;
      border-radius: 10px;
      border: 1px solid var(--border);
    }
    .preview-wrap img {
      max-width: 100%;
      height: auto;
      display: block;
    }
    .preview-placeholder {
      color: var(--muted);
      font-size: 0.9rem;
    }
    .actions {
      margin-top: 1.25rem;
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0.6rem 1.1rem;
      font-size: 0.9rem;
      font-weight: 500;
      border-radius: 8px;
      text-decoration: none;
      cursor: pointer;
      border: none;
      font-family: inherit;
      transition: background 0.15s, color 0.15s;
    }
    .btn-primary {
      background: var(--accent);
      color: #fff;
    }
    .btn-primary:hover {
      background: var(--accent-hover);
      color: #fff;
    }
    .btn-secondary {
      background: var(--input-bg);
      color: var(--text);
      border: 1px solid var(--border);
    }
    .btn-secondary:hover {
      background: var(--border);
      color: var(--text);
    }
    .foot {
      margin-top: 2.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border);
      color: var(--muted);
      font-size: 0.8rem;
    }
    .foot a { color: var(--accent); }

    .preset-tabs {
      display: flex;
      flex-wrap: wrap;
      gap: 0.35rem;
      margin-bottom: 1.25rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border);
    }
    .preset-tab {
      padding: 0.45rem 0.75rem;
      font-size: 0.8rem;
      font-weight: 500;
      color: var(--muted);
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 6px;
      cursor: pointer;
      transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .preset-tab:hover { color: var(--text); border-color: var(--muted); }
    .preset-tab.active {
      color: var(--accent);
      border-color: var(--accent);
      background: rgba(34, 197, 94, 0.1);
    }
    .preset-panel { display: none; }
    .preset-panel.active { display: block; }
    .checkbox-row { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
    .checkbox-row input[type="checkbox"] { width: auto; margin: 0; cursor: pointer; }
    .checkbox-row label { margin: 0; cursor: pointer; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p class="tagline">Create QR codes for URLs, text, or any content. No sign-up, no tracking.</p>

    <div class="grid">
      <div class="panel">
        <h2>Content &amp; options</h2>
        <div class="preset-tabs" role="tablist" aria-label="QR code type">
          <button type="button" class="preset-tab" data-preset="url" role="tab">URL</button>
          <button type="button" class="preset-tab" data-preset="wifi" role="tab">Wi‑Fi</button>
          <button type="button" class="preset-tab" data-preset="vcard" role="tab">vCard</button>
          <button type="button" class="preset-tab active" data-preset="text" role="tab">Text</button>
          <button type="button" class="preset-tab" data-preset="email" role="tab">Email</button>
          <button type="button" class="preset-tab" data-preset="sms" role="tab">SMS</button>
          <button type="button" class="preset-tab" data-preset="bitcoin" role="tab">Bitcoin</button>
          <button type="button" class="preset-tab" data-preset="facebook" role="tab">Facebook</button>
          <button type="button" class="preset-tab" data-preset="pdf" role="tab">PDF</button>
          <button type="button" class="preset-tab" data-preset="mp3" role="tab">MP3</button>
          <button type="button" class="preset-tab" data-preset="appstore" role="tab">App Store</button>
          <button type="button" class="preset-tab" data-preset="image" role="tab">Image</button>
          <button type="button" class="preset-tab" data-preset="custom" role="tab">Custom</button>
        </div>
        <form id="qr-form" method="get" action="" autocomplete="off">
          <div id="preset-url" class="preset-panel">
            <label for="url">Website URL</label>
            <input type="url" id="url" placeholder="https://example.com" value="https://example.com" autocomplete="off">
          </div>
          <div id="preset-wifi" class="preset-panel">
            <label for="wifi-ssid">Network name (SSID)</label>
            <input type="text" id="wifi-ssid" placeholder="MyNetwork" autocomplete="off">
            <div class="checkbox-row">
              <input type="checkbox" id="wifi-hidden" aria-describedby="wifi-hidden-desc">
              <label for="wifi-hidden" id="wifi-hidden-desc">Hidden network</label>
            </div>
            <label for="wifi-password">Password</label>
            <input type="text" id="wifi-password" placeholder="Leave empty for open networks" autocomplete="off">
            <label for="wifi-encryption">Encryption</label>
            <select id="wifi-encryption">
              <option value="nopass">None (open)</option>
              <option value="WPA" selected>WPA / WPA2</option>
              <option value="WEP">WEP</option>
            </select>
          </div>
          <div id="preset-vcard" class="preset-panel">
            <label for="vcard-name">Full name</label>
            <input type="text" id="vcard-name" placeholder="John Doe" autocomplete="off">
            <label for="vcard-org">Organization</label>
            <input type="text" id="vcard-org" placeholder="Company" autocomplete="off">
            <label for="vcard-tel">Phone</label>
            <input type="tel" id="vcard-tel" placeholder="+1 234 567 8900" autocomplete="off">
            <label for="vcard-email">Email</label>
            <input type="email" id="vcard-email" placeholder="john@example.com" autocomplete="off">
          </div>
          <div id="preset-text" class="preset-panel active">
            <label for="text">Plain text</label>
            <textarea id="text" name="text" placeholder="Enter any text..." autocomplete="off"></textarea>
          </div>
          <div id="preset-email" class="preset-panel">
            <label for="email-addr">Email address</label>
            <input type="email" id="email-addr" placeholder="you@example.com" autocomplete="off">
            <label for="email-subject">Subject</label>
            <input type="text" id="email-subject" placeholder="Optional" autocomplete="off">
            <label for="email-body">Body</label>
            <textarea id="email-body" placeholder="Optional" rows="3" autocomplete="off"></textarea>
          </div>
          <div id="preset-sms" class="preset-panel">
            <label for="sms-number">Phone number</label>
            <input type="tel" id="sms-number" placeholder="+1234567890" autocomplete="off">
            <label for="sms-message">Message</label>
            <textarea id="sms-message" placeholder="Pre-filled message (optional)" rows="3" autocomplete="off"></textarea>
          </div>
          <div id="preset-bitcoin" class="preset-panel">
            <label for="btc-address">Bitcoin address</label>
            <input type="text" id="btc-address" placeholder="bc1q... or 1..." autocomplete="off">
            <label for="btc-amount">Amount (BTC, optional)</label>
            <input type="text" id="btc-amount" placeholder="0.01" autocomplete="off">
            <label for="btc-label">Label (optional)</label>
            <input type="text" id="btc-label" placeholder="Payment for..." autocomplete="off">
          </div>
          <div id="preset-facebook" class="preset-panel">
            <label for="facebook-url">Facebook page or profile URL</label>
            <input type="url" id="facebook-url" placeholder="https://www.facebook.com/..." autocomplete="off">
          </div>
          <div id="preset-pdf" class="preset-panel">
            <label for="pdf-url">Link to PDF file</label>
            <input type="url" id="pdf-url" placeholder="https://example.com/document.pdf" autocomplete="off">
          </div>
          <div id="preset-mp3" class="preset-panel">
            <label for="mp3-url">Link to audio file (MP3, etc.)</label>
            <input type="url" id="mp3-url" placeholder="https://example.com/audio.mp3" autocomplete="off">
          </div>
          <div id="preset-appstore" class="preset-panel">
            <label for="appstore-url">App store or play store URL</label>
            <input type="url" id="appstore-url" placeholder="https://apps.apple.com/... or https://play.google.com/..." autocomplete="off">
          </div>
          <div id="preset-image" class="preset-panel">
            <label for="image-url">Link to image</label>
            <input type="url" id="image-url" placeholder="https://example.com/image.png" autocomplete="off">
          </div>
          <div id="preset-custom" class="preset-panel">
            <label for="custom-text">Raw content (URL, vCard, or any string)</label>
            <textarea id="custom-text" placeholder="Paste or type any content to encode" autocomplete="off"></textarea>
          </div>

          <div class="row">
            <div class="field">
              <label for="size">Module size (pixels)</label>
              <input type="number" id="size" name="size" value="6" min="1" max="20" step="1" autocomplete="off">
            </div>
            <div class="field">
              <label for="margin">Margin (modules)</label>
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
              <label>Foreground color</label>
              <div class="color-wrap">
                <input type="color" id="fg-color" value="#000000" aria-label="Foreground color">
                <input type="text" id="fg" name="fg" value="#000000" maxlength="7" placeholder="#000000" autocomplete="off">
              </div>
            </div>
            <div class="field">
              <label>Background color</label>
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
          <img id="preview" src="" alt="QR code preview" style="display:none;">
          <span id="preview-placeholder" class="preview-placeholder">Enter content to see preview</span>
        </div>
        <div class="actions">
          <a id="dl-png" class="btn btn-primary" href="#" download="qrcode.png">Download PNG</a>
          <a id="dl-svg" class="btn btn-secondary" href="#" download="qrcode.svg">Download SVG</a>
        </div>
      </div>
    </div>

    <p class="foot">
      Uses <a href="https://github.com/chillerlan/php-qrcode" target="_blank" rel="noopener">chillerlan/php-qrcode</a> (MIT).
      No data is stored on the server. For very long content, use the download buttons.
    </p>
  </div>

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
  var tabButtons = document.querySelectorAll('.preset-tab');
  var panels = document.querySelectorAll('.preset-panel');

  var PRESET_IDS = ['url', 'wifi', 'vcard', 'text', 'email', 'sms', 'bitcoin', 'facebook', 'pdf', 'mp3', 'appstore', 'image', 'custom'];
  var STORAGE_KEY = 'qr-preset';

  function setPreset(id) {
    currentPreset = id;
    tabButtons.forEach(function(btn) {
      btn.classList.toggle('active', btn.getAttribute('data-preset') === id);
    });
    panels.forEach(function(panel) {
      panel.classList.toggle('active', panel.id === 'preset-' + id);
    });
    try { sessionStorage.setItem(STORAGE_KEY, id); } catch (e) {}
    update();
  }

  tabButtons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      setPreset(btn.getAttribute('data-preset'));
    });
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
      default:
        return trim(v('custom-text')) || '';
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
    var url = buildUrl('png');
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

  var saved = null;
  try { saved = sessionStorage.getItem(STORAGE_KEY); } catch (e) {}
  var initial = (saved && PRESET_IDS.indexOf(saved) !== -1) ? saved : 'text';
  setPreset(initial);
})();
  </script>
</body>
</html>
