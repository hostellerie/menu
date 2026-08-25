<?php

define('VERSION', '2.1.1');
require_once dirname(__DIR__) . '/cache_filesystem.php';

$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'menu-cache-' . uniqid('', true);
$cache = $base . DIRECTORY_SEPARATOR . 'cache';
$outside = $base . DIRECTORY_SEPARATOR . 'outside';
@mkdir($cache, 0755, true);
@mkdir($outside, 0755, true);
file_put_contents($cache . DIRECTORY_SEPARATOR . 'inside.tmp', 'inside');
file_put_contents($outside . DIRECTORY_SEPARATOR . 'keep.txt', 'outside');

$symlinkSupported = function_exists('symlink')
    && @symlink($outside, $cache . DIRECTORY_SEPARATOR . 'outside-link');

MENU_cache_clean_directories($cache);

if (file_exists($cache . DIRECTORY_SEPARATOR . 'inside.tmp')) {
    fwrite(STDERR, "Cache file was not removed.\n");
    exit(1);
}
if (!file_exists($outside . DIRECTORY_SEPARATOR . 'keep.txt')) {
    fwrite(STDERR, "Cache cleanup escaped its cache root.\n");
    exit(1);
}
if ($symlinkSupported && !is_link($cache . DIRECTORY_SEPARATOR . 'outside-link')) {
    fwrite(STDERR, "Cache cleanup unexpectedly followed or removed the symlink.\n");
    exit(1);
}

if ($symlinkSupported) {
    @unlink($cache . DIRECTORY_SEPARATOR . 'outside-link');
}
@unlink($outside . DIRECTORY_SEPARATOR . 'keep.txt');
@rmdir($cache);
@rmdir($outside);
@rmdir($base);

echo "Menu cache filesystem safety tests passed\n";
