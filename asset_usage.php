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
 * Pre-register assets for a named menu when the menu reference is known before
 * the menu HTML itself is rendered.
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
 * On Geeklog's modern document renderer the registry is authoritative, even
 * when empty. Menu references that live in template source or assigned template
 * values are preflighted before plugin_getheadercode_menu() runs. This prevents
 * unrelated active menus from leaking CSS/JS into the page.
 *
 * Geeklog 2.1.1 keeps the historical fallback because COM_siteHeader() can
 * finalize the head before arbitrary page content or autotags are rendered.
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
 * Extract [menu:name] references from a string.
 *
 * @param mixed $value
 * @return array
 */
function MENU_extractAutotagMenuNames($value)
{
    $names = array();

    if (!is_string($value) || stripos($value, '[menu:') === false) {
        return $names;
    }

    if (preg_match_all('/\\[menu:([^\\]]+)\\]/i', $value, $matches)) {
        foreach ($matches[1] as $name) {
            $name = trim($name);
            if ($name !== '' && !in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
    }

    return $names;
}

/**
 * Register Menu autotags found in a string.
 *
 * @param mixed $value
 * @return void
 */
function MENU_registerAutotagAssetsFromValue($value)
{
    foreach (MENU_extractAutotagMenuNames($value) as $name) {
        MENU_registerNamedAssetUsage($name);
    }
}

/**
 * Inspect values already assigned to the active Template object.
 *
 * This is request state available before the head is generated; it is not a
 * scan of the final HTML. It catches configured header/footer values containing
 * [menu:footer] and similar references that Geeklog expands later.
 *
 * @param object $template
 * @return void
 */
function MENU_preflightAssignedTemplateValues(&$template)
{
    if (!is_object($template) || !method_exists($template, 'get_vars')) {
        return;
    }

    $values = $template->get_vars();
    if (!is_array($values)) {
        return;
    }

    foreach ($values as $value) {
        if (is_string($value)) {
            MENU_registerAutotagAssetsFromValue($value);
        }
    }
}

/**
 * Return the candidate source files for a Geeklog template hook.
 *
 * @param string $templateName
 * @return array
 */
function MENU_templateSourceFileNames($templateName)
{
    $templateName = (string) $templateName;

    if (MENU_supportsDemandAssetLoading()) {
        if ($templateName === 'header' || $templateName === 'footer') {
            return array('index.thtml');
        }
    } elseif ($templateName === 'header') {
        return array('header.thtml');
    } elseif ($templateName === 'footer') {
        return array('footer.thtml');
    }

    return array();
}

/**
 * Return template roots in Geeklog resolution order.
 *
 * @param object $template
 * @return array
 */
function MENU_templateRoots(&$template)
{
    global $_CONF;

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

    return array_unique($roots);
}

/**
 * Read the first active template source matching Geeklog's root precedence.
 *
 * @param string $templateName
 * @param object $template
 * @return string|null
 */
function MENU_readTemplateSource($templateName, &$template)
{
    $fileNames = MENU_templateSourceFileNames($templateName);
    if (empty($fileNames)) {
        return null;
    }

    $roots = MENU_templateRoots($template);

    foreach ($fileNames as $fileName) {
        foreach ($roots as $root) {
            $path = rtrim($root, "/\\") . DIRECTORY_SEPARATOR . $fileName;
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            $source = file_get_contents($path);
            if ($source !== false) {
                return $source;
            }
        }
    }

    return null;
}

/**
 * Pre-register Menu autotags visible before header asset generation.
 *
 * @param string $templateName
 * @param object $template
 * @return void
 */
function MENU_preflightTemplateAssets($templateName, &$template)
{
    if (!MENU_supportsDemandAssetLoading()) {
        return;
    }

    MENU_preflightAssignedTemplateValues($template);

    $source = MENU_readTemplateSource($templateName, $template);
    if ($source !== null) {
        MENU_registerAutotagAssetsFromValue($source);
    }
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
 * This deliberately inspects template source/request state, never final HTML.
 * On the modern renderer this hook also preflights Menu autotags before header
 * resources are generated so only the referenced menus enter the registry.
 *
 * @param string $templateName
 * @param object $template
 * @param string $variable
 * @return bool
 */
function MENU_templateUsesVariable($templateName, &$template, $variable)
{
    $templateName = (string) $templateName;
    $variable = (string) $variable;

    MENU_preflightTemplateAssets($templateName, $template);

    $fileNames = MENU_templateSourceFileNames($templateName);
    if (empty($fileNames)) {
        return true;
    }

    $source = MENU_readTemplateSource($templateName, $template);
    if ($source === null) {
        return true;
    }

    return strpos($source, '{' . $variable . '}') !== false;
}
