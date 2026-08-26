<?php

$root = dirname(__DIR__);
$autoinstall = file_get_contents($root . '/autoinstall.php');
$functions = file_get_contents($root . '/functions.inc');
$config = file_get_contents($root . '/config.php');

if ($autoinstall === false || $functions === false || $config === false) {
    fwrite(STDERR, "Unable to read installation sources\n");
    exit(1);
}

$requiredAutoinstall = array(
    "'pi_version'      => '1.3.0'",
    "'gl_version'    => '2.1.1'",
    "'tables'   => array('menu', 'menu_config', 'menu_elements')",
    'function plugin_load_configuration_menu',
    'return plugin_initconfig_menu();',
    'function plugin_postinstall_menu',
    'MENU_ensureImageDir()',
    'MENU_ensureDataDirs()',
    'menu_update_Database_1_3_0()',
    "version_compare(VERSION, '2.1.1', '<')",
    "version_compare(PHP_VERSION, '5.6.0', '<')",
);

foreach ($requiredAutoinstall as $snippet) {
    $haystack = (strpos($snippet, "'pi_version'") !== false || strpos($snippet, "'gl_version'") !== false)
        ? $config : $autoinstall;
    if (strpos($haystack, $snippet) === false) {
        fwrite(STDERR, 'Install contract changed or disappeared: ' . $snippet . "\n");
        exit(1);
    }
}

$requiredUninstall = array(
    'function plugin_autouninstall_menu()',
    "'tables' => array('menu','menu_config','menu_elements')",
    "'groups' => array('Menu Admin')",
    "'features' => array('menu.admin')",
);

foreach ($requiredUninstall as $snippet) {
    if (strpos($functions, $snippet) === false) {
        fwrite(STDERR, 'Uninstall contract changed or disappeared: ' . $snippet . "\n");
        exit(1);
    }
}

echo "Install/uninstall release contract OK\n";
