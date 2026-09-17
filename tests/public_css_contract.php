<?php

$root = dirname(__DIR__);
$functions = file_get_contents($root . '/functions.inc');

$required = array(
    'function MENU_publishCssAsset',
    'substr(sha1($css), 0, 12)',
    "'menu-' . \$menuId . '-' . \$fingerprint . '.css'",
    'MENU_imageDir()',
    'MENU_imageUrl()',
    'MENU_ensureDirectory($cssDir)',
    'file_put_contents($path, $css, LOCK_EX)',
    'function MENU_cssAssetMarkup',
    "'<link rel=\"stylesheet\" type=\"text/css\" href=\"'",
    "'<style type=\"text/css\">' . \$css . '</style>'",
    "MENU_cssAssetMarkup(\$menu['menu_id'], \$css_minify)",
);

foreach ($required as $needle) {
    if (strpos($functions, $needle) === false) {
        fwrite(STDERR, "Generated CSS contract missing: {$needle}\n");
        exit(1);
    }
}

$oldInline = "\$menu_header .= LB . '<style type=\"text/css\">' . \$css_minify . '</style>' . LB;";
if (strpos($functions, $oldInline) !== false) {
    fwrite(STDERR, "Legacy direct inline CSS injection is still present\n");
    exit(1);
}

echo "Public generated menu CSS contract tests passed\n";
