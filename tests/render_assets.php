<?php

// Frontend rendering asset regression test. Compatible with PHP 5.6+.

function menu_render_test_fail($message)
{
    fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
    exit(1);
}

function menu_render_test_assert($condition, $message)
{
    if (!$condition) {
        menu_render_test_fail($message);
    }
}

$root = dirname(__DIR__);
$required = array(
    'gl_horizontal-cascading.thtml',
    'gl_horizontal-simple.thtml',
    'gl_vertical-cascading.thtml',
    'gl_vertical-simple.thtml',
);

foreach ($required as $file) {
    $frontend = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $file;
    $default = $root . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
        . 'default' . DIRECTORY_SEPARATOR . $file;

    menu_render_test_assert(is_file($frontend), 'frontend style template missing: ' . $file);
    menu_render_test_assert(is_file($default), 'default style template missing: ' . $file);
    menu_render_test_assert(filesize($frontend) > 0, 'frontend style template is empty: ' . $file);
}

$preview = file_get_contents($root . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'preview.php');
menu_render_test_assert(
    strpos($preview, 'MENU_imageUrl()') !== false,
    'preview does not use the multisite image URL helper'
);

$storage = file_get_contents($root . DIRECTORY_SEPARATOR . 'storage.php');
menu_render_test_assert(
    strpos($storage, 'function MENU_imageDir()') !== false,
    'MENU_imageDir helper is missing'
);
menu_render_test_assert(
    strpos($storage, 'function MENU_imageUrl()') !== false,
    'MENU_imageUrl helper is missing'
);

$functions = file_get_contents($root . DIRECTORY_SEPARATOR . 'functions.inc');
menu_render_test_assert(
    strpos($functions, '$needsSlickNav = false;') !== false,
    'SlickNav loading must use a dedicated need flag'
);
menu_render_test_assert(
    strpos($functions, "(int) \$menu['menu_type'] === 1") !== false,
    'SlickNav assets must be limited to horizontal cascading menus'
);
menu_render_test_assert(
    strpos($functions, 'if ($needsSlickNav && $loadLegacyCss)') !== false,
    'SlickNav CSS must only load when a compatible menu needs it'
);
menu_render_test_assert(
    strpos($functions, 'if ($needsSlickNav && $loadLegacyJs)') !== false,
    'SlickNav JavaScript must only load when a compatible menu needs it'
);

echo "Frontend rendering asset tests passed" . PHP_EOL;
