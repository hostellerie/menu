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
 * Return contextual help for Geeklog's native configuration editor.
 *
 * Geeklog calls plugin_getconfigtooltip_<plugin>() for the question-mark help
 * shown beside configuration options. English is the canonical fallback; the
 * small French overlay keeps the currently maintained translation localized.
 * Unknown and obsolete options deliberately return an empty string so Geeklog
 * does not add a meaningless tooltip.
 *
 * @param string $id Configuration option name
 * @return string
 */
function plugin_getconfigtooltip_menu($id)
{
    global $_CONF;

    $tooltips = array(
        'enable_cache' => 'Caches generated Menu data to avoid rebuilding it on every request. Disable only while diagnosing cache-related behavior.',
        'accessibility_markup' => 'Adds navigation and ARIA attributes to legacy Menu output to improve accessibility without changing the menu structure.',
        'external_link_protection' => 'Adds rel="noopener noreferrer" to external links opened in a new window to prevent the opened page from controlling the original window.',
        'allow_php_elements' => 'Allows menu items that call trusted PHP functions. Keep disabled unless the site explicitly relies on this advanced legacy feature.',
        'legacy_rendering' => 'Uses Menu\'s historical HTML/CSS renderer. Disable only when the active theme or another consumer fully handles menu presentation.',
        'load_legacy_css' => 'Loads Menu-generated legacy styles for menus whose presentation is not handled by the active theme.',
        'load_legacy_js' => 'Loads legacy JavaScript when required by Menu, including responsive SlickNav behavior for compatible horizontal menus.',
        'debug' => 'Writes additional Menu diagnostic messages to the Geeklog error log. Enable temporarily while troubleshooting.',
    );

    $language = isset($_CONF['language']) ? (string) $_CONF['language'] : 'english';
    if ($language === 'french_france_utf-8' || $language === 'french_france') {
        $tooltips = array_replace($tooltips, array(
            'enable_cache' => 'Met en cache les données générées par Menu afin d’éviter de les reconstruire à chaque requête. À désactiver uniquement pour diagnostiquer un problème de cache.',
            'accessibility_markup' => 'Ajoute les attributs de navigation et ARIA au rendu historique de Menu afin d’améliorer l’accessibilité sans modifier la structure du menu.',
            'external_link_protection' => 'Ajoute rel="noopener noreferrer" aux liens externes ouverts dans une nouvelle fenêtre afin d’empêcher la page ouverte de contrôler la fenêtre d’origine.',
            'allow_php_elements' => 'Autorise les éléments de menu qui appellent des fonctions PHP de confiance. À laisser désactivé sauf si le site utilise explicitement cette fonction historique avancée.',
            'legacy_rendering' => 'Utilise le moteur HTML/CSS historique de Menu. À désactiver uniquement si le thème actif ou un autre composant prend entièrement en charge la présentation du menu.',
            'load_legacy_css' => 'Charge les styles historiques générés par Menu pour les menus dont la présentation n’est pas prise en charge par le thème actif.',
            'load_legacy_js' => 'Charge le JavaScript historique lorsque Menu en a besoin, notamment SlickNav pour certains menus horizontaux responsives.',
            'debug' => 'Ajoute des messages de diagnostic Menu dans le journal d’erreurs de Geeklog. À activer temporairement pour le dépannage.',
        ));
    }

    return isset($tooltips[$id]) ? $tooltips[$id] : '';
}

/**
 * Write a diagnostic message only when Menu debug logging is enabled.
 *
 * @param string $message
 * @return void
 */
function MENU_debugLog($message)
{
    if (!MENU_runtimeConfigEnabled('debug', false) || !function_exists('COM_errorLog')) {
        return;
    }

    COM_errorLog('Menu: ' . (string) $message, 1);
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
