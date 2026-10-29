<?php
/** Shared by the examples: loads the library and the API key. */
require_once file_exists(__DIR__ . '/../vendor/autoload.php') ? __DIR__ . '/../vendor/autoload.php' : __DIR__ . '/../src/autoload.php';

// 1) export BGCUT_API_KEY=bgc_xxx   or   2) paste it below
$apiKey = getenv('BGCUT_API_KEY') ?: 'bgc_your_api_key_here';
if ($apiKey === 'bgc_your_api_key_here') {
    fwrite(STDERR, "Set BGCUT_API_KEY first – create a free key at https://bgcut.net/api-keys\n");
    exit(1);
}
return new BgCut\Client($apiKey);
