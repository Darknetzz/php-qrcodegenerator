<?php
/**
 * QR code image endpoint. Outputs PNG or SVG.
 * GET/POST: text, size, margin, fg, bg, level, format
 *
 * Uses chillerlan/php-qrcode (https://github.com/chillerlan/php-qrcode).
 */
require_once __DIR__ . '/load_config.php';
security_headers();
require_app_access(realpath(__DIR__));
require_once __DIR__ . '/vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;

/** @return int[]|null [R, G, B] or null if invalid */
function hex_to_rgb(string $hex): ?array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return null;
    }
    return [
        (int) hexdec(substr($hex, 0, 2)),
        (int) hexdec(substr($hex, 2, 2)),
        (int) hexdec(substr($hex, 4, 2)),
    ];
}

/** Dark module types that get the foreground color */
const DARK_MODULE_TYPES = [
    QRMatrix::M_DARKMODULE, QRMatrix::M_DATA_DARK, QRMatrix::M_FINDER_DARK,
    QRMatrix::M_SEPARATOR_DARK, QRMatrix::M_ALIGNMENT_DARK, QRMatrix::M_TIMING_DARK,
    QRMatrix::M_FORMAT_DARK, QRMatrix::M_VERSION_DARK, QRMatrix::M_QUIETZONE_DARK,
    QRMatrix::M_LOGO_DARK, QRMatrix::M_FINDER_DOT,
];

/** Light module types that get the background color */
const LIGHT_MODULE_TYPES = [
    QRMatrix::M_NULL, QRMatrix::M_DARKMODULE_LIGHT, QRMatrix::M_DATA, QRMatrix::M_FINDER,
    QRMatrix::M_SEPARATOR, QRMatrix::M_ALIGNMENT, QRMatrix::M_TIMING, QRMatrix::M_FORMAT,
    QRMatrix::M_VERSION, QRMatrix::M_QUIETZONE, QRMatrix::M_LOGO, QRMatrix::M_FINDER_DOT_LIGHT,
];

/** Module values for GdImage PNG: dark => fg [R,G,B], light => bg [R,G,B] */
function module_values_rgb(array $fg, array $bg): array {
    $out = [];
    foreach (DARK_MODULE_TYPES as $type) {
        $out[$type] = $fg;
    }
    foreach (LIGHT_MODULE_TYPES as $type) {
        $out[$type] = $bg;
    }
    return $out;
}

/** Module values for SVG: dark => fg hex, light => bg hex */
function module_values_hex(string $fg, string $bg): array {
    $out = [];
    foreach (DARK_MODULE_TYPES as $type) {
        $out[$type] = $fg;
    }
    foreach (LIGHT_MODULE_TYPES as $type) {
        $out[$type] = $bg;
    }
    return $out;
}

$text    = isset($_REQUEST['text']) ? (string) $_REQUEST['text'] : '';
$size    = isset($_REQUEST['size']) ? max(1, min(20, (int) $_REQUEST['size'])) : 6;
$margin  = isset($_REQUEST['margin']) ? max(0, min(20, (int) $_REQUEST['margin'])) : 4;
$fgHex   = isset($_REQUEST['fg']) ? (string) $_REQUEST['fg'] : '#000000';
$bgHex   = isset($_REQUEST['bg']) ? (string) $_REQUEST['bg'] : '#ffffff';
$level   = isset($_REQUEST['level']) ? strtoupper((string) $_REQUEST['level']) : 'L';
$format  = isset($_REQUEST['format']) ? strtolower((string) $_REQUEST['format']) : 'png';
$download = !empty($_REQUEST['download']);

$levels = ['L' => EccLevel::L, 'M' => EccLevel::M, 'Q' => EccLevel::Q, 'H' => EccLevel::H];
$eccLevel = $levels[$level] ?? EccLevel::L;

$fgRgb = hex_to_rgb($fgHex) ?? [0, 0, 0];
$bgRgb = hex_to_rgb($bgHex) ?? [255, 255, 255];
$fgNorm = (str_starts_with($fgHex, '#')) ? $fgHex : '#' . ltrim($fgHex, '#');
$bgNorm = (str_starts_with($bgHex, '#')) ? $bgHex : '#' . ltrim($bgHex, '#');

/** Maximum content length to prevent DoS (QR capacity and CPU). */
const GENERATE_TEXT_MAX_LENGTH = 4000;

if ($text === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Missing or empty "text" parameter.';
    exit;
}
if (strlen($text) > GENERATE_TEXT_MAX_LENGTH) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Content too long. Maximum ' . GENERATE_TEXT_MAX_LENGTH . ' characters.';
    exit;
}

$disposition = $download ? 'attachment' : 'inline';

$baseOptions = [
    'scale'          => $size,
    'quietzoneSize'   => $margin,
    'eccLevel'       => $eccLevel,
    'outputBase64'    => false,
];

if ($format === 'svg') {
    $options = new QROptions(array_merge($baseOptions, [
        'outputType'   => QROutputInterface::MARKUP_SVG,
        'bgColor'      => $bgNorm,
        'moduleValues' => module_values_hex($fgNorm, $bgNorm),
    ]));
    $qr = new QRCode($options);
    $output = $qr->render($text);

    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Content-Disposition: ' . $disposition . '; filename="qrcode.svg"');
    echo $output;
    exit;
}

$options = new QROptions(array_merge($baseOptions, [
    'outputType'   => QROutputInterface::GDIMAGE_PNG,
    'bgColor'      => $bgRgb,
    'moduleValues' => module_values_rgb($fgRgb, $bgRgb),
]));
$qr = new QRCode($options);
$output = $qr->render($text);

header('Content-Type: image/png');
header('Content-Disposition: ' . $disposition . '; filename="qrcode.png"');
echo $output;
