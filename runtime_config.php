<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | runtime_config.php                                                        |
// |                                                                           |
// | Runtime accessors for global Menu configuration.                          |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Return the conservative 1.3.0 default for one runtime option.
 *
 * Keep these defaults in sync with config.php. They deliberately preserve the
 * classic Menu renderer on upgrades while disabling PHP callbacks by default.
 *
 * @param string $name
 * @param mixed  $fallback
 * @return mixed
 */
function MENU_runtimeConfigDefault($name, $fallback = null)
{
    $defaults = array(
        'enable_cache'             => true,
        'accessibility_markup'     => true,
        'external_link_protection' => true,
        'allow_php_elements'       => false,
        'legacy_rendering'         => true,
        'load_legacy_css'          => true,
        'load_legacy_js'           => true,
        'debug'                    => false,
    );

    return array_key_exists($name, $defaults) ? $defaults[$name] : $fallback;
}

/**
 * Read one global Menu option without assuming configuration load order.
 *
 * @param string $name
 * @param mixed  $fallback
 * @return mixed
 */
function MENU_runtimeConfig($name, $fallback = null)
{
    global $_MENU_CONF;

    if (isset($_MENU_CONF) && is_array($_MENU_CONF) && array_key_exists($name, $_MENU_CONF)) {
        return $_MENU_CONF[$name];
    }

    return MENU_runtimeConfigDefault($name, $fallback);
}

/**
 * Read a global Menu option as a boolean.
 *
 * Geeklog's configuration manager may return booleans, integers or their
 * string equivalents depending on version/database driver.
 *
 * @param string    $name
 * @param bool|null $fallback
 * @return bool
 */
function MENU_runtimeConfigEnabled($name, $fallback = null)
{
    if ($fallback === null) {
        $fallback = (bool) MENU_runtimeConfigDefault($name, false);
    }

    $value = MENU_runtimeConfig($name, $fallback);

    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) || is_float($value)) {
        return ((int) $value) !== 0;
    }

    $value = strtolower(trim((string) $value));
    if ($value === '1' || $value === 'true' || $value === 'yes' || $value === 'on') {
        return true;
    }
    if ($value === '0' || $value === 'false' || $value === 'no' || $value === 'off' || $value === '') {
        return false;
    }

    return (bool) $fallback;
}

/**
 * Provide the documentation URL used by Geeklog configuration help.
 *
 * Returning a plugin-owned config document lets Geeklog render the same
 * structured VARIABLE / DEFAULT VALUE / DESCRIPTION tooltip used by Core
 * plugins such as Static Pages.
 *
 * @param string $file Documentation file being requested
 * @return mixed URL or false when not available
 */
function plugin_getdocumentationurl_menu($file)
{
    global $_CONF;

    if ($file !== 'config' && $file !== 'index') {
        return false;
    }

    if (empty($_CONF['site_url'])) {
        return false;
    }

    return rtrim((string) $_CONF['site_url'], '/') . '/menu/config.html';
}

/**
 * Use Geeklog's documentation-backed configuration tooltip.
 *
 * NULL is intentional: config.class.php interprets it as a request to load
 * the matching desc_<option> row from plugin_getdocumentationurl_menu().
 *
 * @param string $id Configuration option name
 * @return null
 */
function plugin_getconfigtooltip_menu($id)
{
    return null;
}

/**
 * Write a diagnostic message only when Menu debug logging is enabled.
 *
 * @param string $message
 * @return void
 */
