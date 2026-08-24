<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | configuration_language.php                                                |
// |                                                                           |
// | Labels used by Geeklog's global configuration manager.                    |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

if (!isset($LANG_configsections) || !is_array($LANG_configsections)) {
    $LANG_configsections = array();
}
if (!isset($LANG_confignames) || !is_array($LANG_confignames)) {
    $LANG_confignames = array();
}
if (!isset($LANG_configsubgroups) || !is_array($LANG_configsubgroups)) {
    $LANG_configsubgroups = array();
}
if (!isset($LANG_tab) || !is_array($LANG_tab)) {
    $LANG_tab = array();
}
if (!isset($LANG_fs) || !is_array($LANG_fs)) {
    $LANG_fs = array();
}

$LANG_configsections['menu'] = array(
    'label' => 'Menu',
    'title' => 'Menu Configuration',
);

$LANG_configsubgroups['menu'] = array(
    'sg_main' => 'Global Settings',
);

$LANG_tab['menu'] = array(
    'tab_main' => 'Global Settings',
);

$LANG_fs['menu'] = array(
    'fs_main' => 'Menu Plugin Settings',
);

$LANG_confignames['menu'] = array(
    // Kept temporarily so old 1.2.x configuration rows can still render
    // safely until the 1.3.0 upgrade removes them.
    'samplesetting1'          => 'Legacy sample setting 1',
    'samplesetting2'          => 'Legacy sample setting 2',

    'enable_cache'             => 'Enable menu cache',
    'accessibility_markup'     => 'Enable accessibility / ARIA markup',
    'external_link_protection' => 'Protect external links opened in a new window',
    'allow_php_elements'       => 'Allow PHP function menu elements',
    'legacy_rendering'         => 'Enable legacy Menu rendering',
    'load_legacy_css'          => 'Load legacy Menu CSS',
    'load_legacy_js'           => 'Load legacy Menu JavaScript',
    'debug'                    => 'Enable Menu debug logging',
);
