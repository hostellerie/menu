<?php

$root = dirname(__DIR__);
$resolved = file_get_contents($root . '/resolved_tree.php');
$services = file_get_contents($root . '/services.inc.php');
$functions = file_get_contents($root . '/functions.inc');

$requiredResolved = array(
    'function MENU_getAvailableMenus()',
    "empty(\$menu['active'])",
    "(int) \$menu['menu_perm'] !== 3",
    "'id' => (int) \$menu['menu_id']",
    "'name' => (string) \$menu['menu_name']",
    "'type' => isset(\$menu['menu_type'])",
);

foreach ($requiredResolved as $needle) {
    if (strpos($resolved, $needle) === false) {
        fwrite(STDERR, "Menu discovery contract missing: {$needle}\n");
        exit(1);
    }
}

$requiredServices = array(
    'function service_getMenuList_menu(',
    'MENU_getAvailableMenus()',
    'function service_getMenuTree_menu(',
    'MENU_getResolvedTree($name)',
    "'provider_family' => 'navigation'",
    "'provider_contract_version'",
    'return PLG_RET_OK;',
);

foreach ($requiredServices as $needle) {
    if (strpos($services, $needle) === false) {
        fwrite(STDERR, "Menu service contract missing: {$needle}\n");
        exit(1);
    }
}

if (strpos($functions, "require_once \$_CONF['path'] . 'plugins/menu/services.inc.php';") === false) {
    fwrite(STDERR, "Menu services are not loaded by functions.inc\n");
    exit(1);
}

foreach (array('group_id', 'owner_id', 'perm_owner', 'perm_group', 'perm_members', 'perm_anon') as $privateField) {
    if (strpos($services, "'{$privateField}' =>") !== false) {
        fwrite(STDERR, "Menu service must not expose ACL field: {$privateField}\n");
        exit(1);
    }
}

echo "Menu service contract tests passed\n";
