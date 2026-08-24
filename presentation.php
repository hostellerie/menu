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

/**
 * Replace the legacy "Admin Home" entry in Menu's own admin navigation with
 * a link to the Menu configuration POST bridge.
 *
 * ADMIN_createMenu() only accepts URLs, while Geeklog's configuration manager
 * selects a plugin configuration group through POST (conf_group=menu). The
 * bridge keeps the navigation as a normal link and performs the required POST.
 *
 * @param string $html
 * @return string
 */
function MENU_rewriteAdminConfigurationNavigation($html)
{
    global $_CONF, $LANG_ADMIN, $LANG_MENU01;

    if (!is_string($html) || $html === '') {
        return $html;
    }

    if (!isset($LANG_ADMIN['admin_home']) || !isset($LANG_MENU01['configuration'])) {
        return $html;
    }

    $adminUrl = rtrim($_CONF['site_admin_url'], '/');
    $bridgeUrl = $adminUrl . '/plugins/menu/configuration.php';
    $adminHome = (string) $LANG_ADMIN['admin_home'];
    $configuration = (string) $LANG_MENU01['configuration'];

    // Double quotes keep the quote character class readable and avoid the
    // ambiguous escaping that broke parsing on PHP 5.6.
    $pattern = "~(<a\\b[^>]*\\bhref=)([\"'])"
             . preg_quote($adminUrl, '~')
             . "/?\\2([^>]*>)(.*?)</a>~is";

    return preg_replace_callback(
        $pattern,
        function ($matches) use ($bridgeUrl, $adminHome, $configuration) {
            $label = trim(html_entity_decode(strip_tags($matches[4]), ENT_QUOTES, 'UTF-8'));
            if ($label !== $adminHome) {
                return $matches[0];
            }

            $quote = $matches[2];
            $safeUrl = htmlspecialchars($bridgeUrl, ENT_QUOTES, 'UTF-8');
            $safeLabel = htmlspecialchars($configuration, ENT_QUOTES, 'UTF-8');

            return $matches[1] . $quote . $safeUrl . $quote
                 . $matches[3] . $safeLabel . '</a>';
        },
        $html
    );
}

/**
 * MENU's legacy admin page builds its navigation internally with
 * ADMIN_createMenu(). Start a narrow output buffer only for that page so the
 * old Admin Home entry can be replaced without changing Geeklog core APIs.
 */
function MENU_enableAdminConfigurationNavigation()
{
    if (!isset($_SERVER['SCRIPT_NAME'])) {
        return;
    }

    $script = str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']);
    if (substr($script, -29) !== '/admin/plugins/menu/index.php') {
        return;
    }

    ob_start('MENU_rewriteAdminConfigurationNavigation');
}

MENU_enableAdminConfigurationNavigation();