<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | admin_security.php                                                        |
// |                                                                           |
// | Shared administration security helpers.                                   |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Return the Menu administration modes that mutate persistent state.
 *
 * Display-only modes such as menu, edit, config, new and newmenu are
 * deliberately excluded. The list mirrors the legacy admin/index.php routing
 * table and is kept in one place so CSRF enforcement cannot drift between
 * actions.
 *
 * @return array
 */
function MENU_adminMutationModes()
{
    return array(
        'move',
        'saveedit',
        'save',
        'savenewmenu',
        'saveclonemenu',
        'saveeditmenu',
        'activate',
        'menuactivate',
        'delete',
        'deletemenu',
        'savecfg',
        'disablemenu',
    );
}

/**
 * Return true when a routed admin mode changes state.
 *
 * @param string $mode
 * @return bool
 */
function MENU_adminModeMutates($mode)
{
    return in_array((string) $mode, MENU_adminMutationModes(), true);
}

/**
 * Return true for legacy POST mutations that do not use a routed mode.
 *
 * The old controller handles "defaults" and drag/drop "orders" outside the
 * main switch. They still require exactly the same CSRF protection.
 *
 * @param array $post
 * @return bool
 */
function MENU_adminPostMutates($post)
{
    if (!is_array($post)) {
        return false;
    }

    return isset($post['defaults']) || isset($post['orders']);
}

/**
 * Return true when the current/requested operation needs a CSRF token.
 *
 * @param string $mode
 * @param array  $post
 * @return bool
 */
function MENU_adminRequestMutates($mode, $post = array())
{
    return MENU_adminModeMutates($mode) || MENU_adminPostMutates($post);
}

/**
 * Check Geeklog's native CSRF token for a mutating request.
 *
 * This helper intentionally delegates token lifetime and session binding to
 * Geeklog. Both Geeklog 2.1.1 and 2.2.2 provide SEC_checkToken().
 *
 * @return bool
 */
function MENU_adminCheckToken()
{
    return function_exists('SEC_checkToken') && SEC_checkToken();
}

/**
 * Create a Geeklog-native CSRF token for Menu admin forms and mutation links.
 *
 * @return string
 */
function MENU_adminCreateToken()
{
    return function_exists('SEC_createToken') ? SEC_createToken() : '';
}

/**
 * Return the configured Geeklog CSRF field name.
 *
 * @return string
 */
function MENU_adminTokenName()
{
    return defined('CSRF_TOKEN') ? CSRF_TOKEN : 'token';
}

/**
 * Build a hidden input containing a Geeklog CSRF token.
 *
 * @param string|null $token
 * @return string
 */
function MENU_adminTokenInput($token = null)
{
    if ($token === null) {
        $token = MENU_adminCreateToken();
    }
    if ($token === '') {
        return '';
    }

    return '<input type="hidden" name="'
        . htmlspecialchars(MENU_adminTokenName(), ENT_QUOTES, 'UTF-8')
        . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Cast an administration identifier to a non-negative integer.
 *
 * @param mixed $value
 * @return int
 */
function MENU_adminId($value)
{
    $value = (int) $value;
    return $value > 0 ? $value : 0;
}

/**
 * Escape a string for SQL using Geeklog's database abstraction when possible.
 *
 * @param mixed $value
 * @return string
 */
function MENU_adminDbEscape($value)
{
    $value = (string) $value;
    if (function_exists('DB_escapeString')) {
        return DB_escapeString($value);
    }

    // Compatibility fallback for test/minimal environments only. Production
    // Geeklog 2.1.1+ provides DB_escapeString().
    return addslashes($value);
}
