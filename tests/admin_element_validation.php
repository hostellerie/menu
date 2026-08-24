<?php

// Server-side Menu element mutation validation tests. PHP 5.6+.
define('VERSION', '2.1.1');

$_TABLES = array(
    'menu' => 'gl_menu',
    'menu_elements' => 'gl_menu_elements',
    'staticpage' => 'gl_staticpage',
    'topics' => 'gl_topics',
);
$_PLUGINS = array('staticpages');

$menuValidationMenus = array(
    1 => 1, // cascading
    2 => 2, // simple
);
$menuValidationElements = array(
    1 => array('menu_id' => 1, 'pid' => 0, 'element_type' => 2, 'element_subtype' => '0'),
    2 => array('menu_id' => 1, 'pid' => 0, 'element_type' => 1, 'element_subtype' => ''),
    3 => array('menu_id' => 1, 'pid' => 2, 'element_type' => 6, 'element_subtype' => 'https://example.test/child'),
    4 => array('menu_id' => 1, 'pid' => 0, 'element_type' => 4, 'element_subtype' => 'missingplugin'),
    5 => array('menu_id' => 1, 'pid' => 0, 'element_type' => 5, 'element_subtype' => 'missing-page'),
    6 => array('menu_id' => 1, 'pid' => 0, 'element_type' => 9, 'element_subtype' => 'missing-topic'),
    20 => array('menu_id' => 2, 'pid' => 0, 'element_type' => 2, 'element_subtype' => '0'),
);
$menuValidationStaticPages = array('about' => true);
$menuValidationTopics = array('news' => true);

function DB_getItem($table, $field, $where)
{
    global $menuValidationMenus, $menuValidationElements,
           $menuValidationStaticPages, $menuValidationTopics;

    if ($table === 'gl_menu' && $field === 'menu_type') {
        if (preg_match('/id=(\d+)/', $where, $matches)) {
            $id = (int) $matches[1];
            return isset($menuValidationMenus[$id]) ? $menuValidationMenus[$id] : '';
        }
    }

    if ($table === 'gl_menu_elements') {
        if (!preg_match('/id=(\d+)/', $where, $matches)) {
            return '';
        }
        $id = (int) $matches[1];
        if (!isset($menuValidationElements[$id])) {
            return '';
        }
        $element = $menuValidationElements[$id];
        if (preg_match('/menu_id=(\d+)/', $where, $menuMatches)
            && (int) $menuMatches[1] !== (int) $element['menu_id']) {
            return '';
        }
        return isset($element[$field]) ? $element[$field] : '';
    }

    if ($table === 'gl_staticpage' && $field === 'sp_id') {
        if (preg_match("/sp_id='([^']*)'/", $where, $matches)) {
            return isset($menuValidationStaticPages[$matches[1]]) ? $matches[1] : '';
        }
    }

    if ($table === 'gl_topics' && $field === 'tid') {
        if (preg_match("/tid='([^']*)'/", $where, $matches)) {
            return isset($menuValidationTopics[$matches[1]]) ? $matches[1] : '';
        }
    }

    return '';
}

