<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | database.php                                                              |
// |                                                                           |
// | Small database helpers shared by administration and upgrades.             |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Delete one menu and every plugin-owned row attached to it.
 *
 * No foreign keys are required: explicit menu_id deletes keep this compatible
 * with existing MyISAM installations and future InnoDB installations alike.
 *
 * @param int $menuId
 * @return bool
 */
function MENU_deleteMenuData($menuId)
{
    global $_TABLES;

    $menuId = (int) $menuId;
    if ($menuId <= 0
        || !isset($_TABLES['menu'])
        || !isset($_TABLES['menu_config'])
        || !isset($_TABLES['menu_elements'])) {
        return false;
    }

    DB_query("DELETE FROM {$_TABLES['menu_elements']} WHERE menu_id=" . $menuId);
    DB_query("DELETE FROM {$_TABLES['menu_config']} WHERE menu_id=" . $menuId);
    DB_query("DELETE FROM {$_TABLES['menu']} WHERE id=" . $menuId);

    return true;
}
