<?php

define('VERSION', '2.1.1');

$_TABLES = array(
    'menu' => 'gl_menu',
    'menu_config' => 'gl_menu_config',
    'menu_elements' => 'gl_menu_elements',
);
$_queryCount = 0;
$_results = array();

class mbElement
{
    public $id = 0;
    public $menu_id = 0;
    public $label = '';
    public $type = 0;
    public $pid = 0;
    public $order = 0;
    public $url = '';
    public $owner_id = 0;
    public $group_id = 0;
    public $access = 3;
    public $children = array();

    public function constructor($row, $admin, $root, $groups)
    {
        $this->id = (int) $row['id'];
        $this->menu_id = (int) $row['menu_id'];
        $this->pid = (int) $row['pid'];
        $this->order = (int) $row['element_order'];
        $this->access = isset($row['access']) ? (int) $row['access'] : 3;
    }

    public function setChild($id)
    {
        $this->children[] = (int) $id;
    }
}

function DB_query($sql, $ignore = 0)
{
    global $_queryCount, $_results;
    $_queryCount++;

    if (strpos($sql, 'FROM gl_menu_config') !== false) {
        return array(
            array('menu_id' => 1, 'conf_name' => 'menu_alignment', 'conf_value' => '1'),
            array('menu_id' => 2, 'conf_name' => 'menu_alignment', 'conf_value' => '0'),
        );
    }
    if (strpos($sql, 'FROM gl_menu_elements') !== false) {
        return array(
            array('id' => 10, 'menu_id' => 1, 'pid' => 0, 'element_order' => 10),
            array('id' => 11, 'menu_id' => 1, 'pid' => 10, 'element_order' => 20),
            array('id' => 20, 'menu_id' => 2, 'pid' => 0, 'element_order' => 10),
        );
    }
    return array(
        array('id' => 1, 'menu_name' => 'navigation', 'menu_active' => 1, 'menu_type' => 1, 'group_id' => 2),
        array('id' => 2, 'menu_name' => 'footer', 'menu_active' => 1, 'menu_type' => 2, 'group_id' => 998),
    );
}

function DB_fetchArray(&$result)
{
    if (empty($result)) {
        return false;
    }
    return array_shift($result);
}

function COM_isAnonUser()
{
    return true;
}

require_once dirname(__DIR__) . '/runtime_loader.php';

$menus = MENU_loadRuntimeMenus(false, false, array(2));

if ($_queryCount !== 3) {
    fwrite(STDERR, 'Expected exactly 3 runtime loader queries, got ' . $_queryCount . PHP_EOL);
    exit(1);
}
if (!isset($menus[1]['config']['menu_alignment']) || $menus[1]['config']['menu_alignment'] !== '1') {
    fwrite(STDERR, "Menu configuration was not batched correctly.\n");
    exit(1);
}
if (!isset($menus[1]['elements'][10]) || !isset($menus[1]['elements'][11])) {
    fwrite(STDERR, "Menu elements were not batched correctly.\n");
    exit(1);
}
if ($menus[1]['elements'][10]->children !== array(11)) {
    fwrite(STDERR, "Parent/child hierarchy was not rebuilt correctly.\n");
    exit(1);
}
if ($menus[1]['menu_perm'] !== 3 || $menus[2]['menu_perm'] !== 3) {
    fwrite(STDERR, "Menu permissions were not preserved.\n");
    exit(1);
}

echo "Menu runtime loader tests passed\n";
