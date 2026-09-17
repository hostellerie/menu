<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | tree-action.php                                                           |
// |                                                                           |
// | AJAX endpoint for menu tree ordering only.                                |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';
require_once $_CONF['path'] . 'plugins/menu/admin_menu_mutations.php';
require_once $_CONF['path'] . 'plugins/menu/admin_element_validation.php';

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

if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
    MENU_treeActionResponse('405 Method Not Allowed', array(
        'ok' => false,
        'message' => 'POST required.',
    ));
}

if (!SEC_hasRights('menu.admin')) {
    MENU_treeActionResponse('403 Forbidden', array(
        'ok' => false,
        'message' => 'Access denied.',
    ));
}

$requestedWith = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    ? strtolower(trim((string) $_SERVER['HTTP_X_REQUESTED_WITH']))
    : '';
if ($requestedWith !== 'xmlhttprequest') {
    MENU_treeActionResponse('403 Forbidden', array(
        'ok' => false,
        'message' => 'AJAX request required.',
    ));
}

if (!empty($_SERVER['HTTP_ORIGIN'])) {
    $origin = parse_url((string) $_SERVER['HTTP_ORIGIN']);
    $site = parse_url((string) $_CONF['site_url']);
    $originHost = isset($origin['host']) ? strtolower($origin['host']) : '';
    $siteHost = isset($site['host']) ? strtolower($site['host']) : '';
    $originScheme = isset($origin['scheme']) ? strtolower($origin['scheme']) : '';
    $siteScheme = isset($site['scheme']) ? strtolower($site['scheme']) : '';

    if ($originHost === '' || $siteHost === ''
        || $originHost !== $siteHost
        || ($originScheme !== '' && $siteScheme !== '' && $originScheme !== $siteScheme)) {
        MENU_treeActionResponse('403 Forbidden', array(
            'ok' => false,
            'message' => 'Cross-origin request rejected.',
        ));
    }
}

$menuId = (int) Geeklog\Input::fPost('menu_id');
$validationError = MENU_adminPostMutationError('', $_POST);
if ($validationError !== '') {
    MENU_treeActionResponse('400 Bad Request', array(
        'ok' => false,
        'message' => $validationError,
    ));
}

MENU_saveElementOrder($menuId, Geeklog\Input::post('orders', ''));

MENU_treeActionResponse('200 OK', array(
    'ok' => true,
));
