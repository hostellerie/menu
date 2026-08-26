<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | install_updates.php                                                       |
// |                                                                           |
// | Upgrade helpers for existing Menu installations.                          |
// +---------------------------------------------------------------------------+

if (stripos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    die('This file can not be used on its own.');
}

/**
 * Add the 1.3.0 database indexes without changing existing data or engines.
 *
 * The legacy schema already uses AUTO_INCREMENT primary keys. 1.3.0 keeps the
 * schema compatible and adds only the composite index used by tree traversal
 * and sibling reordering. The operation is intentionally idempotent.
 *
 * @return bool
 */
function menu_update_Database_1_3_0()
{
    global $_TABLES;

    if (!isset($_TABLES['menu_elements']) || $_TABLES['menu_elements'] === '') {
        return false;
    }

    $indexName = 'menu_parent_order';
    $result = DB_query(
        'SHOW INDEX FROM ' . $_TABLES['menu_elements']
        . " WHERE Key_name = '" . $indexName . "'"
    );

    if (DB_numRows($result) === 0) {
        DB_query(
            'ALTER TABLE ' . $_TABLES['menu_elements']
            . ' ADD INDEX ' . $indexName . ' (menu_id, pid, element_order)'
        );
    }

    return true;
}

/**
 * Update Geeklog configuration values for Menu 1.3.0.
 *
 * Existing values are preserved. The operation is idempotent and can safely
 * be re-run if an upgrade was interrupted before the plugin version changed.
 * Database schema changes are deliberately handled later by the real upgrade
 * or post-install path, once plugin tables are guaranteed to exist.
 *
 * @return bool
 */
function menu_update_ConfValues_1_3_0()
{
    global $_CONF, $_TABLES, $_MENU_CONF;

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once __DIR__ . '/install_defaults.php';

    if (!MENU_ensureConfig130()) {
        COM_errorLog('Menu upgrade: unable to initialize 1.3.0 configuration');
        return false;
    }

    if (isset($_TABLES['conf_values'])) {
        DB_query(
            "DELETE FROM {$_TABLES['conf_values']} "
            . "WHERE group_name = 'menu' "
            . "AND name IN ('samplesetting1', 'samplesetting2')"
        );
    }

    $menuConfig = config::get_instance();
    $_MENU_CONF = $menuConfig->get_config('menu');

    return true;
}
