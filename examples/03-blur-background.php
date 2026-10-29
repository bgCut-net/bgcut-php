<?php
/** Keep the original background but blur it (portrait / "bokeh" effect). */
$bg = require __DIR__ . '/_bootstrap.php';

$in = $argv[1] ?? __DIR__ . '/sample.jpg';
$result = $bg->removeFile($in, [
    'model' => 'portrait',  // fastest model, optimised for people – use 'lite' for objects
    'bg_blur' => 25,        // blur radius in px (1–200)
    'format' => 'webp',
    'size' => 'preview',
]);
$result->save(__DIR__ . '/output/blurred.webp');
echo "Saved examples/output/blurred.webp\n";
