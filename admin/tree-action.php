<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | tree-action.php                                                           |
// |                                                                           |
// | AJAX endpoint for menu tree ordering and activation changes.              |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';
require_once $_CONF['path'] . 'plugins/menu/admin_menu_mutations.php';
require_once $_CONF['path'] . 'plugins/menu/admin_security.php';

function MENU_treeActionResponse($status, $payload)
{
    if (!headers_sent()) {
        header('HTTP/1.1 ' . $status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    echo json_encode($payload);
    exit;
}

if (MENU_adminRequestMethod() !== 'POST') {
    MENU_treeActionResponse('405 Method Not Allowed', array(
        'ok' => false,
        'reload' => false,
        'message' => 'POST required.',
    ));
}

if (!MENU_adminHasRights()) {
    MENU_treeActionResponse('403 Forbidden', array(
        'ok' => false,
        'reload' => false,
        'message' => 'Access denied.',
    ));
}

if (!MENU_adminCheckToken()) {
    MENU_treeActionResponse('403 Forbidden', array(
        'ok' => false,
        'reload' => true,
        'message' => 'Security token expired.',
    ));
}

$action = isset($_POST['tree_action']) ? strtolower(trim((string) $_POST['tree_action'])) : '';

if ($action === 'order') {
    $menuId = (int) Geeklog\Input::fPost('menu_id');
    $validationError = MENU_adminPostMutationError('', $_POST);
    if ($validationError !== '') {
        MENU_treeActionResponse('400 Bad Request', array(
            'ok' => false,
            'reload' => false,
            'message' => $validationError,
        ));
    }

    MENU_saveElementOrder($menuId, Geeklog\Input::post('orders', ''));
} elseif ($action === 'activate') {
    $validationError = MENU_adminMutationReferenceError('activate', $_POST);
    if ($validationError !== '') {
        MENU_treeActionResponse('400 Bad Request', array(
            'ok' => false,
            'reload' => false,
            'message' => $validationError,
        ));
    }

    MENU_changeActiveStatusElement();
} else {
    MENU_treeActionResponse('400 Bad Request', array(
        'ok' => false,
        'reload' => false,
        'message' => 'Unsupported menu tree action.',
    ));
}

$newToken = MENU_adminCreateToken();
if ($newToken === '') {
    MENU_treeActionResponse('500 Internal Server Error', array(
        'ok' => false,
        'reload' => true,
        'message' => 'Unable to refresh security token.',
    ));
}

MENU_treeActionResponse('200 OK', array(
    'ok' => true,
    'tokenName' => MENU_adminTokenName(),
    'tokenValue' => $newToken,
));
