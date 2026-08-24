<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | presentation.php                                                          |
// |                                                                           |
// | Theme presentation hand-off helpers.                                      |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Return presentation capabilities declared by the active theme.
 *
 * A theme may provide layout/<theme>/plugin-presentation.php returning:
 *
 *     array(
 *         'menu' => array('navigation'),
 *     );
 *
 * This manifest is deliberately generic so other plugins can use the same
 * declaration format. A PHP callback remains supported as an optional dynamic
 * mechanism when the theme load order makes it available.
 *
 * @return array
 */
function MENU_themePresentationManifest()
{
    global $_CONF;

    static $manifest = null;
    if ($manifest !== null) {
        return $manifest;
    }

    $manifest = array();
    $layoutPath = isset($_CONF['path_layout']) ? rtrim($_CONF['path_layout'], "/\\") : '';
    if ($layoutPath === '') {
        return $manifest;
    }

    $file = $layoutPath . DIRECTORY_SEPARATOR . 'plugin-presentation.php';
    if (!is_file($file)) {
        return $manifest;
    }

    $declared = include $file;
    if (is_array($declared)) {
        $manifest = $declared;
    }

    return $manifest;
}

/**
 * Return true when the active theme explicitly owns presentation for a Menu
 * resource. A declaration for navigation also covers Geeklog's localized
 * navigation_<language-id> variant for the active language.
 *
 * @param string $menuName
 * @return bool
 */
function MENU_themeHandlesPresentation($menuName)
{
    $menuName = (string) $menuName;

    if (function_exists('theme_handles_plugin_presentation')
        && theme_handles_plugin_presentation('menu', $menuName)) {
        return true;
    }

    $manifest = MENU_themePresentationManifest();
    if (!isset($manifest['menu']) || !is_array($manifest['menu'])) {
        return false;
    }

    $languageId = function_exists('COM_getLanguageId') ? (string) COM_getLanguageId() : '';

    foreach ($manifest['menu'] as $resource) {
        $resource = (string) $resource;
        if (strcasecmp($resource, $menuName) === 0) {
            return true;
        }
        if ($languageId !== '' && strcasecmp($resource . '_' . $languageId, $menuName) === 0) {
            return true;
        }
    }

    return false;
}
