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

require_once __DIR__ . '/admin_element_validation.php';

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

function MENU_adminPostOnlyModes()
{
    return MENU_adminMutationModes();
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

function MENU_adminHasRights()
{
    return function_exists('SEC_hasRights') && SEC_hasRights('menu.admin');
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

function MENU_adminOutputError($status, $title, $message, $allowPost)
{
    if (!headers_sent()) {
        header('HTTP/1.1 ' . $status);
        if ($allowPost) {
            header('Allow: POST');
        }
    }

    if (function_exists('COM_output') && function_exists('COM_createHTMLDocument')) {
        if (function_exists('COM_showMessageText')) {
            $content = COM_showMessageText($message, $title);
        } else {
            $content = '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p>'
                . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $document = COM_createHTMLDocument($content);
        COM_output($document);
    } else {
        echo $title . ': ' . $message;
    }

    exit;
}

function MENU_adminRejectAccess()
{
    MENU_adminOutputError(
        '403 Forbidden',
        'Access Denied',
        'You do not have permission to modify Menu configuration.',
        false
    );
}

function MENU_adminRejectInvalidToken()
{
    MENU_adminOutputError(
        '403 Forbidden',
        'Authentication Required',
        'The security token for this operation has expired or is invalid. Please reload the administration page and try again.',
        false
    );
}

function MENU_adminRejectWrongMethod()
{
    MENU_adminOutputError(
        '405 Method Not Allowed',
        'Method Not Allowed',
        'This Menu administration operation must be submitted using POST. Please reload the administration page and try again.',
        true
    );
}

function MENU_adminRejectInvalidRequest($message)
{
    MENU_adminOutputError(
        '400 Bad Request',
        'Invalid Menu Request',
        (string) $message,
        false
    );
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

    // Authorization must precede method, token and mutation validation so an
    // unauthorized request cannot probe mutation behavior or token validity.
    if (!MENU_adminHasRights()) {
        MENU_adminRejectAccess();
    }

    if (MENU_adminModeRequiresPost($mode) && MENU_adminRequestMethod() !== 'POST') {
        MENU_adminRejectWrongMethod();
    }

    if (!MENU_adminCheckToken()) {
        MENU_adminRejectInvalidToken();
    }

    // Menu cloning was moved to admin/clone.php in 1.3.0 so the large legacy
    // controller can no longer run its MAX(id)+1 based clone implementation.
    if ($mode === 'saveclonemenu') {
        MENU_adminRejectInvalidRequest(
            'The legacy clone endpoint is no longer available. Reload the Menu administration page and try again.'
        );
    }

    $validationError = MENU_adminMutationReferenceError($mode, $_POST);
    if ($validationError === '') {
        $validationError = MENU_adminPostMutationError($mode, $_POST);
    }
    if ($validationError === '') {
        $validationError = MENU_adminElementMutationError($mode, $_POST);
    }
    if ($validationError !== '') {
        MENU_adminRejectInvalidRequest($validationError);
    }
}

