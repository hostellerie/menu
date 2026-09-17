<?php

$root = dirname(__DIR__);
$config = file_get_contents($root . '/config.php');
$defaults = file_get_contents($root . '/install_defaults.php');
$updates = file_get_contents($root . '/install_updates.php');
$autoinstall = file_get_contents($root . '/autoinstall.php');

foreach (array('samplesetting1', 'samplesetting2') as $name) {
    if (strpos($config, "'" . $name . "'") !== false) {
        fwrite(STDERR, "Obsolete sample setting still exists in config.php: {$name}\n");
        exit(1);
    }
    if (strpos($defaults, "'" . $name . "'") !== false) {
        fwrite(STDERR, "Obsolete sample setting still exists in install defaults: {$name}\n");
        exit(1);
    }
    if (strpos($updates, $name) === false) {
        fwrite(STDERR, "Upgrade cleanup does not mention obsolete setting: {$name}\n");
        exit(1);
    }
}

$required = array(
    'function menu_update_ConfValues_1_4_0()',
    "group_name = 'menu'",
    "name IN ('samplesetting1', 'samplesetting2')",
    'version_compare($installedVersion, \'1.4.0\', \'<\')',
    'menu_update_ConfValues_1_4_0()',
);

foreach ($required as $needle) {
    if (strpos($updates . "\n" . $autoinstall, $needle) === false) {
        fwrite(STDERR, "Obsolete sample config cleanup contract missing: {$needle}\n");
        exit(1);
    }
}

echo "Obsolete sample configuration cleanup contract tests passed\n";
