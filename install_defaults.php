<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// |                                                                           |
// | Default configuration installer for Geeklog.                              |
// +---------------------------------------------------------------------------+

if (stripos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    die('This file can not be used on its own.');
}

require_once __DIR__ . '/config.php';

/**
 * Return config sort positions. Gaps deliberately separate logical groups.
 *
 * @return array
 */
function MENU_configSortOrder130()
{
    return array(
        'enable_cache'             => 10,
        'accessibility_markup'     => 20,
        'external_link_protection' => 110,
        'allow_php_elements'       => 120,
        'legacy_rendering'         => 210,
        'load_legacy_css'          => 220,
        'load_legacy_js'           => 230,
        'debug'                    => 310,
    );
}

/**
 * Add one Menu 1.3.0 boolean setting.
 *
 * @param config $c
 * @param string $name
 * @param mixed  $default
 * @param int    $sort
 * @return void
 */
function MENU_addConfigSetting130($c, $name, $default, $sort)
{
    $c->add($name, $default, 'select', 0, 0, 0, $sort, true, 'menu', 0);
}

/**
 * Add the complete 1.3.0 configuration for a fresh installation.
 *
 * @param config $c
 * @return void
 */
function MENU_addConfig130($c)
{
    global $_MENU_DEFAULT;

    $sortOrder = MENU_configSortOrder130();

    $c->add('sg_main', null, 'subgroup', 0, 0, null, 0, true, 'menu', 0);
    $c->add('tab_main', null, 'tab', 0, 0, null, 0, true, 'menu', 0);
    $c->add('fs_main', null, 'fieldset', 0, 0, null, 0, true, 'menu', 0);

    foreach ($sortOrder as $name => $sort) {
        MENU_addConfigSetting130($c, $name, $_MENU_DEFAULT[$name], $sort);
    }
}

/**
 * Ensure all 1.3.0 settings exist without resetting existing values.
 *
 * @return bool
 */
function MENU_ensureConfig130()
{
    global $_MENU_DEFAULT;

    $c = config::get_instance();

    if (!$c->group_exists('menu')) {
        MENU_addConfig130($c);
        return true;
    }

    $current = $c->get_config('menu');
    if (!is_array($current)) {
        $current = array();
    }

    $sortOrder = MENU_configSortOrder130();
    foreach ($sortOrder as $name => $sort) {
        if (!array_key_exists($name, $current)) {
            MENU_addConfigSetting130($c, $name, $_MENU_DEFAULT[$name], $sort);
        }
    }

    return true;
}

/**
 * Initialize Menu plugin configuration for a fresh installation.
 *
 * @return bool
 */
function plugin_initconfig_menu()
{
    return MENU_ensureConfig130();
}
