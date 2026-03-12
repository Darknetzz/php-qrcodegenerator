<?php
/**
 * QR code image endpoint. Outputs PNG or SVG.
 * GET/POST: text, size, margin, fg, bg, level, format
 */
require_once __DIR__ . '/phpqrcode.php';

// Parse hex color to 0xRRGGBB integer
function parse_color($hex) {
    $hex = ltrim((string) $hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return null;
    }
    return hexdec($hex);
}

$text    = isset($_REQUEST['text']) ? (string) $_REQUEST['text'] : '';
$size    = isset($_REQUEST['size']) ? max(1, min(20, (int) $_REQUEST['size'])) : 6;
$margin  = isset($_REQUEST['margin']) ? max(0, min(20, (int) $_REQUEST['margin'])) : 4;
$fgHex   = isset($_REQUEST['fg']) ? $_REQUEST['fg'] : '#000000';
$bgHex   = isset($_REQUEST['bg']) ? $_REQUEST['bg'] : '#ffffff';
$level   = isset($_REQUEST['level']) ? $_REQUEST['level'] : 'L';
$format  = isset($_REQUEST['format']) ? strtolower($_REQUEST['format']) : 'png';
$download = !empty($_REQUEST['download']);

$levels = ['L' => QR_ECLEVEL_L, 'M' => QR_ECLEVEL_M, 'Q' => QR_ECLEVEL_Q, 'H' => QR_ECLEVEL_H];
$ecLevel = isset($levels[$level]) ? $levels[$level] : QR_ECLEVEL_L;

$fg = parse_color($fgHex);
$bg = parse_color($bgHex);
if ($fg === null) $fg = 0x000000;
if ($bg === null) $bg = 0xFFFFFF;

if ($text === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Missing or empty "text" parameter.';
    exit;
}

$disposition = $download ? 'attachment' : 'inline';

if ($format === 'svg') {
    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Content-Disposition: ' . $disposition . '; filename="qrcode.svg"');
    QRcode::svg($text, false, $ecLevel, $size, $margin, false, $bg, $fg);
    exit;
}

header('Content-Type: image/png');
header('Content-Disposition: ' . $disposition . '; filename="qrcode.png"');
QRcode::png($text, false, $ecLevel, $size, $margin, false, $bg, $fg);
