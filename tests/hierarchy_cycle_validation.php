<?php

// Runtime hierarchy-cycle validation. PHP 5.6+.
define('VERSION', '2.1.1');

$_TABLES = array(
    'menu' => 'gl_menu',
    'menu_elements' => 'gl_menu_elements',
);
$_PLUGINS = array();

$cycleMenus = array(
    1 => array('menu_type' => 1, 'menu_name' => 'navigation'),
);
$cycleElements = array(
    10 => array('id' => 10, 'menu_id' => 1, 'pid' => 0,  'element_type' => 1, 'element_subtype' => ''),
    11 => array('id' => 11, 'menu_id' => 1, 'pid' => 10, 'element_type' => 1, 'element_subtype' => ''),
    12 => array('id' => 12, 'menu_id' => 1, 'pid' => 11, 'element_type' => 1, 'element_subtype' => ''),
    20 => array('id' => 20, 'menu_id' => 1, 'pid' => 0,  'element_type' => 1, 'element_subtype' => ''),
);

function DB_getItem($table, $field, $where)
{
    global $cycleMenus, $cycleElements;

    if ($table === 'gl_menu' && preg_match('/id=(\d+)/', $where, $m)) {
        $id = (int) $m[1];
        return isset($cycleMenus[$id][$field]) ? $cycleMenus[$id][$field] : '';
    }

    if ($table === 'gl_menu_elements' && preg_match('/id=(\d+)/', $where, $m)) {
        $id = (int) $m[1];
        if (!isset($cycleElements[$id])) {
            return '';
        }
        if (preg_match('/menu_id=(\d+)/', $where, $mm)
            && (int) $mm[1] !== (int) $cycleElements[$id]['menu_id']) {
            return '';
        }
        return isset($cycleElements[$id][$field]) ? $cycleElements[$id][$field] : '';
    }

    return '';
}

function DB_query($sql)
{
    global $cycleElements;

    $rows = array();
    if (preg_match('/SELECT id,pid FROM gl_menu_elements WHERE menu_id=(\d+)/', $sql, $m)) {
        $menuId = (int) $m[1];
        foreach ($cycleElements as $row) {
            if ((int) $row['menu_id'] === $menuId) {
                $rows[] = array('id' => $row['id'], 'pid' => $row['pid']);
            }
        }
    } elseif (preg_match('/WHERE id=(\d+) AND menu_id=(\d+)/', $sql, $m)) {
        $id = (int) $m[1];
        $menuId = (int) $m[2];
        if (isset($cycleElements[$id]) && (int) $cycleElements[$id]['menu_id'] === $menuId) {
            $rows[] = $cycleElements[$id];
        }
    }

    return array('rows' => $rows, 'offset' => 0);
}

function DB_numRows($result)
{
    return count($result['rows']);
}

function DB_fetchArray(&$result)
{
    if (!isset($result['rows'][$result['offset']])) {
        return false;
    }
    return $result['rows'][$result['offset']++];
}

function MENU_PLG_getMenuItems()
{
    return array();
}

require_once dirname(__DIR__) . '/admin_element_validation.php';

function cycle_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$descendants = MENU_adminDescendantIds(1, 10);
sort($descendants);
cycle_assert($descendants === array(11, 12), 'descendant traversal must include all nested children');

$base = array(
    'menu' => 1,
    'id' => 10,
    'pid' => 0,
    'menuorder' => 0,
    'menutype' => 1,
    'menuurl' => '',
    'urltarget' => '',
);

$self = $base;
$self['pid'] = 10;
cycle_assert(MENU_adminElementMutationError('saveedit', $self) !== '', 'self-parent must be rejected');

$child = $base;
$child['pid'] = 11;
cycle_assert(MENU_adminElementMutationError('saveedit', $child) !== '', 'direct child parent must be rejected');

$grandchild = $base;
$grandchild['pid'] = 12;
cycle_assert(MENU_adminElementMutationError('saveedit', $grandchild) !== '', 'nested descendant parent must be rejected');

$otherBranch = $base;
$otherBranch['pid'] = 20;
cycle_assert(MENU_adminElementMutationError('saveedit', $otherBranch) === '', 'unrelated submenu parent must remain valid');

echo "Hierarchy cycle validation tests passed\n";
