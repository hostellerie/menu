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

/**
 * Return true only for the Menu administration controller.
 *
 * @return bool
 */
function MENU_adminIsControllerRequest()
{
    $script = isset($_SERVER['PHP_SELF']) ? (string) $_SERVER['PHP_SELF'] : '';
    $script = str_replace('\\', '/', strtolower($script));

    return substr($script, -29) === '/admin/plugins/menu/index.php';
}

/**
 * Read the current routed mode without depending on Geeklog\Input.
 *
 * @return string
 */
function MENU_adminCurrentMode()
{
    if (isset($_POST['mode'])) {
        return strtolower(trim((string) $_POST['mode']));
    }
    if (isset($_GET['mode'])) {
        return strtolower(trim((string) $_GET['mode']));
    }

    return '';
}

/**
 * Stop a state-changing request whose Geeklog security token is invalid.
 *
 * @return void
 */
function MENU_adminRejectInvalidToken()
{
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
    }

    $title = 'Authentication Required';
    $message = 'The security token for this operation has expired or is invalid. Please reload the administration page and try again.';

    if (function_exists('COM_output') && function_exists('COM_createHTMLDocument')) {
        if (function_exists('COM_showMessageText')) {
            COM_output(COM_createHTMLDocument(COM_showMessageText($message, $title)));
        } else {
            COM_output(COM_createHTMLDocument('<h2>' . $title . '</h2><p>' . $message . '</p>'));
        }
    } else {
        echo $title . ': ' . $message;
    }

    exit;
}

/**
 * Enforce Geeklog's CSRF token before the legacy controller can mutate state.
 *
 * @return void
 */
function MENU_adminEnforceCsrf()
{
    if (!MENU_adminIsControllerRequest()) {
        return;
    }

    $mode = MENU_adminCurrentMode();
    if (!MENU_adminRequestMutates($mode, $_POST)) {
        return;
    }

    if (!MENU_adminCheckToken()) {
        MENU_adminRejectInvalidToken();
    }
}

/**
 * Register a client-side compatibility bridge for the legacy Menu admin UI.
 *
 * The old templates predate Geeklog's systematic CSRF protection. Menu's
 * administration already requires JavaScript, so this bridge adds the native
 * Geeklog token to existing POST forms, drag/drop AJAX requests and the legacy
 * GET mutation links. Destructive GET actions will be converted to POST in a
 * later cleanup; until then they are at least session-token protected.
 *
 * @return void
 */
function MENU_adminRegisterTokenBridge()
{
    global $_SCRIPTS;

    if (!MENU_adminIsControllerRequest() || !isset($_SCRIPTS) || !is_object($_SCRIPTS)) {
        return;
    }

    $token = MENU_adminCreateToken();
    if ($token === '') {
        return;
    }

    $tokenName = MENU_adminTokenName();
    $nameJson = json_encode($tokenName);
    $tokenJson = json_encode($token);

    if ($nameJson === false || $tokenJson === false) {
        return;
    }

    if (method_exists($_SCRIPTS, 'setJavaScriptLibrary')) {
        $_SCRIPTS->setJavaScriptLibrary('jquery');
    }

    $js = "jQuery(function($) {\n"
        . "    var tokenName = " . $nameJson . ";\n"
        . "    var tokenValue = " . $tokenJson . ";\n"
        . "    var menuAdmin = '/plugins/menu/index.php';\n"
        . "    $('form').each(function() {\n"
        . "        var form = $(this);\n"
        . "        var action = form.attr('action') || window.location.pathname;\n"
        . "        if (action.indexOf(menuAdmin) === -1) { return; }\n"
        . "        if (form.find('input[name=\"' + tokenName + '\"]').length === 0) {\n"
        . "            $('<input>', {type: 'hidden', name: tokenName, value: tokenValue}).appendTo(form);\n"
        . "        }\n"
        . "    });\n"
        . "    $('a[href*=\"/plugins/menu/index.php\"]').each(function() {\n"
        . "        var link = $(this);\n"
        . "        var href = link.attr('href') || '';\n"
        . "        if (!/[?&]mode=(?:move|delete|deletemenu)(?:&|$)/.test(href)) { return; }\n"
        . "        if (href.indexOf(encodeURIComponent(tokenName) + '=') !== -1 || href.indexOf(tokenName + '=') !== -1) { return; }\n"
        . "        link.attr('href', href + (href.indexOf('?') === -1 ? '?' : '&') + encodeURIComponent(tokenName) + '=' + encodeURIComponent(tokenValue));\n"
        . "    });\n"
        . "    if ($.ajaxPrefilter) {\n"
        . "        $.ajaxPrefilter(function(options, originalOptions) {\n"
        . "            var url = options.url || '';\n"
        . "            var method = (options.type || options.method || 'GET').toUpperCase();\n"
        . "            if (method !== 'POST' || url.indexOf(menuAdmin) === -1) { return; }\n"
        . "            if (typeof options.data === 'string') {\n"
        . "                if (options.data.indexOf(encodeURIComponent(tokenName) + '=') === -1) {\n"
        . "                    options.data += (options.data ? '&' : '') + encodeURIComponent(tokenName) + '=' + encodeURIComponent(tokenValue);\n"
        . "                }\n"
        . "            } else {\n"
        . "                if (!originalOptions.data || typeof originalOptions.data !== 'object') { originalOptions.data = {}; }\n"
        . "                originalOptions.data[tokenName] = tokenValue;\n"
        . "                options.data = $.param(originalOptions.data);\n"
        . "            }\n"
        . "        });\n"
        . "    }\n"
        . "});";

    if (method_exists($_SCRIPTS, 'setJavaScript')) {
        $_SCRIPTS->setJavaScript($js, true);
    }
}

// The helper is loaded through storage.php before admin/index.php enters its
// routing switch. Enforce first, then prepare the token bridge on safe display
// requests. Mutating requests already carry the token from the previous page.
if (MENU_adminIsControllerRequest()) {
    MENU_adminEnforceCsrf();
    MENU_adminRegisterTokenBridge();
}
