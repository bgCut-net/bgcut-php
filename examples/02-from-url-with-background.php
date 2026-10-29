<?php
/** Process an image from a URL, put it on a white background, crop to the subject and save as JPG. */
$bg = require __DIR__ . '/_bootstrap.php';

$url = $argv[1] ?? 'https://bgcut.net/assets/img/examples/ex-1-dog.jpg';

$result = $bg->removeUrl($url, [
    'size' => 'preview',       // free preview – switch to 'full' for production
    'format' => 'jpg',
    'quality' => 90,
    'bg_color' => '#ffffff',   // e-shop style white background
    'crop' => true,            // crop to the subject…
    'crop_margin' => 20,       // …with a 20 px margin
    'feather' => 1,            // slight edge softening
]);
$result->save(__DIR__ . '/output/product-white.jpg');
echo "Saved examples/output/product-white.jpg ({$result->width}x{$result->height})\n";
