<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0-alpha1                                                  |
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
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+

/**
* @package Menu
*/

function plugin_autoinstall_menu($pi_name)
{
    $pi_name         = 'menu';
    $pi_display_name = 'Menu';
    $pi_admin        = $pi_display_name . ' Admin';

    $info = array(
        'pi_name'         => $pi_name,
        'pi_display_name' => $pi_display_name,
        'pi_version'      => '1.3.0-alpha1',
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

function plugin_compatible_with_this_version_menu($pi_name)
{
    global $_CONF, $_DB_dbms;

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

    return true;
}

function plugin_postinstall_menu($pi_name)
{
    global $_CONF;

    if (!is_dir($_CONF['path_images'] . 'menu')) {
        @mkdir($_CONF['path_images'] . 'menu', 0755, true);
    }

    require_once $_CONF['path'] . 'plugins/menu/storage.php';

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
