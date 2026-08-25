<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | getorder.php                                                              |
// |                                                                           |
// | Return the Display After selector for a selected parent element.          |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

if (!SEC_hasRights('menu.admin')) {
    if (!headers_sent()) {
        header('HTTP/1.1 403 Forbidden');
    }
    exit;
}

$menuId = isset($_GET['menuid']) ? (int) $_GET['menuid'] : 0;
$parentId = isset($_GET['optionid']) ? (int) $_GET['optionid'] : 0;

if ($menuId <= 0) {
    if (!headers_sent()) {
        header('HTTP/1.1 400 Bad Request');
    }
    exit;
}

if ((int) DB_count($_TABLES['menu'], 'id', $menuId) !== 1) {
    if (!headers_sent()) {
        header('HTTP/1.1 400 Bad Request');
    }
    exit;
}

if ($parentId > 0) {
    $parentResult = DB_query(
        "SELECT id FROM {$_TABLES['menu_elements']} WHERE id=" . $parentId
        . ' AND menu_id=' . $menuId . ' AND element_type=1'
    );
    if (DB_numRows($parentResult) !== 1) {
        if (!headers_sent()) {
            header('HTTP/1.1 400 Bad Request');
        }
        exit;
    }
}

$rows = array();
$result = DB_query(
    "SELECT id,element_label FROM {$_TABLES['menu_elements']} WHERE menu_id=" . $menuId
    . ' AND pid=' . $parentId . ' ORDER BY element_order ASC, id ASC'
);
while ($row = DB_fetchArray($result)) {
    $rows[] = $row;
}

$label = isset($LANG_MENU01['display_after']) ? $LANG_MENU01['display_after'] : 'Display After';
$first = isset($LANG_MENU01['first_position']) ? $LANG_MENU01['first_position'] : 'First position';

$output = '<p><label for="menuorder">' . MENU_escapeHTML($label) . ':</label>' . LB;
$output .= '<select id="menuorder" name="menuorder">' . LB;
$output .= '<option value="0">' . MENU_escapeHTML($first) . '</option>' . LB;

$lastIndex = count($rows) - 1;
foreach ($rows as $index => $row) {
    $output .= '<option value="' . (int) $row['id'] . '"';
    if ($index === $lastIndex) {
        $output .= ' selected="selected"';
    }
    $output .= '>' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
}
$output .= '</select></p>' . LB;

echo $output;
