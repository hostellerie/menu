<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | asset_usage.php                                                           |
// |                                                                           |
// | Request-scoped frontend asset usage helpers.                              |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Return true when Geeklog provides the modern document renderer.
 *
 * This capability alone does not guarantee that every block/autotag has already
 * rendered before plugin_getheadercode_menu(). Some render surfaces can still
 * discover Menu usage later in the request.
 *
 * @return bool
 */
function MENU_supportsDemandAssetLoading()
{
    return function_exists('COM_createHTMLDocument');
}

/**
 * Resolve a runtime menu name exactly as MENU_getMenu() does, including the
 * active language variant when one exists.
 *
 * @param string $name
 * @return int
 */
function MENU_resolveMenuId($name)
{
    global $Menus;

    $name = (string) $name;
    $languageId = function_exists('COM_getLanguageId') ? COM_getLanguageId() : '';

    if (!empty($languageId) && is_array($Menus)) {
        $localizedName = $name . '_' . $languageId;
        foreach ($Menus as $menu) {
            if (isset($menu['menu_name'], $menu['menu_id'])
                && $menu['menu_name'] == $localizedName) {
                return (int) $menu['menu_id'];
            }
        }
    }

    if (is_array($Menus)) {
        foreach ($Menus as $menu) {
            if (isset($menu['menu_name'], $menu['menu_id'])
                && strcasecmp(trim($menu['menu_name']), trim($name)) === 0) {
                return (int) $menu['menu_id'];
            }
        }
    }

    return 0;
}

/**
 * Register a menu that has actually reached the rendering boundary.
 *
 * @param int|string $menuID
 * @return void
 */
function MENU_registerAssetUsage($menuID)
{
    $menuID = (int) $menuID;
    if ($menuID <= 0) {
        return;
    }

    if (!isset($GLOBALS['MENU_ASSET_USAGE']) || !is_array($GLOBALS['MENU_ASSET_USAGE'])) {
        $GLOBALS['MENU_ASSET_USAGE'] = array();
    }

    $GLOBALS['MENU_ASSET_USAGE'][$menuID] = true;
}

/**
 * Pre-register assets for a named menu when Geeklog's legacy header lifecycle
 * requires resource knowledge before the menu itself can be rendered.
 *
 * This is only intended for a template variable that has already been confirmed
 * present in the theme source. It applies the same active/permission gate as
 * MENU_getMenu().
 *
 * @param string $name
 * @return int Resolved/registered menu id, or 0
 */
function MENU_registerNamedAssetUsage($name)
{
    global $Menus;

    $menuID = MENU_resolveMenuId($name);
    if ($menuID <= 0
        || !isset($Menus[$menuID])
        || (int) $Menus[$menuID]['active'] !== 1
        || (int) $Menus[$menuID]['menu_perm'] !== 3) {
        return 0;
    }

    MENU_registerAssetUsage($menuID);

    return $menuID;
}

/**
 * Return menu ids rendered or explicitly pre-registered during this request.
 *
 * @return array
 */
function MENU_getUsedMenuIds()
{
    if (!isset($GLOBALS['MENU_ASSET_USAGE']) || !is_array($GLOBALS['MENU_ASSET_USAGE'])) {
        return array();
    }

    return array_keys($GLOBALS['MENU_ASSET_USAGE']);
}

/**
 * Return whether header resource generation should consider this menu.
 *
 * When at least one menu has already been registered, the registry is
 * authoritative and only those menu ids qualify. When the registry is still
 * empty, usage may simply not have been discovered yet (for example an autotag
 * or block rendered after the header hook), so the historical active-menu
 * fallback is preserved to avoid rendering a menu without its legacy CSS/JS.
 *
 * This means exact demand loading is applied whenever Geeklog exposes usage in
 * time, while late-rendered surfaces remain backward compatible.
 *
 * @param int|string $menuID
 * @return bool
 */
function MENU_isAssetUsageRegistered($menuID)
{
    $menuID = (int) $menuID;
    if ($menuID <= 0) {
        return false;
    }

    if (!MENU_supportsDemandAssetLoading()) {
        return true;
    }

    if (!isset($GLOBALS['MENU_ASSET_USAGE'])
        || !is_array($GLOBALS['MENU_ASSET_USAGE'])
        || count($GLOBALS['MENU_ASSET_USAGE']) === 0) {
        return true;
    }

    return isset($GLOBALS['MENU_ASSET_USAGE'][$menuID]);
}

