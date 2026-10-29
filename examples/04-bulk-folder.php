<?php
/**
 * Bulk: remove backgrounds of every image in a folder.
 *   php examples/04-bulk-folder.php ./photos ./photos-nobg
 * Respects the 60 requests/min rate limit and keeps going on per-file errors.
 */
$bg = require __DIR__ . '/_bootstrap.php';

$src = rtrim($argv[1] ?? __DIR__, '/');
$dst = rtrim($argv[2] ?? __DIR__ . '/output', '/');
@mkdir($dst, 0775, true);

$files = glob("$src/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}", GLOB_BRACE) ?: [];
if (!$files) exit("No images in $src\n");

$account = $bg->account();
printf("Account %s – %d credits, %d free previews left. Processing %d file(s)…\n", $account['email'], $account['credits'], $account['free_remaining'], count($files));

$ok = $fail = 0;
foreach ($files as $i => $file) {
    $out = "$dst/" . pathinfo($file, PATHINFO_FILENAME) . '.png';
    if (is_file($out)) { echo "skip (exists) " . basename($file) . "\n"; continue; }
    try {
        $r = $bg->removeFile($file, ['size' => 'auto', 'model' => 'lite']);
        $r->save($out);
        printf("[%d/%d] %s → %s (%s, %d credits left)\n", $i + 1, count($files), basename($file), basename($out), $r->type, $r->creditsRemaining);
        $ok++;
    } catch (BgCut\ApiException $e) {
        $fail++;
        fprintf(STDERR, "[%d/%d] %s FAILED [%s] %s\n", $i + 1, count($files), basename($file), $e->errorCode, $e->getMessage());
        if ($e->isOutOfCredits()) break;                       // no point continuing
        if ($e->isRateLimited()) sleep($e->retryAfter ?? 60);  // wait for the next minute window
    }
}
echo "Done: $ok ok, $fail failed.\n";
