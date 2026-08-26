<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | configuration_validation.php                                              |
// |                                                                           |
// | Validation rules for Geeklog's global configuration manager.              |
// +---------------------------------------------------------------------------+

if (stripos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    die('This file can not be used on its own.');
}

$_CONF_VALIDATE['menu'] = array(
    'enable_cache'             => array('rule' => array('inList', array('0', '1'), true)),
    'load_legacy_css'          => array('rule' => array('inList', array('0', '1'), true)),
    'load_legacy_js'           => array('rule' => array('inList', array('0', '1'), true)),
    'legacy_rendering'         => array('rule' => array('inList', array('0', '1'), true)),
    'allow_php_elements'       => array('rule' => array('inList', array('0', '1'), true)),
    'external_link_protection' => array('rule' => array('inList', array('0', '1'), true)),
    'accessibility_markup'     => array('rule' => array('inList', array('0', '1'), true)),
    'debug'                    => array('rule' => array('inList', array('0', '1'), true)),
);
