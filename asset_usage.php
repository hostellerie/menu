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
 * Return menu ids rendered during the current request.
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
 * Return true when a menu has been registered during this request.
 *
 * @param int|string $menuID
 * @return bool
 */
function MENU_isAssetUsageRegistered($menuID)
{
    $menuID = (int) $menuID;

    return isset($GLOBALS['MENU_ASSET_USAGE'])
        && is_array($GLOBALS['MENU_ASSET_USAGE'])
        && isset($GLOBALS['MENU_ASSET_USAGE'][$menuID]);
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

    if (function_exists('COM_createHTMLDocument')) {
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
