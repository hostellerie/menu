<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | create_element.php                                                        |
// |                                                                           |
// | Dedicated persistence endpoint for new menu elements.                     |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';
require_once $_CONF['path'] . 'plugins/menu/admin_element_validation.php';

if (!SEC_hasRights('menu.admin')) {
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
    }
    exit;
}

if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
    if (!headers_sent()) {
        header('HTTP/1.1 405 Method Not Allowed');
        header('Allow: POST');
    }
    exit;
}

if (!function_exists('SEC_checkToken') || !SEC_checkToken()) {
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
    }
    exit;
}

$validationError = MENU_adminElementMutationError('save', $_POST);
if ($validationError !== '') {
    if (!headers_sent()) {
        header('HTTP/1.1 400 Bad Request');
    }
    echo MENU_escapeHTML($validationError);
    exit;
}

$menuId = isset($_POST['menuid']) ? (int) $_POST['menuid'] : 0;
$pid = isset($_POST['pid']) ? (int) $_POST['pid'] : 0;
$afterElementId = isset($_POST['menuorder']) ? (int) $_POST['menuorder'] : 0;
$label = isset($_POST['menulabel'])
    ? trim(strip_tags(COM_checkWords((string) $_POST['menulabel']))) : '';
$type = isset($_POST['menutype']) ? (int) $_POST['menutype'] : 0;
$target = isset($_POST['urltarget']) ? (string) $_POST['urltarget'] : '';
$active = isset($_POST['menuactive']) ? 1 : 0;
$url = isset($_POST['menuurl']) ? trim((string) $_POST['menuurl']) : '';
$groupId = isset($_POST['group']) ? (int) $_POST['group'] : 0;

if ($label === '' || strlen($label) > 255 || $groupId <= 0) {
    if (!headers_sent()) {
        header('HTTP/1.1 400 Bad Request');
    }
    exit;
}

$subtype = '';
switch ($type) {
    case 2:
        $subtype = isset($_POST['glfunction']) ? (string) $_POST['glfunction'] : '';
        break;
    case 3:
        $subtype = isset($_POST['gltype']) ? (string) ((int) $_POST['gltype']) : '';
        break;
    case 4:
        $subtype = isset($_POST['pluginname']) ? (string) $_POST['pluginname'] : '';
        break;
    case 5:
        $subtype = isset($_POST['spname']) ? (string) $_POST['spname'] : '';
        break;
    case 6:
        $subtype = $url;
        if ($subtype !== ''
            && strpos($subtype, 'http') !== 0
            && strpos($subtype, '%site') === false
            && $subtype[0] !== '#') {
            $subtype = 'http://' . $subtype;
        }
        break;
    case 7:
        $subtype = isset($_POST['phpfunction']) ? (string) $_POST['phpfunction'] : '';
        break;
    case 9:
        $subtype = isset($_POST['topicname']) ? (string) $_POST['topicname'] : '';
        break;
}

if ($url !== ''
    && strpos($url, 'http') !== 0
    && strpos($url, '%site') === false
    && $url[0] !== '#') {
    $url = 'http://' . $url;
}

if ($afterElementId === 0) {
    $elementOrder = 1;
} else {
    $afterOrder = DB_getItem(
        $_TABLES['menu_elements'],
        'element_order',
        'id=' . $afterElementId . ' AND menu_id=' . $menuId . ' AND pid=' . $pid
    );
    $elementOrder = (int) $afterOrder + 1;
}

$labelSql = MENU_dbEscape($label);
$subtypeSql = MENU_dbEscape($subtype);
$urlSql = MENU_dbEscape($url);
$targetSql = MENU_dbEscape($target);

DB_save(
    $_TABLES['menu_elements'],
    'pid,menu_id,element_label,element_type,element_subtype,element_order,element_active,element_url,element_target,group_id',
    $pid . ',' . $menuId . ",'" . $labelSql . "'," . $type . ",'" . $subtypeSql . "',"
    . $elementOrder . ',' . $active . ",'" . $urlSql . "','" . $targetSql . "'," . $groupId
);

$newElementId = (int) DB_insertId();
if ($newElementId <= 0) {
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
    }
    exit;
}

// Normalize sibling order after insertion. The composite database index keeps
// this query efficient and the menu_id condition prevents cross-menu updates.
$orderResult = DB_query(
    "SELECT id FROM {$_TABLES['menu_elements']} WHERE menu_id=" . $menuId
    . ' AND pid=' . $pid . ' ORDER BY element_order ASC, id ASC'
);
$order = 10;
while ($row = DB_fetchArray($orderResult)) {
    $id = (int) $row['id'];
    DB_query(
        "UPDATE {$_TABLES['menu_elements']} SET element_order=" . $order
        . ' WHERE menu_id=' . $menuId . ' AND id=' . $id
    );
    $order += 10;
}

MENU_CACHE_remove_instance('menu');
MENU_CACHE_remove_instance('css');
DB_save($_TABLES['vars'], 'name,value', "'cacheid'," . rand());
MENU_initMENU(true);

COM_redirect(
    $_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&menu=' . $menuId
);
