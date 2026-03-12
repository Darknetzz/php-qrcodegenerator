<?php
/**
 * QR code image endpoint. Outputs PNG or SVG.
 * GET/POST: text, size, margin, fg, bg, level, format
 *
 * Uses chillerlan/php-qrcode (https://github.com/chillerlan/php-qrcode).
 */
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

/** Dark module types that get the foreground color (GdImage uses [R,G,B]) */
function dark_module_values_rgb(array $fg): array {
    $dark = [
        QRMatrix::M_DARKMODULE, QRMatrix::M_DATA_DARK, QRMatrix::M_FINDER_DARK,
        QRMatrix::M_SEPARATOR_DARK, QRMatrix::M_ALIGNMENT_DARK, QRMatrix::M_TIMING_DARK,
        QRMatrix::M_FORMAT_DARK, QRMatrix::M_VERSION_DARK, QRMatrix::M_QUIETZONE_DARK,
        QRMatrix::M_LOGO_DARK, QRMatrix::M_FINDER_DOT,
    ];
    $out = [];
    foreach ($dark as $type) {
        $out[$type] = $fg;
    }
    return $out;
}

/** Dark module types for SVG (hex string) */
function dark_module_values_hex(string $fg): array {
    $dark = [
        QRMatrix::M_DARKMODULE, QRMatrix::M_DATA_DARK, QRMatrix::M_FINDER_DARK,
        QRMatrix::M_SEPARATOR_DARK, QRMatrix::M_ALIGNMENT_DARK, QRMatrix::M_TIMING_DARK,
        QRMatrix::M_FORMAT_DARK, QRMatrix::M_VERSION_DARK, QRMatrix::M_QUIETZONE_DARK,
        QRMatrix::M_LOGO_DARK, QRMatrix::M_FINDER_DOT,
    ];
    $out = [];
    foreach ($dark as $type) {
        $out[$type] = $fg;
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

if ($text === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Missing or empty "text" parameter.';
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
        'moduleValues' => dark_module_values_hex($fgNorm),
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
    'moduleValues' => dark_module_values_rgb($fgRgb),
]));
$qr = new QRCode($options);
$output = $qr->render($text);

header('Content-Type: image/png');
header('Content-Disposition: ' . $disposition . '; filename="qrcode.png"');
echo $output;
