<?php

$root = dirname(__DIR__);
$class = file_get_contents($root . '/classes/classMenuElement.php');

if (strpos($class, "plugins/menu/images/info.png") === false) {
    fwrite(STDERR, "Element info icon missing\n");
    exit(1);
}

$infoPos = strpos($class, "plugins/menu/images/info.png");
$context = substr($class, max(0, $infoPos - 500), 1200);

if (strpos($context, 'COM_getTooltip') !== false) {
    fwrite(STDERR, "Element info icon must not use positioned Geeklog tooltip markup\n");
    exit(1);
}

if (strpos($context, 'title=') === false) {
    fwrite(STDERR, "Element info icon must expose a native title tooltip\n");
    exit(1);
}

echo "Admin tree info tooltip contract tests passed\n";
