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
    input[type="url"],
    input[type="email"],
    input[type="tel"],
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
    input[type="text"]:focus,
    input[type="number"]:focus,
    input[type="url"]:focus,
    input[type="email"]:focus,
    input[type="tel"]:focus,
    textarea:focus {
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
    }
    .btn-group {
      display: flex;
      flex-direction: column;
      width: 100%;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid var(--border);
      background: var(--input-bg);
    }
    .btn-group .btn {
      width: 100%;
      box-sizing: border-box;
      border-radius: 0;
      border: none;
      border-bottom: 1px solid var(--border);
    }
    .btn-group .btn:last-child {
      border-bottom: none;
    }
    .btn-group .btn-primary {
      background: var(--accent);
      color: #fff;
    }
    .btn-group .btn-primary:hover {
      background: var(--accent-hover);
      color: #fff;
    }
    .btn-icon { width: 1.1em; height: 1.1em; margin-right: 0.4rem; flex-shrink: 0; }
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
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
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
    .preset-tab .tab-icon { width: 1.1em; height: 1.1em; flex-shrink: 0; opacity: 0.9; }
    .label-icon { display: inline-block; width: 1em; height: 1em; margin-right: 0.4rem; vertical-align: -0.15em; opacity: 0.85; }
    label { display: flex; align-items: center; }
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
    .updates-row {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex-wrap: wrap;
      margin-top: 0.75rem;
    }
    .updates-row .btn { padding: 0.4rem 0.75rem; font-size: 0.8rem; }
    .updates-row .version { color: var(--muted); font-size: 0.85rem; }
    .updates-row .update-msg { font-size: 0.85rem; }
    .updates-row .update-msg.has-update { color: var(--accent); }
    .updates-row .update-msg.error { color: #f87171; }
    .updates-row .update-msg.loading { color: var(--muted); }
  </style>
</head>
<body>
  <svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;pointer-events:none" aria-hidden="true">
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
          <img id="preview" src="" alt="QR code preview" style="display:none;">
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
    <div class="foot updates-row" id="updates-row" aria-live="polite">
      <span class="version" id="current-version">—</span>
      <button type="button" class="btn btn-secondary" id="btn-check-updates" aria-label="Check for updates">
        <svg class="btn-icon" aria-hidden="true"><use href="#icon-refresh"/></svg>Check for updates
      </button>
      <span class="update-msg" id="update-msg"></span>
      <button type="button" class="btn btn-primary" id="btn-upgrade" style="display:none;"><svg class="btn-icon" aria-hidden="true"><use href="#icon-arrow-up"/></svg>Upgrade (git pull)</button>
    </div>
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

  var saved = null;
  try { saved = sessionStorage.getItem(STORAGE_KEY); } catch (e) {}
  var initial = (saved && PRESET_IDS.indexOf(saved) !== -1) ? saved : 'text';
  setPreset(initial);
})();

(function updatesUi() {
  var versionEl = document.getElementById('current-version');
  var msgEl = document.getElementById('update-msg');
  var checkBtn = document.getElementById('btn-check-updates');
  var upgradeBtn = document.getElementById('btn-upgrade');

  function setMsg(text, className) {
    msgEl.textContent = text || '';
    msgEl.className = 'update-msg' + (className ? ' ' + className : '');
  }

  function loadVersion() {
    fetch('updates.php?action=check')
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (d.currentVersion) versionEl.textContent = 'Version ' + d.currentVersion;
      })
      .catch(function() { versionEl.textContent = 'Version —'; });
  }

  checkBtn.addEventListener('click', function() {
    checkBtn.disabled = true;
    setMsg('Checking…', 'loading');
    upgradeBtn.style.display = 'none';
    fetch('updates.php?action=check')
      .then(function(r) { return r.json(); })
      .then(function(d) {
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
    fetch('updates.php', { method: 'POST', body: form })
      .then(function(r) { return r.json(); })
      .then(function(d) {
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

  loadVersion();
})();
  </script>
</body>
</html>
