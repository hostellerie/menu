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

if ($menuId <= 0 || !isset($Menus[$menuId])) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

$currentType = null;
$locked = false;
if ($mid > 0 && isset($Menus[$menuId]['elements'][$mid])) {
    $currentType = (int) $Menus[$menuId]['elements'][$mid]->type;
    $locked = ($currentType === 1);
}

$hasStaticPages = isset($_PLUGINS) && in_array('staticpages', $_PLUGINS);
$types = MENU_getAllowedElementTypes(
    $LANG_MENU_TYPES,
    (int) $Menus[$menuId]['menu_type'],
    $hasStaticPages,
    $currentType
);

$response = array(
    'currentType' => $currentType,
    'defaultType' => MENU_defaultElementType($types),
    'locked' => $locked,
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
