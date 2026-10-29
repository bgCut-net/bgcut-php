<?php
/** Remove the background of one local image → transparent PNG. */
$bg = require __DIR__ . '/_bootstrap.php';

$in = $argv[1] ?? __DIR__ . '/sample.jpg';
$out = __DIR__ . '/output/' . pathinfo($in, PATHINFO_FILENAME) . '-nobg.png';

try {
    $result = $bg->removeFile($in, [
        'size' => 'auto',   // 'preview' = free (≤640 px), 'full' = 1 credit, 'auto' = full if you have credits
        'model' => 'lite',  // fast | lite | hq | portrait
        'format' => 'png',
    ]);
    $result->save($out);
    printf("Saved %s (%dx%d, %s, %d credit charged, %d credits left, %d free previews left)\n",
        $out, $result->width, $result->height, $result->type, $result->creditsCharged, $result->creditsRemaining, $result->freeRemaining);
} catch (BgCut\ApiException $e) {
    fwrite(STDERR, "API error [{$e->errorCode}] {$e->getMessage()}\n");
    if ($e->isOutOfCredits()) fwrite(STDERR, "Buy credits at https://bgcut.net/pricing or use size=preview.\n");
    exit(1);
}
