<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | autoinstall.php                                                           |
// |                                                                           |
// | This file provides helper functions for the automatic plugin install.     |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2014-2018 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// +---------------------------------------------------------------------------+
// | Created with the Geeklog Plugin Toolkit.                                  |
// +---------------------------------------------------------------------------+

/**
 * @package Menu
 */

require_once __DIR__ . '/configuration_language.php';

function plugin_autoinstall_menu($pi_name)
{
    $pi_name         = 'menu';
    $pi_display_name = 'Menu';
    $pi_admin        = $pi_display_name . ' Admin';

    $info = array(
        'pi_name'         => $pi_name,
        'pi_display_name' => $pi_display_name,
        'pi_version'      => '1.3.0',
        'pi_gl_version'   => '2.1.1',
        'pi_homepage'     => 'https://github.com/hostellerie/menu'
    );

    $groups = array(
        $pi_admin => 'Users in this group can administer the '
                     . $pi_display_name . ' plugin'
    );

    $features = array(
        $pi_name . '.admin' => 'Full access to ' . $pi_display_name
                                  . ' plugin'
    );

    $mappings = array(
        $pi_name . '.admin' => array($pi_admin)
    );

    $tables = array(
        'menu',
        'menu_config',
        'menu_elements'
    );

    return array(
        'info'      => $info,
        'groups'    => $groups,
        'features'  => $features,
        'mappings'  => $mappings,
        'tables'    => $tables
    );
}

function plugin_load_configuration_menu($pi_name)
{
    global $_CONF;

    $base_path = $_CONF['path'] . 'plugins/' . $pi_name . '/';

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once $base_path . 'install_defaults.php';

    return plugin_initconfig_menu();
}

/**
 * Add the Menu 1.3.0 global configuration to an existing installation.
 *
 * This helper is deliberately separate from plugin_upgrade_menu(), which is
 * already part of functions.inc and owns the complete upgrade sequence
 * (including persistent-data migration and cache cleanup).
 *
 * Existing values are preserved and the legacy Plugin Toolkit sample settings
 * are removed. The operation is safe to run more than once.
 *
 * @return bool
 */
function MENU_upgradeConfig130()
{
    global $_CONF, $_TABLES, $_MENU_CONF;

    if (!isset($_TABLES['conf_values'])) {
        return true;
    }

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once __DIR__ . '/install_defaults.php';

    if (!MENU_ensureConfig130()) {
        COM_errorLog('Menu upgrade: unable to initialize 1.3.0 configuration');
        return false;
    }

    DB_query(
        "DELETE FROM {$_TABLES['conf_values']} "
        . "WHERE group_name = 'menu' "
        . "AND name IN ('samplesetting1', 'samplesetting2')"
    );

    $menuConfig = config::get_instance();
    $_MENU_CONF = $menuConfig->get_config('menu');

    return true;
}

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

    /*
     * functions.inc already owns plugin_upgrade_menu(). When Geeklog invokes
     * that upgrade path, this compatibility check is reached only after a
     * version difference has been detected. Add the 1.3.0 configuration here
     * before functions.inc records the new plugin version.
     *
     * On a fresh install there is no existing plugin row, so no upgrade
     * migration is performed; plugin_load_configuration_menu() creates the
     * configuration normally instead.
     */
    if (isset($_TABLES['plugins'])) {
        $installedVersion = DB_getItem(
            $_TABLES['plugins'],
            'pi_version',
            "pi_name = 'menu'"
        );

        if ($installedVersion !== ''
            && version_compare($installedVersion, '1.3.0', '<')) {
            if (!MENU_upgradeConfig130()) {
                return false;
            }
        }
    }

    return true;
}

function plugin_postinstall_menu($pi_name)
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/menu/storage.php';

    if (!MENU_ensureImageDir()) {
        COM_errorLog('Menu postinstall: unable to create public image directory: ' . MENU_imageDir());
        return false;
    }

    if (!MENU_ensureDataDirs()) {
        COM_errorLog('Menu postinstall: unable to create storage directory: ' . MENU_dataDir());
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
