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
