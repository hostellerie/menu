<?php

$root = dirname(__DIR__);
$class = file_get_contents($root . '/classes/classMenuElement.php');
$template = file_get_contents($root . '/templates/default/menutree.thtml');

if (strpos($class, "plugins/menu/images/info.png") === false) {
    fwrite(STDERR, "Element info icon missing\n");
    exit(1);
}

$infoPos = strpos($class, "plugins/menu/images/info.png");
$context = substr($class, max(0, $infoPos - 500), 1500);

if (strpos($context, 'COM_getTooltip') !== false) {
    fwrite(STDERR, "Element info icon must not use positioned Geeklog tooltip markup\n");
    exit(1);
}

if (strpos($context, 'menu-info-tooltip') === false
    || strpos($context, 'menu-info-tooltip-text') === false) {
    fwrite(STDERR, "Element info icon must expose the anchored CSS tooltip markup\n");
    exit(1);
}

if (strpos($context, 'title=') !== false) {
    fwrite(STDERR, "Element info icon must not fall back to delayed native title tooltip\n");
    exit(1);
}

if (strpos($template, '.menu-info-tooltip-text') === false
    || strpos($template, '.menu-info-tooltip:hover .menu-info-tooltip-text') === false) {
    fwrite(STDERR, "Anchored tooltip CSS is missing from Menu tree template\n");
    exit(1);
}

echo "Admin tree info tooltip contract tests passed\n";