function DB_query($sql)
{
    global $menuValidationElements;

    $rows = array();
    if (preg_match('/WHERE id=(\d+) AND menu_id=(\d+)/', $sql, $matches)) {
        $id = (int) $matches[1];
        $menuId = (int) $matches[2];
        if (isset($menuValidationElements[$id])
            && (int) $menuValidationElements[$id]['menu_id'] === $menuId) {
            $rows[] = $menuValidationElements[$id];
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
    $row = $result['rows'][$result['offset']];
    $result['offset']++;
    return $row;
}

function DB_escapeString($value)
{
    return str_replace("'", "''", $value);
}

function MENU_PLG_getMenuItems()
{
    return array('calendar' => 'https://example.test/calendar/');
}

require_once dirname(__DIR__) . '/admin_element_validation.php';

function menu_validation_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

function menu_validation_base_create()
{
    return array(
        'menuid' => 1,
        'pid' => 0,
        'menuorder' => 0,
        'menutype' => 2,
        'glfunction' => 0,
        'urltarget' => '',
    );
}

function menu_validation_base_edit($id, $type)
{
    return array(
        'menu' => 1,
        'id' => $id,
        'pid' => 0,
        'menuorder' => 0,
        'menutype' => $type,
        'urltarget' => '',
    );
}

$valid = menu_validation_base_create();
menu_validation_assert(MENU_adminElementMutationError('save', $valid) === '', 'valid Geeklog Action create must pass');

$invalid = $valid;
$invalid['menuid'] = 99;
menu_validation_assert(MENU_adminElementMutationError('save', $invalid) !== '', 'unknown menu must be rejected');

$invalid = $valid;
$invalid['pid'] = 1;
menu_validation_assert(MENU_adminElementMutationError('save', $invalid) !== '', 'non-submenu parent must be rejected');

$validChild = $valid;
$validChild['pid'] = 2;
menu_validation_assert(MENU_adminElementMutationError('save', $validChild) === '', 'submenu parent in same menu must be accepted');

$invalid = $validChild;
$invalid['menuorder'] = 1;
menu_validation_assert(MENU_adminElementMutationError('save', $invalid) !== '', 'Display After from another parent must be rejected');

$validAfter = $validChild;
$validAfter['menuorder'] = 3;
menu_validation_assert(MENU_adminElementMutationError('save', $validAfter) === '', 'Display After with same parent must pass');

$invalid = $valid;
$invalid['menutype'] = 12;
menu_validation_assert(MENU_adminElementMutationError('save', $invalid) !== '', 'unknown element type must be rejected');

$invalid = $valid;
$invalid['urltarget'] = '_self" onclick="bad()';
menu_validation_assert(MENU_adminElementMutationError('save', $invalid) !== '', 'unsafe URL target must be rejected');

$invalid = $valid;
$invalid['glfunction'] = 99;
menu_validation_assert(MENU_adminElementMutationError('save', $invalid) !== '', 'unknown Geeklog Action must be rejected');

$core = $valid;
$core['menutype'] = 3;
$core['gltype'] = 6;
menu_validation_assert(MENU_adminElementMutationError('save', $core) === '', 'valid Geeklog Menu subtype must pass');
$core['gltype'] = 7;
menu_validation_assert(MENU_adminElementMutationError('save', $core) !== '', 'invalid Geeklog Menu subtype must fail');

$plugin = $valid;
$plugin['menutype'] = 4;
$plugin['pluginname'] = 'calendar';
menu_validation_assert(MENU_adminElementMutationError('save', $plugin) === '', 'available plugin destination must pass');
$plugin['pluginname'] = 'missingplugin';
menu_validation_assert(MENU_adminElementMutationError('save', $plugin) !== '', 'new unavailable plugin destination must fail');

$editMissingPlugin = menu_validation_base_edit(4, 4);
$editMissingPlugin['pluginname'] = 'missingplugin';
menu_validation_assert(MENU_adminElementMutationError('saveedit', $editMissingPlugin) === '', 'stored unavailable plugin must remain preservable');

$static = $valid;
$static['menutype'] = 5;
$static['spname'] = 'about';
menu_validation_assert(MENU_adminElementMutationError('save', $static) === '', 'existing Static Page must pass');
$static['spname'] = 'missing-page';
menu_validation_assert(MENU_adminElementMutationError('save', $static) !== '', 'new missing Static Page must fail');

$editMissingStatic = menu_validation_base_edit(5, 5);
$editMissingStatic['spname'] = 'missing-page';
menu_validation_assert(MENU_adminElementMutationError('saveedit', $editMissingStatic) === '', 'stored missing Static Page must remain preservable');

$url = $valid;
$url['menutype'] = 6;
$url['menuurl'] = '';
menu_validation_assert(MENU_adminElementMutationError('save', $url) !== '', 'empty External URL must fail');
$url['menuurl'] = 'https://example.test/';
menu_validation_assert(MENU_adminElementMutationError('save', $url) === '', 'non-empty External URL must pass');

$topic = $valid;
$topic['menutype'] = 9;
$topic['topicname'] = 'news';
menu_validation_assert(MENU_adminElementMutationError('save', $topic) === '', 'existing Topic must pass');
$topic['topicname'] = 'missing-topic';
menu_validation_assert(MENU_adminElementMutationError('save', $topic) !== '', 'new missing Topic must fail');

$editMissingTopic = menu_validation_base_edit(6, 9);
$editMissingTopic['topicname'] = 'missing-topic';
menu_validation_assert(MENU_adminElementMutationError('saveedit', $editMissingTopic) === '', 'stored missing Topic must remain preservable');

$wrongMenu = array(
    'menu' => 1,
    'id' => 20,
    'pid' => 0,
    'menuorder' => 0,
    'menutype' => 2,
    'glfunction' => 0,
    'urltarget' => '',
);
menu_validation_assert(MENU_adminElementMutationError('saveedit', $wrongMenu) !== '', 'element from another menu must be rejected');

$simpleCore = array(
    'menuid' => 2,
    'pid' => 0,
    'menuorder' => 0,
    'menutype' => 3,
    'gltype' => 1,
    'urltarget' => '',
);
menu_validation_assert(MENU_adminElementMutationError('save', $simpleCore) !== '', 'Geeklog Core must be rejected for simple menus');

echo "Admin element validation tests passed" . PHP_EOL;