function MENU_debugLog($message)
{
    global $_CONF;

    if (!MENU_runtimeConfigEnabled('debug', false)) {
        return;
    }

    $line = 'Menu: ' . (string) $message;
    $debugLines = array($line);

    $geeklogLoggerAvailable = function_exists('COM_errorLog');
    $pathLog = isset($_CONF['path_log']) ? (string) $_CONF['path_log'] : '';
    $errorLog = $pathLog !== ''
        ? rtrim($pathLog, "/\\") . DIRECTORY_SEPARATOR . 'error.log'
        : '';

    $debugLines[] = 'Geeklog log channel: COM_errorLog='
        . ($geeklogLoggerAvailable ? 'yes' : 'no')
        . ', path_log=' . ($pathLog !== '' ? $pathLog : '[unset]')
        . ', path_exists=' . ($pathLog !== '' && is_dir($pathLog) ? 'yes' : 'no')
        . ', path_writable=' . ($pathLog !== '' && is_writable($pathLog) ? 'yes' : 'no')
        . ', error_log_exists=' . ($errorLog !== '' && file_exists($errorLog) ? 'yes' : 'no')
        . ', error_log_writable=' . ($errorLog !== '' && file_exists($errorLog) && is_writable($errorLog) ? 'yes' : 'no')
        . '.';

    $geeklogResult = null;
    if ($geeklogLoggerAvailable) {
        clearstatcache(true, $errorLog);
        $sizeBefore = ($errorLog !== '' && is_file($errorLog)) ? @filesize($errorLog) : false;

        $geeklogResult = COM_errorLog($line, 1);

        clearstatcache(true, $errorLog);
        $sizeAfter = ($errorLog !== '' && is_file($errorLog)) ? @filesize($errorLog) : false;
        $delta = ($sizeBefore !== false && $sizeAfter !== false)
            ? ((int) $sizeAfter - (int) $sizeBefore)
            : null;

        $debugLines[] = 'Geeklog log channel result: '
            . ($geeklogResult === '' || $geeklogResult === null
                ? '[empty]'
                : trim(strip_tags((string) $geeklogResult)))
            . ', size_before=' . ($sizeBefore === false ? '[unknown]' : (string) $sizeBefore)
            . ', size_after=' . ($sizeAfter === false ? '[unknown]' : (string) $sizeAfter)
            . ', delta=' . ($delta === null ? '[unknown]' : (string) $delta)
            . ', realpath=' . ($errorLog !== '' && realpath($errorLog) !== false ? realpath($errorLog) : '[unresolved]')
            . '.';
    } else {
        error_log($line);
        $debugLines[] = 'Geeklog log channel result: COM_errorLog unavailable; PHP error_log fallback used.';
    }

    /*
     * Keep a plugin-owned private debug log so diagnostics do not depend on
     * Geeklog/PHP logging configuration. MENU_dataDir() may not yet be loaded
     * in very early bootstrap contexts, so detect it conservatively.
     */
    if (function_exists('MENU_dataDir')) {
        $dataDir = MENU_dataDir();
        if ($dataDir !== '') {
            if (!is_dir($dataDir)) {
                @mkdir($dataDir, 0755, true);
            }
            if (is_dir($dataDir) && is_writable($dataDir)) {
                $payload = '';
                foreach ($debugLines as $debugLine) {
                    $payload .= '[' . date('Y-m-d H:i:s') . '] ' . $debugLine . PHP_EOL;
                }
                @file_put_contents(
                    rtrim($dataDir, "/\\") . DIRECTORY_SEPARATOR . 'menu-debug.log',
                    $payload,
                    FILE_APPEND | LOCK_EX
                );
            }
        }
    }
}

/**
 * Return true when an absolute URL points outside the configured site host.
 * Relative URLs and Menu macros are treated as internal.
 *
 * @param string $url
 * @return bool
 */
function MENU_isExternalUrl($url)
{
    global $_CONF;

    $url = trim((string) $url);
    if ($url === '' || strpos($url, '%site_') !== false || strpos($url, '//') === false) {
        return false;
    }

    $urlHost = parse_url(html_entity_decode($url, ENT_QUOTES, 'UTF-8'), PHP_URL_HOST);
    $siteHost = isset($_CONF['site_url']) ? parse_url($_CONF['site_url'], PHP_URL_HOST) : '';

    if ($urlHost === null || $urlHost === false || $urlHost === '') {
        return false;
    }
    if ($siteHost === null || $siteHost === false || $siteHost === '') {
        return true;
    }

    return strcasecmp((string) $urlHost, (string) $siteHost) !== 0;
}

/**
 * Build target/rel attributes for a legacy rendered link.
 *
 * @param string $url
 * @param string $target
 * @return string
 */
function MENU_legacyLinkAttributes($url, $target)
{
    $target = trim((string) $target);
    $attributes = '';

    if ($target !== '') {
        $attributes .= ' target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
    }

    if ($target === '_blank'
        && MENU_runtimeConfigEnabled('external_link_protection', true)
        && MENU_isExternalUrl($url)) {
        $attributes .= ' rel="noopener noreferrer"';
    }

    return $attributes;
}

/**
 * Return presentation-neutral rel data for the resolved-tree API.
 *
 * @param string $url
 * @param string $target
 * @return string
 */
function MENU_resolvedLinkRel($url, $target)
{
    if ((string) $target === '_blank'
        && MENU_runtimeConfigEnabled('external_link_protection', true)
        && MENU_isExternalUrl($url)) {
        return 'noopener noreferrer';
    }

    return '';
}

/**
 * Accessibility attributes for the outer legacy navigation container.
 *
 * @param string $label
 * @return string
 */
function MENU_legacyNavigationAttributes($label)
{
    if (!MENU_runtimeConfigEnabled('accessibility_markup', true)) {
        return '';
    }

    $label = trim(strip_tags((string) $label));
    if ($label === '') {
        $label = 'Menu';
    }

    return ' role="navigation" aria-label="'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '"';
}

/**
 * Accessibility attributes for a legacy menu item owning child navigation.
 *
 * @return string
 */
function MENU_legacyParentAttributes()
{
    if (!MENU_runtimeConfigEnabled('accessibility_markup', true)) {
        return '';
    }

    return ' aria-haspopup="true" aria-expanded="false"';
}
