<?php

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

if (!SEC_hasRights('menu.admin')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

require_once $_CONF['path'] . 'plugins/menu/element_types.php';

$menuId = isset($_GET['menu']) ? (int) $_GET['menu'] : 0;
$mid = isset($_GET['mid']) ? (int) $_GET['mid'] : 0;

if ($menuId <= 0) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$menuType = DB_getItem($_TABLES['menu'], 'menu_type', 'id=' . $menuId);
if ($menuType === false || $menuType === null || $menuType === '') {
    header('HTTP/1.1 404 Not Found');
    exit;
}
$menuType = (int) $menuType;

$currentType = null;
$currentSubtype = '';
$locked = false;
if ($mid > 0) {
    $result = DB_query(
        'SELECT element_type, element_subtype FROM ' . $_TABLES['menu_elements']
        . ' WHERE id=' . $mid . ' AND menu_id=' . $menuId
    );

    if (DB_numRows($result) === 0) {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    $row = DB_fetchArray($result);
    $currentType = (int) $row['element_type'];
    $currentSubtype = (string) $row['element_subtype'];
    $locked = ($currentType === 1);
}

$hasStaticPages = isset($_PLUGINS) && in_array('staticpages', $_PLUGINS, true);
$types = MENU_getAllowedElementTypes(
    $LANG_MENU_TYPES,
    $menuType,
    $hasStaticPages,
    $currentType
);

$resource = array(
    'kind' => '',
    'available' => true,
    'value' => $currentSubtype,
);

if ($currentType === 4) {
    $resource['kind'] = 'plugin';
    $pluginMenus = MENU_PLG_getMenuItems();
    $resource['available'] = isset($pluginMenus[$currentSubtype]);
} elseif ($currentType === 5) {
    $resource['kind'] = 'static page';
    $resource['available'] = false;
    if ($hasStaticPages && isset($_TABLES['staticpage']) && $currentSubtype !== '') {
        $escaped = function_exists('DB_escapeString')
            ? DB_escapeString($currentSubtype)
            : addslashes($currentSubtype);
        $found = DB_getItem(
            $_TABLES['staticpage'],
            'sp_id',
            "sp_id='" . $escaped . "' AND draft_flag=0"
        );
        $resource['available'] = $found !== '' && $found !== null && $found !== false;
    }
} elseif ($currentType === 9) {
    $resource['kind'] = 'topic';
    $resource['available'] = false;
    if (isset($_TABLES['topics']) && $currentSubtype !== '') {
        $escaped = function_exists('DB_escapeString')
            ? DB_escapeString($currentSubtype)
            : addslashes($currentSubtype);
        $found = DB_getItem(
            $_TABLES['topics'],
            'tid',
            "tid='" . $escaped . "'"
        );
        $resource['available'] = $found !== '' && $found !== null && $found !== false;
    }
}

$response = array(
    'currentType' => $currentType,
    'currentSubtype' => $currentSubtype,
    'defaultType' => MENU_defaultElementType($types),
    'locked' => $locked,
    'resource' => $resource,
    'types' => array()
);

foreach ($types as $typeId => $typeLabel) {
    $response['types'][] = array(
        'id' => (int) $typeId,
        'label' => $typeLabel
    );
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
