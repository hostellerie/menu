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
 * Return the legacy mutation modes that must no longer execute through GET.
 *
 * @return array
 */
function MENU_adminPostOnlyModes()
{
    return array('move', 'delete', 'deletemenu');
}

function MENU_adminModeMutates($mode)
{
    return in_array((string) $mode, MENU_adminMutationModes(), true);
}

function MENU_adminModeRequiresPost($mode)
{
    return in_array((string) $mode, MENU_adminPostOnlyModes(), true);
}

function MENU_adminPostMutates($post)
{
    if (!is_array($post)) {
        return false;
    }

    return isset($post['defaults']) || isset($post['orders']);
}

function MENU_adminRequestMutates($mode, $post = array())
{
    return MENU_adminModeMutates($mode) || MENU_adminPostMutates($post);
}

function MENU_adminRequestMethod()
{
    return isset($_SERVER['REQUEST_METHOD'])
        ? strtoupper((string) $_SERVER['REQUEST_METHOD'])
        : 'GET';
}

function MENU_adminCheckToken()
{
    return function_exists('SEC_checkToken') && SEC_checkToken();
}

function MENU_adminCreateToken()
{
    return function_exists('SEC_createToken') ? SEC_createToken() : '';
}

function MENU_adminTokenName()
{
    return defined('CSRF_TOKEN') ? CSRF_TOKEN : 'token';
}

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

function MENU_adminId($value)
{
    $value = (int) $value;
    return $value > 0 ? $value : 0;
}

function MENU_adminDbEscape($value)
{
    $value = (string) $value;
    if (function_exists('DB_escapeString')) {
        return DB_escapeString($value);
    }

    return addslashes($value);
}

function MENU_adminIsControllerRequest()
{
    $script = isset($_SERVER['PHP_SELF']) ? (string) $_SERVER['PHP_SELF'] : '';
    $script = str_replace('\\', '/', strtolower($script));

    return substr($script, -29) === '/admin/plugins/menu/index.php';
}

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

function MENU_adminRejectInvalidToken()
{
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
    }

    $title = 'Authentication Required';
    $message = 'The security token for this operation has expired or is invalid. Please reload the administration page and try again.';

    if (function_exists('COM_output') && function_exists('COM_createHTMLDocument')) {
        if (function_exists('COM_showMessageText')) {
            $content = COM_showMessageText($message, $title);
        } else {
            $content = '<h2>' . $title . '</h2><p>' . $message . '</p>';
        }

        $document = COM_createHTMLDocument($content);
        COM_output($document);
    } else {
        echo $title . ': ' . $message;
    }

    exit;
}

/**
 * Reject old mutation URLs that still try to execute through GET.
 *
 * The administration UI rewrites the legacy move/delete links into POST forms.
 * Rejecting GET server-side prevents bookmarked, crawled or manually replayed
 * mutation URLs from changing state.
 *
 * @return void
 */
function MENU_adminRejectWrongMethod()
{
    if (!headers_sent()) {
        header('HTTP/1.1 405 Method Not Allowed');
        header('Allow: POST');
    }

    $title = 'Method Not Allowed';
    $message = 'This Menu administration operation must be submitted using POST. Please reload the administration page and try again.';

    if (function_exists('COM_output') && function_exists('COM_createHTMLDocument')) {
        if (function_exists('COM_showMessageText')) {
            $content = COM_showMessageText($message, $title);
        } else {
            $content = '<h2>' . $title . '</h2><p>' . $message . '</p>';
        }
        $document = COM_createHTMLDocument($content);
        COM_output($document);
    } else {
        echo $title . ': ' . $message;
    }

    exit;
}

function MENU_adminEnforceCsrf()
{
    if (!MENU_adminIsControllerRequest()) {
        return;
    }

    $mode = MENU_adminCurrentMode();
    if (!MENU_adminRequestMutates($mode, $_POST)) {
        return;
    }

    if (MENU_adminModeRequiresPost($mode) && MENU_adminRequestMethod() !== 'POST') {
        MENU_adminRejectWrongMethod();
    }

    if (!MENU_adminCheckToken()) {
        MENU_adminRejectInvalidToken();
    }
}

/**
 * Register a client-side compatibility bridge for the legacy Menu admin UI.
 *
 * Existing POST forms and AJAX requests receive the native Geeklog token. The
 * historical move/delete/deletemenu anchors are converted into POST submissions
 * in-place. Their GET URLs are therefore no longer used for state changes, and
 * the server rejects direct GET attempts for those modes.
 *
 * @return void
 */
function MENU_adminRegisterTokenBridge()
{
    global $_SCRIPTS, $LANG_MENU01;

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
    $confirmMessage = isset($LANG_MENU01['confirm_delete'])
        ? (string) $LANG_MENU01['confirm_delete']
        : 'Are you sure you want to delete this item?';
    $confirmJson = json_encode($confirmMessage);

    if ($nameJson === false || $tokenJson === false || $confirmJson === false) {
        return;
    }

    if (method_exists($_SCRIPTS, 'setJavaScriptLibrary')) {
        $_SCRIPTS->setJavaScriptLibrary('jquery');
    }

    $js = "jQuery(function($) {\n"
        . "    var tokenName = " . $nameJson . ";\n"
        . "    var tokenValue = " . $tokenJson . ";\n"
        . "    var confirmDelete = " . $confirmJson . ";\n"
        . "    var menuAdmin = '/plugins/menu/index.php';\n"
        . "    function decodePart(value) {\n"
        . "        value = String(value || '').replace(/\\+/g, ' ');\n"
        . "        try { return decodeURIComponent(value); } catch (e) { return value; }\n"
        . "    }\n"
        . "    function queryData(href) {\n"
        . "        var data = {};\n"
        . "        var query = String(href || '').split('?')[1] || '';\n"
        . "        query = query.split('#')[0];\n"
        . "        if (!query) { return data; }\n"
        . "        $.each(query.split('&'), function(index, pair) {\n"
        . "            if (!pair) { return; }\n"
        . "            var bits = pair.split('=');\n"
        . "            var key = decodePart(bits.shift());\n"
        . "            var value = decodePart(bits.join('='));\n"
        . "            if (key) { data[key] = value; }\n"
        . "        });\n"
        . "        return data;\n"
        . "    }\n"
        . "    function submitMutation(href, data) {\n"
        . "        var action = String(href || '').split('?')[0] || window.location.pathname;\n"
        . "        var form = $('<form>', {method: 'post', action: action, style: 'display:none'});\n"
        . "        $.each(data, function(key, value) {\n"
        . "            $('<input>', {type: 'hidden', name: key, value: value}).appendTo(form);\n"
        . "        });\n"
        . "        $('<input>', {type: 'hidden', name: tokenName, value: tokenValue}).appendTo(form);\n"
        . "        form.appendTo('body');\n"
        . "        form.get(0).submit();\n"
        . "    }\n"
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
        . "        var data = queryData(href);\n"
        . "        if (!data.mode || !/^(?:move|delete|deletemenu)$/.test(data.mode)) { return; }\n"
        . "        link.removeAttr('onclick');\n"
        . "        link.off('click.menuMutationPost').on('click.menuMutationPost', function(event) {\n"
        . "            event.preventDefault();\n"
        . "            if ((data.mode === 'delete' || data.mode === 'deletemenu') && !window.confirm(confirmDelete)) { return; }\n"
        . "            submitMutation(href, data);\n"
        . "        });\n"
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

if (MENU_adminIsControllerRequest()) {
    MENU_adminEnforceCsrf();
    MENU_adminRegisterTokenBridge();
}
