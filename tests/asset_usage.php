<?php

// Demand-loaded asset registry tests. Compatible with PHP 5.6+.

define('VERSION', 'test');
function COM_createHTMLDocument() {}
function COM_getLanguageId() { return ''; }

$menuTestConfig = array(
    'legacy_rendering' => true,
    'load_legacy_css' => true,
    'load_legacy_js' => true,
);
$menuThemeOwned = array();

function MENU_runtimeConfigEnabled($key, $default)
{
    global $menuTestConfig;

    return isset($menuTestConfig[$key]) ? (bool) $menuTestConfig[$key] : (bool) $default;
}

function MENU_themeHandlesPresentation($menuName)
{
    global $menuThemeOwned;

    return in_array($menuName, $menuThemeOwned, true);
}

require_once dirname(__DIR__) . '/asset_usage.php';

function menu_asset_test_fail($message)
{
    fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
    exit(1);
}

function menu_asset_test_assert($condition, $message)
{
    if (!$condition) {
        menu_asset_test_fail($message);
    }
}

$Menus = array(
    1 => array('menu_id' => 1, 'menu_name' => 'navigation', 'menu_type' => 1, 'active' => 1, 'menu_perm' => 3),
    2 => array('menu_id' => 2, 'menu_name' => 'footer', 'menu_type' => 2, 'active' => 1, 'menu_perm' => 3),
    3 => array('menu_id' => 3, 'menu_name' => 'vertical', 'menu_type' => 3, 'active' => 1, 'menu_perm' => 3),
    4 => array('menu_id' => 4, 'menu_name' => 'secondary', 'menu_type' => 2, 'active' => 1, 'menu_perm' => 3),
);

menu_asset_test_assert(MENU_supportsDemandAssetLoading(), 'modern renderer capability was not detected');
menu_asset_test_assert(MENU_resolveMenuId('footer') === 2, 'footer menu resolution failed');

// Footer only.
MENU_resetAssetUsage();
MENU_registerAssetUsage(2);
menu_asset_test_assert(MENU_getUsedMenuIds() === array(2), 'footer-only registry is incorrect');
menu_asset_test_assert(MENU_menuNeedsLegacyCss(2), 'footer should need its legacy CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyJs(2), 'footer must not load SlickNav');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(3), 'unused vertical menu must not produce CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyJs(3), 'unused vertical menu must not produce JS');

// Navigation only.
MENU_resetAssetUsage();
MENU_registerAssetUsage(1);
menu_asset_test_assert(MENU_getUsedMenuIds() === array(1), 'navigation-only registry is incorrect');
menu_asset_test_assert(MENU_menuNeedsLegacyCss(1), 'navigation should need legacy CSS');
menu_asset_test_assert(MENU_menuNeedsLegacyJs(1), 'horizontal cascading navigation should need SlickNav');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(2), 'unused footer must not produce CSS');

// Footer + navigation and duplicate registration.
MENU_resetAssetUsage();
MENU_registerAssetUsage(2);
MENU_registerAssetUsage(1);
MENU_registerAssetUsage(2);
menu_asset_test_assert(MENU_getUsedMenuIds() === array(2, 1), 'used menu ids must remain unique and ordered');
menu_asset_test_assert(MENU_menuNeedsLegacyCss(2), 'footer CSS missing when footer + navigation are used');
menu_asset_test_assert(MENU_menuNeedsLegacyCss(1), 'navigation CSS missing when footer + navigation are used');
menu_asset_test_assert(MENU_menuNeedsLegacyJs(1), 'navigation JS missing when footer + navigation are used');

// Several active menus, only one rendered.
MENU_resetAssetUsage();
MENU_registerAssetUsage(4);
menu_asset_test_assert(MENU_getUsedMenuIds() === array(4), 'only secondary should be registered');
menu_asset_test_assert(MENU_menuNeedsLegacyCss(4), 'used secondary menu should need CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(1), 'unused active navigation leaked CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(2), 'unused active footer leaked CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(3), 'unused active vertical menu leaked CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyJs(1), 'unused active navigation leaked JS');

// Theme owns the presentation.
MENU_resetAssetUsage();
MENU_registerAssetUsage(1);
$menuThemeOwned = array('navigation');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(1), 'theme-owned navigation must not load plugin CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyJs(1), 'theme-owned navigation must not load plugin JS');
$menuThemeOwned = array();

// Configuration switches.
MENU_resetAssetUsage();
MENU_registerAssetUsage(2);
$menuTestConfig['load_legacy_css'] = false;
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(2), 'load_legacy_css=false must disable CSS');
$menuTestConfig['load_legacy_css'] = true;

MENU_resetAssetUsage();
MENU_registerAssetUsage(1);
$menuTestConfig['load_legacy_js'] = false;
menu_asset_test_assert(!MENU_menuNeedsLegacyJs(1), 'load_legacy_js=false must disable SlickNav');
$menuTestConfig['load_legacy_js'] = true;

$menuTestConfig['legacy_rendering'] = false;
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(1), 'legacy_rendering=false must disable legacy CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyJs(1), 'legacy_rendering=false must disable legacy JS');
$menuTestConfig['legacy_rendering'] = true;

// Named pre-registration uses the same visibility gate as MENU_getMenu().
MENU_resetAssetUsage();
menu_asset_test_assert(MENU_registerNamedAssetUsage('footer') === 2, 'named footer pre-registration failed');
menu_asset_test_assert(MENU_getUsedMenuIds() === array(2), 'named pre-registration did not register footer');

// Invalid ids are ignored.
MENU_resetAssetUsage();
MENU_registerAssetUsage(0);
MENU_registerAssetUsage(-1);
menu_asset_test_assert(MENU_getUsedMenuIds() === array(), 'invalid ids must not enter the registry');

echo 'Demand-loaded asset registry tests passed' . PHP_EOL;
