<?php

$root = dirname(__DIR__);
$functions = file_get_contents($root . '/functions.inc');
$assets = file_get_contents($root . '/asset_usage.php');

if ($functions === false || $assets === false) {
    fwrite(STDERR, "Unable to read Menu frontend asset sources\n");
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

$requiredFunctionSnippets = array(
    '$needsSlickNav = false;',
    "MENU_runtimeConfigEnabled('legacy_rendering', true)",
    "MENU_runtimeConfigEnabled('load_legacy_css', true)",
    "MENU_runtimeConfigEnabled('load_legacy_js', true)",
    "MENU_menuNeedsLegacyJs(\$menu['menu_id'])",
    "setCSSFile('menu_slicknav', '/menu/css/slicknav.css')",
    "setJavaScriptFile('slicknav', '/menu/js/jquery.slicknav.js')",
);

foreach ($requiredFunctionSnippets as $snippet) {
    if (strpos($functions, $snippet) === false) {
        fwrite(STDERR, 'SlickNav legacy loading contract changed or disappeared: ' . $snippet . "\n");
        exit(1);
    }
}

$requiredAssetSnippets = array(
    'function MENU_menuNeedsLegacyJs($menuID)',
    "(int) \$Menus[\$menuID]['menu_type'] !== 1",
    "!MENU_runtimeConfigEnabled('load_legacy_js', true)",
    'MENU_themeHandlesPresentation($menuName)',
);

foreach ($requiredAssetSnippets as $snippet) {
    if (strpos($assets, $snippet) === false) {
        fwrite(STDERR, 'SlickNav decision helper contract changed or disappeared: ' . $snippet . "\n");
        exit(1);
    }
}

if (strpos($functions, 'if (!$legacyRendering)') === false) {
    fwrite(STDERR, "Legacy rendering disable gate is missing\n");
    exit(1);
}

echo "SlickNav legacy compatibility contract OK\n";
