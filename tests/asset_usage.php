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

class MenuAssetTemplateFixture
{
    private $root;
    private $values;

    public function __construct($root, $values)
    {
        $this->root = $root;
        $this->values = $values;
    }

    public function getRoot()
    {
        return array($this->root);
    }

    public function get_vars()
    {
        return $this->values;
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

// Empty modern registry is authoritative: no menu assets until usage is known.
MENU_resetAssetUsage();
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(1), 'empty modern registry must not leak navigation CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(2), 'empty modern registry must not leak footer CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(3), 'empty modern registry must not leak vertical CSS');

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

// Template-assigned autotag preflight must select only the referenced menu.
$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'menu-asset-preflight-' . uniqid('', true);
if (!mkdir($dir, 0700, true)) {
    menu_asset_test_fail('unable to create preflight fixture directory');
}
file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.thtml', '<html>{footer_text}</html>');
$_CONF = array('path_layout' => $dir . DIRECTORY_SEPARATOR);
$template = new MenuAssetTemplateFixture($dir, array(
    'footer_text' => '<div>[menu:footer]</div>',
    'other' => '<p>No menu here</p>',
));
MENU_resetAssetUsage();
MENU_preflightTemplateAssets('header', $template);
menu_asset_test_assert(MENU_getUsedMenuIds() === array(2), 'preflight must register only footer');
menu_asset_test_assert(MENU_menuNeedsLegacyCss(2), 'preflighted footer must receive CSS');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(3), 'preflight must not leak vertical CSS');
@unlink($dir . DIRECTORY_SEPARATOR . 'index.thtml');
@rmdir($dir);

// Direct autotag in active template source is also preflighted.
$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'menu-asset-source-' . uniqid('', true);
if (!mkdir($dir, 0700, true)) {
    menu_asset_test_fail('unable to create source fixture directory');
}
file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.thtml', '<html><footer>[menu:footer]</footer></html>');
$_CONF = array('path_layout' => $dir . DIRECTORY_SEPARATOR);
$template = new MenuAssetTemplateFixture($dir, array());
MENU_resetAssetUsage();
MENU_preflightTemplateAssets('footer', $template);
menu_asset_test_assert(MENU_getUsedMenuIds() === array(2), 'source preflight must register footer');
menu_asset_test_assert(!MENU_menuNeedsLegacyCss(3), 'source preflight must not register vertical menu');
@unlink($dir . DIRECTORY_SEPARATOR . 'index.thtml');
@rmdir($dir);

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
