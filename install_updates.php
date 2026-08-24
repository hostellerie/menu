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
 * Update Geeklog configuration values for Menu 1.3.0.
 *
 * Existing values are preserved. The operation is idempotent and can safely
 * be re-run if an upgrade was interrupted before the plugin version changed.
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
