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
 * Return true when the active theme explicitly owns presentation for a Menu
 * resource. The callback name is intentionally generic so other plugins can
 * adopt the same contract without Menu knowing anything about a specific
 * theme.
 *
 * Themes may implement:
 *
 *     theme_handles_plugin_presentation($plugin, $resource)
 *
 * and return true for resources they render themselves.
 *
 * @param string $menuName
 * @return bool
 */
function MENU_themeHandlesPresentation($menuName)
{
    if (!function_exists('theme_handles_plugin_presentation')) {
        return false;
    }

    return (bool) theme_handles_plugin_presentation('menu', (string) $menuName);
}
