<?php

// Lightweight storage migration regression test. Compatible with PHP 5.6+.

define('VERSION', '2.1.1');
require_once dirname(__DIR__) . '/storage.php';

function menu_test_fail($message)
{
    fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
    exit(1);
}

function menu_test_assert($condition, $message)
{
    if (!$condition) {
        menu_test_fail($message);
    }
}

function menu_test_remove_tree($path)
{
    if (!file_exists($path)) {
        return;
    }
    if (is_file($path) || is_link($path)) {
        @unlink($path);
        return;
    }
    $items = scandir($path);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        menu_test_remove_tree($path . DIRECTORY_SEPARATOR . $item);
    }
    @rmdir($path);
}

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'geeklog-menu-storage-' . uniqid('', true);
$dataRoot = $root . DIRECTORY_SEPARATOR . 'data';
$htmlRoot = $root . DIRECTORY_SEPARATOR . 'public_html';
@mkdir($dataRoot, 0755, true);
@mkdir($htmlRoot . DIRECTORY_SEPARATOR . 'images', 0755, true);

$sites = array('ecologie', 'site2');

foreach ($sites as $site) {
    $_CONF = array(
        'path_data' => $dataRoot . DIRECTORY_SEPARATOR . $site . DIRECTORY_SEPARATOR,
        'path_html' => $htmlRoot . DIRECTORY_SEPARATOR,
        'path_images' => $htmlRoot . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR
            . $site . DIRECTORY_SEPARATOR,
        'site_url' => 'https://example.test/' . $site,
    );

    $legacy = $_CONF['path_data'] . 'menu_data' . DIRECTORY_SEPARATOR;
    @mkdir($legacy . 'cache', 0755, true);
    @mkdir($legacy . 'css', 0755, true);

    file_put_contents($legacy . 'cache' . DIRECTORY_SEPARATOR . 'old.cache', 'cache-' . $site);
    file_put_contents($legacy . 'css' . DIRECTORY_SEPARATOR . 'gl_menu1.css', 'legacy-' . $site);

    menu_test_assert(MENU_migrateLegacyData(), 'migration failed for ' . $site);

    $target = $dataRoot . DIRECTORY_SEPARATOR . $site . '-menu' . DIRECTORY_SEPARATOR;
    menu_test_assert(MENU_dataDir() === $target, 'unexpected target path for ' . $site);
    menu_test_assert(is_dir($target . 'cache'), 'cache directory missing for ' . $site);
    menu_test_assert(is_dir($target . 'css'), 'css directory missing for ' . $site);
    menu_test_assert(
        file_get_contents($target . 'css' . DIRECTORY_SEPARATOR . 'gl_menu1.css') === 'legacy-' . $site,
        'CSS was not migrated for ' . $site
    );
    menu_test_assert(
        file_exists($legacy . 'css' . DIRECTORY_SEPARATOR . 'gl_menu1.css'),
        'legacy CSS was deleted for ' . $site
    );

    $expectedImageDir = $htmlRoot . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR
        . $site . DIRECTORY_SEPARATOR . 'menu' . DIRECTORY_SEPARATOR;
    $expectedImageUrl = 'https://example.test/' . $site . '/images/' . $site . '/menu/';

    menu_test_assert(MENU_imageDir() === $expectedImageDir, 'unexpected image directory for ' . $site);
    menu_test_assert(MENU_imageUrl() === $expectedImageUrl, 'unexpected image URL for ' . $site);
    menu_test_assert(MENU_ensureImageDir(), 'image directory creation failed for ' . $site);
    menu_test_assert(is_dir($expectedImageDir), 'image directory missing for ' . $site);

    // Existing target data must win on repeated migrations.
    file_put_contents($target . 'css' . DIRECTORY_SEPARATOR . 'gl_menu1.css', 'target-wins-' . $site);
    file_put_contents($legacy . 'css' . DIRECTORY_SEPARATOR . 'gl_menu1.css', 'legacy-changed-' . $site);

    menu_test_assert(MENU_migrateLegacyData(), 'second migration failed for ' . $site);
    menu_test_assert(
        file_get_contents($target . 'css' . DIRECTORY_SEPARATOR . 'gl_menu1.css') === 'target-wins-' . $site,
        'existing destination was overwritten for ' . $site
    );
}

menu_test_assert(
    file_get_contents($dataRoot . DIRECTORY_SEPARATOR . 'ecologie-menu' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'gl_menu1.css')
        !== file_get_contents($dataRoot . DIRECTORY_SEPARATOR . 'site2-menu' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'gl_menu1.css'),
    'multisite storage is not isolated'
);

menu_test_remove_tree($root);
echo "Storage migration tests passed" . PHP_EOL;
