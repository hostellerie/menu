<?php

if (!defined('VERSION')) {
    define('VERSION', '2.1.1');
}

$_CONF = array(
    'site_url' => 'https://example.test',
);
$_MENU_CONF = array();

require_once dirname(__DIR__) . '/runtime_config.php';

function assertRuntimeConfig($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . "\n");
        exit(1);
    }
}

assertRuntimeConfig(MENU_runtimeConfigEnabled('enable_cache') === true, 'cache defaults to enabled');
assertRuntimeConfig(MENU_runtimeConfigEnabled('allow_php_elements') === false, 'PHP elements default to disabled');
assertRuntimeConfig(MENU_runtimeConfigEnabled('legacy_rendering') === true, 'legacy rendering defaults to enabled');

$_MENU_CONF['enable_cache'] = '0';
assertRuntimeConfig(MENU_runtimeConfigEnabled('enable_cache') === false, 'string zero disables cache');
$_MENU_CONF['enable_cache'] = '1';
assertRuntimeConfig(MENU_runtimeConfigEnabled('enable_cache') === true, 'string one enables cache');

assertRuntimeConfig(MENU_isExternalUrl('https://example.test/path') === false, 'same-host URL is internal');
assertRuntimeConfig(MENU_isExternalUrl('/relative/path') === false, 'relative URL is internal');
assertRuntimeConfig(MENU_isExternalUrl('https://outside.example/path') === true, 'different host is external');

$_MENU_CONF['external_link_protection'] = 1;
$attrs = MENU_legacyLinkAttributes('https://outside.example/path', '_blank');
assertRuntimeConfig(strpos($attrs, 'target="_blank"') !== false, 'blank target is preserved');
assertRuntimeConfig(strpos($attrs, 'rel="noopener noreferrer"') !== false, 'external blank target is protected');

$_MENU_CONF['external_link_protection'] = 0;
$attrs = MENU_legacyLinkAttributes('https://outside.example/path', '_blank');
assertRuntimeConfig(strpos($attrs, 'rel=') === false, 'link protection can be disabled');

$_MENU_CONF['accessibility_markup'] = 1;
assertRuntimeConfig(strpos(MENU_legacyNavigationAttributes('Primary'), 'role="navigation"') !== false, 'navigation ARIA can be enabled');
$_MENU_CONF['accessibility_markup'] = 0;
assertRuntimeConfig(MENU_legacyNavigationAttributes('Primary') === '', 'navigation ARIA can be disabled');

echo "runtime_config: OK\n";
