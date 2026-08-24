<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// |                                                                           |
// | This file is used to hook into Geeklog's configuration UI                 |
// +---------------------------------------------------------------------------+

/**
 * @package Menu
 */

if (stripos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    die('This file can not be used on its own.');
}

/**
 * Return the global Menu configuration defaults introduced in 1.3.0.
 *
 * These options apply to the plugin as a whole. Per-menu presentation and
 * structure remain stored in the Menu plugin tables and editor.
 *
 * @return array
 */
function MENU_configDefaults()
{
    return array(
        'enable_cache'             => true,
        'load_legacy_css'          => true,
        'load_legacy_js'           => true,
        'legacy_rendering'         => true,
        'allow_php_elements'       => false,
        'external_link_protection' => true,
        'accessibility_markup'     => true,
        'debug'                    => false,
    );
}

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
    // selectionArray 0 is Geeklog's standard Yes / No selector.
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
    $defaults = MENU_configDefaults();
    $sortOrder = MENU_configSortOrder130();

    $c->add('sg_main', null, 'subgroup', 0, 0, null, 0, true, 'menu', 0);
    $c->add('tab_main', null, 'tab', 0, 0, null, 0, true, 'menu', 0);
    $c->add('fs_main', null, 'fieldset', 0, 0, null, 0, true, 'menu', 0);

    foreach ($sortOrder as $name => $sort) {
        MENU_addConfigSetting130($c, $name, $defaults[$name], $sort);
    }
}

/**
 * Ensure all 1.3.0 settings exist without resetting existing values.
 *
 * This is used by the upgrade path and is intentionally idempotent.
 *
 * @return bool
 */
function MENU_ensureConfig130()
{
    $c = config::get_instance();

    if (!$c->group_exists('menu')) {
        MENU_addConfig130($c);
        return true;
    }

    $current = $c->get_config('menu');
    if (!is_array($current)) {
        $current = array();
    }

    $defaults = MENU_configDefaults();
    $sortOrder = MENU_configSortOrder130();

    foreach ($sortOrder as $name => $sort) {
        if (!array_key_exists($name, $current)) {
            MENU_addConfigSetting130($c, $name, $defaults[$name], $sort);
        }
    }

    return true;
}

/**
 * Initialize Menu plugin configuration for a fresh installation.
 *
 * @return boolean TRUE on success
 */
function plugin_initconfig_menu()
{
    return MENU_ensureConfig130();
}
