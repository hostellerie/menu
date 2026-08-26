<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | compat.php                                                                |
// |                                                                           |
// | Small compatibility layer for Geeklog 2.1.1 through 2.2.2.               |
// +---------------------------------------------------------------------------+

namespace {
    if (!defined('VERSION')) {
        die('This file can not be used on its own.');
    }
}

namespace Geeklog {
    if (!class_exists('Geeklog\\Input')) {
        /**
         * Minimal fallback matching the subset of Geeklog\Input used by Menu.
         * Newer Geeklog versions provide the native class and never use this.
         */
        class Input
        {
            public static function get($name, $default = null)
            {
                return isset($_GET[$name]) ? $_GET[$name] : $default;
            }

            public static function post($name, $default = null)
            {
                return isset($_POST[$name]) ? $_POST[$name] : $default;
            }

            public static function request($name, $default = null)
            {
                return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
            }

            public static function fGet($name, $default = '')
            {
                $value = self::get($name, $default);
                return is_array($value) ? $value : \COM_applyFilter($value);
            }

            public static function fPost($name, $default = '')
            {
                $value = self::post($name, $default);
                return is_array($value) ? $value : \COM_applyFilter($value);
            }

            public static function fRequest($name, $default = '')
            {
                $value = self::request($name, $default);
                return is_array($value) ? $value : \COM_applyFilter($value);
            }

            public static function fGetOrPost($name, $default = '')
            {
                if (isset($_GET[$name])) {
                    return self::fGet($name, $default);
                }

                return self::fPost($name, $default);
            }
        }
    }
}

namespace {
    if (!function_exists('CTL_plugin_templatePath')) {
        function CTL_plugin_templatePath($plugin)
        {
            global $_CONF;

            $base = $_CONF['path'] . 'plugins/' . $plugin . '/templates/';
            $default = $base . 'default/';

            return is_dir($default) ? $default : $base;
        }
    }

    if (!function_exists('COM_newTemplate')) {
        function COM_newTemplate($path)
        {
            return new Template($path);
        }
    }

    if (!function_exists('COM_versionCompare')) {
        function COM_versionCompare($version1, $version2, $operator = null)
        {
            if ($operator === null) {
                return version_compare($version1, $version2);
            }

            return version_compare($version1, $version2, $operator);
        }
    }

    /*
     * Geeklog 2.1.1 does not provide COM_redirect(). Newer releases do.
     * Keep controller code version-neutral by falling back to the established
     * COM_refresh() redirect helper on older Geeklog versions.
     */

    if (!function_exists('MENU_dbEscape')) {
        function MENU_dbEscape($value)
        {
            return DB_escapeString((string) $value);
        }
    }

    if (!function_exists('MENU_escapeHTML')) {
        function MENU_escapeHTML($value)
        {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
    }

    /*
     * Menu 1.2.x stored some element labels already HTML-encoded. Decode one
     * legacy layer before escaping for output so upgrades remain visually
     * compatible while raw database values can never become markup.
     */
    if (!function_exists('MENU_escapeStoredText')) {
        function MENU_escapeStoredText($value)
        {
            return MENU_escapeHTML(htmlspecialchars_decode((string) $value, ENT_QUOTES));
        }
    }

    if (!function_exists('MENU_safeHref')) {
        function MENU_safeHref($url, $fallback = '#')
        {
            $url = trim((string) $url);
            if ($url === '') {
                return MENU_escapeHTML($fallback);
            }

            $decoded = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
            if (preg_match('/^[\\x00-\\x20]*(?:javascript|vbscript|data):/i', $decoded)) {
                return MENU_escapeHTML($fallback);
            }

            return MENU_escapeHTML($url);
        }
    }

    if (!function_exists('COM_redirect')) {
        function COM_redirect($url)
        {
            if (function_exists('COM_refresh')) {
                echo COM_refresh($url);
                exit;
            }

            if (!headers_sent()) {
                header('Location: ' . $url);
            }
            exit;
        }
    }
}
