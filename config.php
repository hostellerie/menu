<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | config.php                                                                |
// |                                                                           |
// | Plugin metadata and default global settings.                              |
// +---------------------------------------------------------------------------+

if (stripos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    die('This file can not be used on its own.');
}

/*
 * config.php can be included by Geeklog from inside plugin installer
 * functions. Declare these arrays global here so their values never depend on
 * the scope from which this file was first included.
 */
global $_MENU_PLUGIN, $_MENU_DEFAULT;

$_MENU_PLUGIN = array(
    'pi_name'       => 'menu',
    'pi_version'    => '1.3.0',
    'gl_version'    => '2.1.1',
    'pi_url'        => 'https://github.com/hostellerie/menu',
    'GROUPS'        => array(
        'Menu Admin' => 'Users in this group can administer the Menu plugin',
    ),
    'FEATURES'      => array(
        'menu.admin' => 'Full access to Menu plugin',
    ),
    'MAPPINGS'      => array(
        'menu.admin' => array('Menu Admin'),
    ),
);

$_MENU_DEFAULT = array(
    'enable_cache'             => true,
    'load_legacy_css'          => true,
    'load_legacy_js'           => true,
    'legacy_rendering'         => true,
    'allow_php_elements'       => false,
    'external_link_protection' => true,
    'accessibility_markup'     => true,
    'debug'                    => false,
);
