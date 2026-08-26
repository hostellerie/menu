<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | clone.php                                                                 |
// |                                                                           |
// | Dedicated menu clone persistence endpoint.                                |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';
require_once $_CONF['path'] . 'plugins/menu/cache_runtime.php';

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

if (isset($_POST['cancel'])) {
    COM_redirect($_CONF['site_admin_url'] . '/plugins/menu/index.php');
}

if (!function_exists('SEC_checkToken') || !SEC_checkToken()) {
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
    }
    exit;
}

$sourceMenuId = isset($_POST['menu']) ? (int) $_POST['menu'] : 0;
$menuName = isset($_POST['menuname']) ? trim((string) $_POST['menuname']) : '';

if ($sourceMenuId <= 0 || $menuName === '' || strlen($menuName) > 64) {
    if (!headers_sent()) {
        header('HTTP/1.1 400 Bad Request');
    }
    exit;
}

$sourceResult = DB_query(
    "SELECT menu_type, menu_active, group_id FROM {$_TABLES['menu']} WHERE id=" . $sourceMenuId
);
if (DB_numRows($sourceResult) !== 1) {
    if (!headers_sent()) {
        header('HTTP/1.1 400 Bad Request');
    }
    exit;
}

$sourceMenu = DB_fetchArray($sourceResult);
$menuNameSql = MENU_dbEscape($menuName);
$menuType = (int) $sourceMenu['menu_type'];
$menuActive = (int) $sourceMenu['menu_active'];
$groupId = (int) $sourceMenu['group_id'];

DB_save(
    $_TABLES['menu'],
    'menu_name,menu_type,menu_active,group_id',
    "'" . $menuNameSql . "'," . $menuType . ',' . $menuActive . ',' . $groupId
);
$newMenuId = (int) DB_insertId();
if ($newMenuId <= 0) {
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
    }
    exit;
}

$configResult = DB_query(
    "SELECT conf_name, conf_value FROM {$_TABLES['menu_config']} WHERE menu_id=" . $sourceMenuId
);
while ($config = DB_fetchArray($configResult)) {
    $confName = MENU_dbEscape($config['conf_name']);
    $confValue = MENU_dbEscape($config['conf_value']);
    DB_save(
        $_TABLES['menu_config'],
        'menu_id,conf_name,conf_value',
        $newMenuId . ",'" . $confName . "','" . $confValue . "'"
    );
}

$sourceElements = array();
$elementResult = DB_query(
    "SELECT id,pid,element_label,element_type,element_subtype,element_order,"
    . "element_active,element_url,element_target,group_id "
    . "FROM {$_TABLES['menu_elements']} WHERE menu_id=" . $sourceMenuId . " ORDER BY id ASC"
);
while ($element = DB_fetchArray($elementResult)) {
    $sourceElements[] = $element;
}

$idMap = array();
foreach ($sourceElements as $element) {
    $oldId = (int) $element['id'];
    if ($oldId <= 0) {
        continue;
    }

    $label = MENU_dbEscape($element['element_label']);
    $type = (int) $element['element_type'];
    $subtype = MENU_dbEscape($element['element_subtype']);
    $order = (int) $element['element_order'];
    $active = (int) $element['element_active'];
    $url = MENU_dbEscape($element['element_url']);
    $target = MENU_dbEscape($element['element_target']);
    $elementGroupId = (int) $element['group_id'];

    // Parent IDs are remapped in a second pass after every new ID is known.
    DB_save(
        $_TABLES['menu_elements'],
        'pid,menu_id,element_label,element_type,element_subtype,element_order,element_active,element_url,element_target,group_id',
        '0,' . $newMenuId . ",'" . $label . "'," . $type . ",'" . $subtype . "',"
        . $order . ',' . $active . ",'" . $url . "','" . $target . "'," . $elementGroupId
    );

    $newId = (int) DB_insertId();
    if ($newId <= 0) {
        DB_query("DELETE FROM {$_TABLES['menu_elements']} WHERE menu_id=" . $newMenuId);
        DB_query("DELETE FROM {$_TABLES['menu_config']} WHERE menu_id=" . $newMenuId);
        DB_query("DELETE FROM {$_TABLES['menu']} WHERE id=" . $newMenuId);
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        exit;
    }

    $idMap[$oldId] = $newId;
}

foreach ($sourceElements as $element) {
    $oldId = (int) $element['id'];
    if (!isset($idMap[$oldId])) {
        continue;
    }

    $oldPid = (int) $element['pid'];
    $newPid = ($oldPid > 0 && isset($idMap[$oldPid])) ? (int) $idMap[$oldPid] : 0;
    $newId = (int) $idMap[$oldId];

    DB_query(
        "UPDATE {$_TABLES['menu_elements']} SET pid=" . $newPid
        . " WHERE menu_id=" . $newMenuId . " AND id=" . $newId
    );
}

MENU_invalidateRuntimeCache(true);

COM_redirect(
    $_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&menu=' . $newMenuId
);
