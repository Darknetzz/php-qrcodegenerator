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
  </style>
</head>
<body>
  <div class="wrap">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p class="tagline">Create QR codes for URLs, text, or any content. No sign-up, no tracking.</p>

    <div class="grid">
      <div class="panel">
        <h2>Content &amp; options</h2>
        <form id="qr-form" method="get" action="">
          <label for="text">Content (URL, text, vCard, etc.)</label>
          <textarea id="text" name="text" placeholder="https://example.com"><?php echo htmlspecialchars($defaultText); ?></textarea>

          <div class="row">
            <div class="field">
              <label for="size">Module size (pixels)</label>
              <input type="number" id="size" name="size" value="6" min="1" max="20" step="1">
            </div>
            <div class="field">
              <label for="margin">Margin (modules)</label>
              <input type="number" id="margin" name="margin" value="4" min="0" max="20" step="1">
            </div>
          </div>

          <label for="level">Error correction</label>
          <select id="level" name="level">
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
                <input type="text" id="fg" name="fg" value="#000000" maxlength="7" placeholder="#000000">
              </div>
            </div>
            <div class="field">
              <label>Background color</label>
              <div class="color-wrap">
                <input type="color" id="bg-color" value="#ffffff" aria-label="Background color">
                <input type="text" id="bg" name="bg" value="#ffffff" maxlength="7" placeholder="#ffffff">
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
      Uses <a href="https://github.com/t0k4rt/phpqrcode" target="_blank" rel="noopener">PHP QR Code</a> (LGPL).
      No data is stored on the server. For very long content, use the download buttons.
    </p>
  </div>

  <script>
(function() {
  var form = document.getElementById('qr-form');
  var text = document.getElementById('text');
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

  function buildParams() {
    var t = (text.value || '').trim();
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

  [text, size, margin, level, fg, bg].forEach(function(el) {
    el.addEventListener('input', update);
    el.addEventListener('change', update);
  });

  update();
})();
  </script>
</body>
</html>
