<?php
/** Tiny autoloader for installs without Composer: require_once 'src/autoload.php'; */
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'BgCut\\')) {
        $file = __DIR__ . '/' . substr($class, 6) . '.php';
        if (is_file($file)) require $file;
    }
});
