<?php
/** Show balance, quota and the last images processed via the API. */
$bg = require __DIR__ . '/_bootstrap.php';

$a = $bg->account();
printf("E-mail: %s\nCredits: %d\nFree previews this month: %d of %d\n\n", $a['email'], $a['credits'], $a['free_remaining'], $a['free_per_month']);

foreach ($bg->images() as $img) {
    printf("%s  %5dx%-5d %-6s %s\n", $img['created_at'] ?? '', $img['width'], $img['height'], $img['status'], $img['name']);
}
