<?php

// Theme presentation contract regression test. Compatible with PHP 5.6+.
define('VERSION', '2.1.1');

$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'menu-theme-presentation-' . uniqid('', true);
$layout = $base . DIRECTORY_SEPARATOR . 'layout' . DIRECTORY_SEPARATOR . 'eclipse' . DIRECTORY_SEPARATOR;
if (!mkdir($layout, 0755, true) && !is_dir($layout)) {
    fwrite(STDERR, "FAIL: unable to create temporary layout\n");
    exit(1);
}

$_CONF = array('path_layout' => $layout);

file_put_contents(
    $layout . 'plugin-presentation.php',
    "<?php\nreturn array('menu' => array('navigation'));\n"
);

require_once dirname(__DIR__) . '/presentation.php';

function menu_presentation_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

menu_presentation_assert(MENU_themeHandlesPresentation('navigation') === true, 'navigation should be theme-owned');
menu_presentation_assert(MENU_themeHandlesPresentation('Navigation') === true, 'resource matching should be case-insensitive');
menu_presentation_assert(MENU_themeHandlesPresentation('footer') === false, 'undeclared footer must keep legacy Menu presentation');

@unlink($layout . 'plugin-presentation.php');
@rmdir($layout);
@rmdir(dirname($layout));
@rmdir($base);

echo "Theme presentation contract tests passed" . PHP_EOL;
