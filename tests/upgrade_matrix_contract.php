<?php

$root = dirname(__DIR__);
$autoinstall = file_get_contents($root . '/autoinstall.php');
$updates = file_get_contents($root . '/install_updates.php');

if ($autoinstall === false || $updates === false) {
    fwrite(STDERR, "Unable to read upgrade sources\n");
    exit(1);
}

$fromVersions = array('1.2.6', '1.2.7', '1.2.8', '1.2.8.1');
foreach ($fromVersions as $version) {
    if (!version_compare($version, '1.3.0', '<')) {
        fwrite(STDERR, 'Supported upgrade source is not routed through 1.3.0 migration: ' . $version . "\n");
        exit(1);
    }
}

$requiredUpgradePath = array(
    'version_compare($installedVersion, \'1.3.0\', \'<\')',
    "require_once __DIR__ . '/install_updates.php';",
    'menu_update_ConfValues_1_3_0()',
);

foreach ($requiredUpgradePath as $snippet) {
    if (strpos($autoinstall, $snippet) === false) {
        fwrite(STDERR, 'Supported 1.2.x upgrade path changed or disappeared: ' . $snippet . "\n");
        exit(1);
    }
}

$requiredMigrationSafety = array(
    'function menu_update_Database_1_3_0()',
    'SHOW INDEX FROM ',
    'DB_numRows($result) === 0',
    'function menu_update_ConfValues_1_3_0()',
    'MENU_ensureConfig130()',
    "SHOW TABLES LIKE '",
);

foreach ($requiredMigrationSafety as $snippet) {
    if (strpos($updates, $snippet) === false) {
        fwrite(STDERR, 'Upgrade migration safety contract changed or disappeared: ' . $snippet . "\n");
        exit(1);
    }
}

echo "Supported Menu 1.2.x upgrade matrix contract OK\n";
