<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.4.0                                                        |
// +---------------------------------------------------------------------------+
// | services.inc.php                                                          |
// |                                                                           |
// | Read-only Geeklog Plugin Services for navigation discovery.               |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Discover menus available to the current Geeklog request context.
 *
 * The result is permission-aware and intentionally contains no ACL internals
 * or private Menu configuration.
 *
 * @param array $args
 * @param mixed $output
 * @param array $svc_msg
 * @return int Geeklog PLG_RET_* response code
 */
function service_getMenuList_menu($args, &$output, &$svc_msg)
{
    $output = array(
        'provider' => 'menu',
        'provider_family' => 'navigation',
        'provider_contract_version' => function_exists('MENU_getResolvedTreeContractVersion')
            ? (int) MENU_getResolvedTreeContractVersion() : 0,
        'menus' => function_exists('MENU_getAvailableMenus')
            ? MENU_getAvailableMenus() : array(),
    );
    $svc_msg = array();

    return PLG_RET_OK;
}

/**
 * Return one permission-filtered resolved Menu tree.
 *
 * @param array $args Expected key: name
 * @param mixed $output
 * @param array $svc_msg
 * @return int Geeklog PLG_RET_* response code
 */
function service_getMenuTree_menu($args, &$output, &$svc_msg)
{
    $name = is_array($args) && isset($args['name'])
        ? trim((string) $args['name']) : '';

    if ($name === '' || !function_exists('MENU_getResolvedTree')) {
        $output = array();
        $svc_msg = array('error' => 'invalid-request');
        return PLG_RET_ERROR;
    }

    $tree = MENU_getResolvedTree($name);
    if (!is_array($tree)) {
        $output = array();
        $svc_msg = array('error' => 'unavailable');
        return PLG_RET_ERROR;
    }

    $output = array(
        'provider' => 'menu',
        'provider_family' => 'navigation',
        'name' => $name,
        'provider_contract_version' => function_exists('MENU_getResolvedTreeContractVersion')
            ? (int) MENU_getResolvedTreeContractVersion() : 0,
        'nodes' => $tree,
    );
    $svc_msg = array();

    return PLG_RET_OK;
}