/**
 * Reset request usage. Public primarily so isolated tests can reset state.
 *
 * @return void
 */
function MENU_resetAssetUsage()
{
    $GLOBALS['MENU_ASSET_USAGE'] = array();
}

/**
 * Return whether a rendered menu requires plugin-owned legacy CSS.
 *
 * @param int|string $menuID
 * @return bool
 */
function MENU_menuNeedsLegacyCss($menuID)
{
    global $Menus;

    $menuID = (int) $menuID;
    if (!MENU_isAssetUsageRegistered($menuID)
        || !isset($Menus[$menuID])
        || empty($Menus[$menuID]['active'])) {
        return false;
    }

    if (!MENU_runtimeConfigEnabled('legacy_rendering', true)
        || !MENU_runtimeConfigEnabled('load_legacy_css', true)) {
        return false;
    }

    $menuName = isset($Menus[$menuID]['menu_name']) ? $Menus[$menuID]['menu_name'] : '';

    return !MENU_themeHandlesPresentation($menuName);
}

/**
 * Return whether a rendered menu requires Menu's legacy SlickNav path.
 *
 * Only horizontal cascading menus (type 1) use SlickNav.
 *
 * @param int|string $menuID
 * @return bool
 */
function MENU_menuNeedsLegacyJs($menuID)
{
    global $Menus;

    $menuID = (int) $menuID;
    if (!MENU_isAssetUsageRegistered($menuID)
        || !isset($Menus[$menuID])
        || empty($Menus[$menuID]['active'])
        || (int) $Menus[$menuID]['menu_type'] !== 1) {
        return false;
    }

    if (!MENU_runtimeConfigEnabled('legacy_rendering', true)
        || !MENU_runtimeConfigEnabled('load_legacy_js', true)) {
        return false;
    }

    $menuName = isset($Menus[$menuID]['menu_name']) ? $Menus[$menuID]['menu_name'] : '';

    return !MENU_themeHandlesPresentation($menuName);
}

/**
 * Check whether the active theme source references a historical Menu variable.
 *
 * This deliberately inspects the template source, never the final HTML. Geeklog
 * 2.1.1 uses header.thtml/footer.thtml while the modern document renderer uses
 * index.thtml. Detection is capability-based through COM_createHTMLDocument().
 * Template roots are checked in Geeklog resolution order and the first readable
 * matching source is authoritative. If no readable source can be identified,
 * return true to preserve compatibility with custom/legacy Template engines.
 *
 * @param string $templateName
 * @param object $template
 * @param string $variable
 * @return bool
 */
function MENU_templateUsesVariable($templateName, &$template, $variable)
{
    global $_CONF;

    $templateName = (string) $templateName;
    $variable = (string) $variable;
    $fileNames = array();

    if (MENU_supportsDemandAssetLoading()) {
        if ($templateName === 'header' || $templateName === 'footer') {
            $fileNames[] = 'index.thtml';
        }
    } elseif ($templateName === 'header') {
        $fileNames[] = 'header.thtml';
    } elseif ($templateName === 'footer') {
        $fileNames[] = 'footer.thtml';
    }

    if (empty($fileNames)) {
        return true;
    }

    $roots = array();
    if (is_object($template) && method_exists($template, 'getRoot')) {
        $templateRoots = $template->getRoot();
        if (!is_array($templateRoots)) {
            $templateRoots = array($templateRoots);
        }
        foreach ($templateRoots as $root) {
            if (is_string($root) && $root !== '') {
                $roots[] = $root;
            }
        }
    }

    if (isset($_CONF['path_layout']) && $_CONF['path_layout'] !== '') {
        $roots[] = $_CONF['path_layout'];
    }
    if (isset($_CONF['path_layout_default']) && $_CONF['path_layout_default'] !== '') {
        $roots[] = $_CONF['path_layout_default'];
    }

    $roots = array_unique($roots);
    $needle = '{' . $variable . '}';

    foreach ($fileNames as $fileName) {
        foreach ($roots as $root) {
            $root = rtrim($root, "/\\") . DIRECTORY_SEPARATOR;
            $path = $root . $fileName;
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $source = file_get_contents($path);
            if ($source === false) {
                continue;
            }

            return strpos($source, $needle) !== false;
        }
    }

    return true;
}
