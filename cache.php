<?php

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

$MENU_TEMPLATE_OPTIONS = array(
    'path_cache'    => MENU_dataDir() . 'cache' . DIRECTORY_SEPARATOR,
    'path_prefixes' => array(
        $_CONF['path_themes'],
        $_CONF['path'],
        '/',
    ),
    'incl_phpself_header' => true,
    'cache_by_language' => true,
    'default_vars' => array(
        'site_url' => $_CONF['site_url'],
        'site_admin_url' => $_CONF['site_admin_url'],
        'layout_url' => $_CONF['layout_url'],
        'xhtml' => (defined('XHTML') ? XHTML : ' /'),
    ),
    'hook' => array(),
);

function MENU_CACHE_cleanup_plugin($plugin)
{
    global $MENU_TEMPLATE_OPTIONS;

    if (!empty($plugin)) {
        $plugin = str_replace(array('..', '/', '\\'), '', $plugin);
        $plugin = '__' . $plugin . '__';
    }

    $pathCache = rtrim($MENU_TEMPLATE_OPTIONS['path_cache'], '/\\');
    MENU_cache_clean_directories($pathCache, $plugin);
}

function MENU_CACHE_remove_instance($iid)
{
    global $MENU_TEMPLATE_OPTIONS;

    $iid = str_replace(array('..', '/', '\\', ':'), '', $iid);
    $iid = str_replace('-', '_', $iid);
    $pathCache = rtrim($MENU_TEMPLATE_OPTIONS['path_cache'], '/\\');
    MENU_cache_clean_directories($pathCache, 'instance__' . $iid);
}

function MENU_CACHE_create_instance($iid, $data, $bypassLang = false)
{
    global $MENU_TEMPLATE_OPTIONS, $_CONF;

    if (!MENU_runtimeConfigEnabled('enable_cache', true)) {
        return;
    }

    if (!$bypassLang && $MENU_TEMPLATE_OPTIONS['cache_by_language']) {
        $languageDir = $MENU_TEMPLATE_OPTIONS['path_cache'] . $_CONF['language'];
        if (!is_dir($languageDir)) {
            @mkdir($languageDir, 0755, true);
            @touch($languageDir . DIRECTORY_SEPARATOR . 'index.html');
        }
    }

    $filename = MENU_CACHE_instance_filename($iid, $bypassLang);
    @file_put_contents($filename, $data, LOCK_EX);
}

function MENU_CACHE_check_instance($iid, $bypassLang = false)
{
    if (!MENU_runtimeConfigEnabled('enable_cache', true)) {
        return false;
    }

    $filename = MENU_CACHE_instance_filename($iid, $bypassLang);
    if (!is_file($filename) || is_link($filename)) {
        return false;
    }

    $str = @file_get_contents($filename);
    return $str === false ? false : $str;
}

function MENU_CACHE_get_instance_update($iid, $bypassLang = false)
{
    if (!MENU_runtimeConfigEnabled('enable_cache', true)) {
        return false;
    }

    $filename = MENU_CACHE_instance_filename($iid, $bypassLang);
    if (!is_file($filename) || is_link($filename)) {
        return false;
    }

    return @filemtime($filename);
}

function MENU_CACHE_instance_filename($iid, $bypassLang = false)
{
    global $MENU_TEMPLATE_OPTIONS, $_CONF;

    $pathCache = $MENU_TEMPLATE_OPTIONS['path_cache'];
    if (!$bypassLang && $MENU_TEMPLATE_OPTIONS['cache_by_language']) {
        $pathCache .= $_CONF['language'] . DIRECTORY_SEPARATOR;
    }

    $iid = COM_sanitizeFilename($iid, true);
    return $pathCache . 'instance__' . $iid . '.php';
}

function MENU_compress($buffer)
{
    $buffer = preg_replace('!/\\*[^*]*\\*+([^/][^*]*\\*+)*/!', '', $buffer);
    return str_replace(array("\r\n", "\r", "\n"), '', $buffer);
}
