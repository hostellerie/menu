<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | autoinstall.php                                                           |
// |                                                                           |
// | Automatic installation metadata and compatibility checks.                 |
// +---------------------------------------------------------------------------+

if (stripos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    die('This file can not be used on its own.');
}

/**
 * Return plugin autoinstall metadata.
 *
 * config.php is loaded inside this function deliberately. This mirrors the
 * official Geeklog 2.2.2 plugin pattern and ensures $_MENU_PLUGIN is populated
 * in the declared global scope even when config.php was previously included
 * from another function scope.
 *
 * @param string $pi_name
 * @return array
 */
function plugin_autoinstall_menu($pi_name)
{
    global $_MENU_PLUGIN;

    require_once __DIR__ . '/config.php';

    if (!isset($_MENU_PLUGIN) || !is_array($_MENU_PLUGIN)) {
        return array();
    }

    return array(
        'info' => array(
            'pi_name'         => $_MENU_PLUGIN['pi_name'],
            'pi_display_name' => 'Menu',
            'pi_version'      => $_MENU_PLUGIN['pi_version'],
            'pi_gl_version'   => $_MENU_PLUGIN['gl_version'],
            'pi_homepage'     => $_MENU_PLUGIN['pi_url'],
        ),
        'groups'   => $_MENU_PLUGIN['GROUPS'],
        'features' => $_MENU_PLUGIN['FEATURES'],
        'mappings' => $_MENU_PLUGIN['MAPPINGS'],
        'tables'   => array('menu', 'menu_config', 'menu_elements'),
    );
}

/**
 * Load the Menu configuration during a fresh installation.
 *
 * @param string $pi_name
 * @return bool
 */
function plugin_load_configuration_menu($pi_name)
{
    global $_CONF;

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once __DIR__ . '/install_defaults.php';

    return plugin_initconfig_menu();
}

/**
 * Check compatibility and run pre-version configuration migration when an
 * existing installation is moving to 1.3.0.
 *
 * The complete upgrade sequence remains owned by plugin_upgrade_menu() in
 * functions.inc. Configuration and database mutations live in
 * install_updates.php.
 *
 * @param string $pi_name
 * @return bool
 */
function plugin_compatible_with_this_version_menu($pi_name)
{
    global $_CONF, $_DB_dbms, $_TABLES;

    $dbFile = $_CONF['path'] . 'plugins/' . $pi_name . '/sql/'
            . $_DB_dbms . '_install.php';
    if (!file_exists($dbFile)) {
        return false;
    }

    if (defined('VERSION') && version_compare(VERSION, '2.1.1', '<')) {
        return false;
    }

    if (version_compare(PHP_VERSION, '5.6.0', '<')) {
        return false;
    }

    if (isset($_TABLES['plugins'])) {
        $installedVersion = DB_getItem(
            $_TABLES['plugins'],
            'pi_version',
            "pi_name = 'menu'"
        );

        if ($installedVersion !== ''
            && $installedVersion !== false
            && version_compare($installedVersion, '1.3.0', '<')) {
            require_once __DIR__ . '/install_updates.php';
            if (!menu_update_ConfValues_1_3_0()) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Complete filesystem and database setup after installation.
 *
 * @param string $pi_name
 * @return bool
 */
function plugin_postinstall_menu($pi_name)
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/menu/storage.php';
    require_once __DIR__ . '/install_updates.php';

    if (!MENU_ensureImageDir()) {
        COM_errorLog('Menu postinstall: unable to create public image directory: ' . MENU_imageDir());
        return false;
    }

    if (!MENU_ensureDataDirs()) {
        COM_errorLog('Menu postinstall: unable to create storage directory: ' . MENU_dataDir());
        return false;
    }

    if (!menu_update_Database_1_3_0()) {
        COM_errorLog('Menu postinstall: unable to initialize database indexes');
        return false;
    }

    if (!MENU_usesPreferredDataDir()) {
        COM_errorLog(
            'Menu postinstall: preferred storage ' . MENU_preferredDataDir()
            . ' is not writable; temporarily using legacy storage '
            . MENU_legacyDataDir()
        );
    }

    return true;
}
