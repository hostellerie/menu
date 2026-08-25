<?php

function menu_legacy_cleanup_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$root = dirname(__DIR__);
$index = file_get_contents($root . '/admin/index.php');
$security = file_get_contents($root . '/admin_security.php');
$createTemplate = file_get_contents($root . '/templates/default/createelement.thtml');
$cloneTemplate = file_get_contents($root . '/templates/default/clonemenu.thtml');

menu_legacy_cleanup_assert(
    strpos($index, 'function MENU_saveNewMenuElement') === false,
    'Legacy new-element persistence function must be removed'
);
menu_legacy_cleanup_assert(
    strpos($index, 'function MENU_saveCloneMenu') === false,
    'Legacy clone persistence function must be removed'
);
menu_legacy_cleanup_assert(
    strpos($index, "case 'save' :") === false,
    'Legacy save controller branch must be removed'
);
menu_legacy_cleanup_assert(
    strpos($index, "case 'saveclonemenu' :") === false,
    'Legacy clone controller branch must be removed'
);
menu_legacy_cleanup_assert(
    strpos($security, "$mode === 'save' || $mode === 'saveclonemenu'") !== false,
    'Crafted legacy persistence requests must remain explicitly rejected'
);
menu_legacy_cleanup_assert(
    strpos($createTemplate, '/plugins/menu/create_element.php') !== false,
    'New element form must use the dedicated persistence endpoint'
);
menu_legacy_cleanup_assert(
    strpos($cloneTemplate, '/plugins/menu/clone.php') !== false,
    'Clone form must use the dedicated persistence endpoint'
);

echo 'Legacy persistence cleanup tests passed' . PHP_EOL;
