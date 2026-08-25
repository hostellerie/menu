<?php

function menu_admin_cache_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$root = dirname(__DIR__);
$index = file_get_contents($root . '/admin/index.php');
$security = file_get_contents($root . '/admin_security.php');
$runtime = file_get_contents($root . '/cache_runtime.php');

menu_admin_cache_assert(
    strpos($index, "MENU_CACHE_remove_instance(") === false,
    'Admin controller must not invalidate individual cache instances directly'
);
menu_admin_cache_assert(
    strpos($index, 'MENU_invalidateRuntimeCache(true)') !== false,
    'Admin mutations must use centralized runtime cache invalidation'
);
menu_admin_cache_assert(
    strpos($index, 'MENU_invalidateRuntimeCache(false)') !== false,
    'Drag ordering may invalidate cache without rebuilding HTML state'
);
menu_admin_cache_assert(
    strpos($index, 'function MENU_deleteMenu(') === false,
    'Dead whole-menu recursive delete wrapper must stay removed'
);
menu_admin_cache_assert(
    strpos($index, 'function MENU_deleteChildElements(') !== false,
    'Subtree deletion must remain available for deleting an element with children'
);
menu_admin_cache_assert(
    strpos($index, '" AND menu_id=" . (int) $menu_id') !== false,
    'Subtree deletion must scope DELETE statements to the current menu'
);
menu_admin_cache_assert(
    strpos($security, 'MENU_deleteMenuData($menuId)') !== false,
    'Whole-menu deletion must use the menu_id-scoped database helper'
);
menu_admin_cache_assert(
    strpos($runtime, 'function MENU_invalidateRuntimeCache') !== false,
    'Central runtime cache invalidation helper must exist'
);

echo 'Admin cache invalidation tests passed' . PHP_EOL;
