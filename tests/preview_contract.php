<?php

$root = dirname(__DIR__);
$preview = file_get_contents($root . '/admin/preview.php');
$template = file_get_contents($root . '/templates/default/menutree.thtml');

function menu_preview_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

menu_preview_assert(strpos($template, 'mode=tabs') !== false, 'menu tree must load the tabbed preview controller');
menu_preview_assert(strpos($preview, "\$mode === 'tabs'") !== false, 'tabs preview mode missing');
menu_preview_assert(strpos($preview, "\$mode === 'theme'") !== false, 'theme preview mode missing');
menu_preview_assert(strpos($preview, 'Theme preview') !== false, 'theme preview tab label missing');
menu_preview_assert(strpos($preview, 'Menu preview') !== false, 'native preview tab label missing');
menu_preview_assert(strpos($preview, 'theme_plugin_presentation_preview') !== false, 'generic theme preview callback missing');
menu_preview_assert(strpos($preview, "strpos(\$relative, '..')") !== false, 'theme preview include must reject traversal');
menu_preview_assert(strpos($preview, '&amp;amp;mode=') === false, 'preview query string must not be double escaped');

echo "Tabbed preview contract tests passed" . PHP_EOL;
