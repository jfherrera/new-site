<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// Print size: 3.9 x 6.7 in at 300 dpi.
const DECAL_W = 1170;
const DECAL_H = 2010;
// Box inside the template's white card where the QR goes (matches tools/decal/build_decal.py).
const DECAL_QR_X = 607;
const DECAL_QR_Y = 1195;
const DECAL_QR_SIZE = 473;

/** QR as PNG bytes, with a 4-module quiet zone, at the given pixel scale per module. */
function decal_qr_png(string $url, int $scale): string
{
    $options = new QROptions([
        'outputType' => QROutputInterface::GDIMAGE_PNG,
        'imageBase64' => false,
        'eccLevel' => EccLevel::H,
        'scale' => $scale,
        'quietzoneSize' => 4,
    ]);
    return (new QRCode($options))->render($url);
}

/** Full print-ready decal for one business, as PNG bytes. */
function decal_png(string $reviewUrl): string
{
    $templatePath = __DIR__ . '/assets/decal-template.png';
    $base = imagecreatefrompng($templatePath);
    if ($base === false) {
        throw new RuntimeException('Decal template is missing.');
    }

    // Pick the largest whole-pixel module size that fits the QR box, so edges stay sharp.
    $probe = imagecreatefromstring(decal_qr_png($reviewUrl, 1));
    $modules = imagesx($probe);
    imagedestroy($probe);
    $scale = max(1, intdiv(DECAL_QR_SIZE, $modules));

    $qr = imagecreatefromstring(decal_qr_png($reviewUrl, $scale));
    if ($qr === false) {
        throw new RuntimeException('QR code could not be rendered.');
    }
    $ox = DECAL_QR_X + intdiv(DECAL_QR_SIZE - imagesx($qr), 2);
    $oy = DECAL_QR_Y + intdiv(DECAL_QR_SIZE - imagesy($qr), 2);
    imagecopy($base, $qr, $ox, $oy, 0, 0, imagesx($qr), imagesy($qr));
    imagedestroy($qr);

    ob_start();
    imagepng($base);
    $png = (string) ob_get_clean();
    imagedestroy($base);
    return $png;
}
