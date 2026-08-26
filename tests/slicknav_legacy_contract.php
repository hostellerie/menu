<?php

$root = dirname(__DIR__);
$functions = file_get_contents($root . '/functions.inc');

if ($functions === false) {
    fwrite(STDERR, "Unable to read functions.inc\n");
    exit(1);
}

$requiredFiles = array(
    $root . '/public_html/js/jquery.slicknav.js',
    $root . '/public_html/css/slicknav.css',
);

foreach ($requiredFiles as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, 'Missing retained SlickNav compatibility asset: ' . $file . "\n");
        exit(1);
    }
}

$requiredSnippets = array(
    '$needsSlickNav = false;',
    "MENU_runtimeConfigEnabled('legacy_rendering', true)",
    "MENU_runtimeConfigEnabled('load_legacy_css', true)",
    "MENU_runtimeConfigEnabled('load_legacy_js', true)",
    '(int) $menu[\'menu_type\'] === 1',
    '!MENU_themeHandlesPresentation(isset($menu[\'menu_name\']) ? $menu[\'menu_name\'] : \'\')',
    "setCSSFile('menu_slicknav', '/menu/css/slicknav.css')",
    "setJavaScriptFile('slicknav', '/menu/js/jquery.slicknav.js')",
);

foreach ($requiredSnippets as $snippet) {
    if (strpos($functions, $snippet) === false) {
        fwrite(STDERR, 'SlickNav legacy loading contract changed or disappeared: ' . $snippet . "\n");
        exit(1);
    }
}

if (strpos($functions, 'if (!$legacyRendering)') === false) {
    fwrite(STDERR, "Legacy rendering disable gate is missing\n");
    exit(1);
}

echo "SlickNav legacy compatibility contract OK\n";
