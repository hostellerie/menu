<?php

$root = dirname(__DIR__);

$removedAssets = array(
    'admin/images/blank.gif',
    'admin/images/check.png',
    'admin/images/copy.png',
    'admin/images/transparent.png',
    'public_html/js/menu_ie6vertmenu.js',
);

foreach ($removedAssets as $relativePath) {
    if (file_exists($root . '/' . $relativePath)) {
        fwrite(STDERR, "Removed legacy asset returned: {$relativePath}\n");
        exit(1);
    }
}

if (!is_file($root . '/admin/images/rainbow.png')) {
    fwrite(STDERR, "Required menu options icon is missing: admin/images/rainbow.png\n");
    exit(1);
}

$menuListTemplate = file_get_contents($root . '/templates/default/menulist.thtml');
if ($menuListTemplate === false
    || strpos($menuListTemplate, '/plugins/menu/images/rainbow.png') === false) {
    fwrite(STDERR, "Menu options icon contract is missing from menulist.thtml\n");
    exit(1);
}

$colorPicker = file_get_contents($root . '/admin/js/colorpicker.js');
$required = array(
    "(function ($) {",
    "'use strict';",
    'var selectorOwner = null;',
    'function buildSelector()',
    'function buildPicker(element)',
    '$.fn.colorPicker = function ()',
    '}(jQuery));',
);

foreach ($required as $needle) {
    if (strpos($colorPicker, $needle) === false) {
        fwrite(STDERR, "Color picker encapsulation contract missing: {$needle}\n");
        exit(1);
    }
}

$forbidden = array(
    "\nbuildPicker = function",
    "\nbuildSelector = function",
    "\ncheckMouse = function",
    "\nhideSelector = function",
    "\nshowSelector = function",
    "\ntoggleSelector = function",
    "\nchangeColor = function",
    "\ntoHex = function",
);

foreach ($forbidden as $needle) {
    if (strpos($colorPicker, $needle) !== false) {
        fwrite(STDERR, "Implicit color picker global returned: {$needle}\n");
        exit(1);
    }
}

echo "Admin asset cleanup contract tests passed\n";
