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
 * Add the 1.3.0 runtime settings to Geeklog's configuration manager.
 *
 * @param config $c
 * @param bool   $includeStructure Whether subgroup/tab/fieldset must be added
 * @return void
 */
function MENU_addConfig130($c, $includeStructure)
{
    $me = 'menu';
    $defaults = MENU_configDefaults();

    if ($includeStructure) {
        $c->add('sg_main', null, 'subgroup', 0, 0, null, 0, true, $me, 0);
        $c->add('tab_main', null, 'tab', 0, 0, null, 0, true, $me, 0);
        $c->add('fs_runtime', null, 'fieldset', 0, 0, null, 0, true, $me, 0);
        $c->add('fs_security', null, 'fieldset', 0, 0, null, 100, true, $me, 0);
        $c->add('fs_compatibility', null, 'fieldset', 0, 0, null, 200, true, $me, 0);
        $c->add('fs_diagnostics', null, 'fieldset', 0, 0, null, 300, true, $me, 0);
    }

    // selectionArray 0 is Geeklog's standard Yes / No selector.
    $c->add('enable_cache', $defaults['enable_cache'], 'select', 0, 0, 0, 10, true, $me, 0);
    $c->add('accessibility_markup', $defaults['accessibility_markup'], 'select', 0, 0, 0, 20, true, $me, 0);
    $c->add('external_link_protection', $defaults['external_link_protection'], 'select', 0, 0, 0, 110, true, $me, 0);
    $c->add('allow_php_elements', $defaults['allow_php_elements'], 'select', 0, 0, 0, 120, true, $me, 0);
    $c->add('legacy_rendering', $defaults['legacy_rendering'], 'select', 0, 0, 0, 210, true, $me, 0);
    $c->add('load_legacy_css', $defaults['load_legacy_css'], 'select', 0, 0, 0, 220, true, $me, 0);
    $c->add('load_legacy_js', $defaults['load_legacy_js'], 'select', 0, 0, 0, 230, true, $me, 0);
    $c->add('debug', $defaults['debug'], 'select', 0, 0, 0, 310, true, $me, 0);
}

/**
 * Initialize Menu plugin configuration for a fresh installation.
 *
 * @return boolean TRUE on success
 */
function plugin_initconfig_menu()
{
    $c = config::get_instance();

    if (!$c->group_exists('menu')) {
        MENU_addConfig130($c, true);
    }

    return true;
}
