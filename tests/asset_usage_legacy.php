<?php

// Geeklog 2.1.1 cannot know arbitrary late menu usage before COM_siteHeader().
// Verify the compatibility fallback remains conservative. PHP 5.6+.

define('VERSION', '2.1.1');

$menuLegacyConfig = array(
    'legacy_rendering' => true,
    'load_legacy_css' => true,
    'load_legacy_js' => true,
);

function MENU_runtimeConfigEnabled($key, $default)
{
    global $menuLegacyConfig;
    return isset($menuLegacyConfig[$key]) ? (bool) $menuLegacyConfig[$key] : (bool) $default;
}

function MENU_themeHandlesPresentation($menuName)
{
    return false;
}

require_once dirname(__DIR__) . '/asset_usage.php';

function menu_asset_legacy_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$Menus = array(
    1 => array('menu_id' => 1, 'menu_name' => 'navigation', 'menu_type' => 1, 'active' => 1, 'menu_perm' => 3),
    2 => array('menu_id' => 2, 'menu_name' => 'footer', 'menu_type' => 2, 'active' => 1, 'menu_perm' => 3),
    3 => array('menu_id' => 3, 'menu_name' => 'vertical', 'menu_type' => 3, 'active' => 1, 'menu_perm' => 3),
);

MENU_resetAssetUsage();
menu_asset_legacy_assert(!MENU_supportsDemandAssetLoading(), 'legacy renderer must not claim deferred asset support');
menu_asset_legacy_assert(MENU_menuNeedsLegacyCss(2), 'legacy footer CSS fallback must remain available before late rendering');
menu_asset_legacy_assert(MENU_menuNeedsLegacyCss(3), 'legacy vertical CSS fallback must remain available before late rendering');
menu_asset_legacy_assert(MENU_menuNeedsLegacyJs(1), 'legacy navigation SlickNav fallback must remain available');

$menuLegacyConfig['load_legacy_css'] = false;
menu_asset_legacy_assert(!MENU_menuNeedsLegacyCss(2), 'legacy fallback must still respect load_legacy_css=false');
$menuLegacyConfig['load_legacy_js'] = false;
menu_asset_legacy_assert(!MENU_menuNeedsLegacyJs(1), 'legacy fallback must still respect load_legacy_js=false');

echo 'Geeklog 2.1.1 asset fallback tests passed' . PHP_EOL;
