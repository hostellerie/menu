<?php

// Resolved tree regression test. Compatible with PHP 5.6+.
define('VERSION', '2.1.1');

define('LB', "\n");

$_CONF = array(
    'site_url' => 'https://example.test',
    'site_admin_url' => 'https://example.test/admin',
    'loginrequired' => 0,
    'submitloginrequired' => 0,
    'directoryloginrequired' => 0,
    'profileloginrequired' => 0,
    'searchloginrequired' => 0,
);
$_GROUPS = array(2 => 'Logged-in Users');
$_REQUEST = array();
$_PLUGINS = array('staticpages');
$_TABLES = array();
$_USER = array('uid' => 2);
$Menus = array();

function COM_getLanguageId() { return ''; }
function COM_applyFilter($value) { return $value; }
function COM_isAnonUser() { return false; }
function COM_buildUrl($url) { return $url; }
function COM_buildURL($url) { return $url; }
function COM_getCurrentURL() { return 'https://example.test/'; }
function SEC_hasRights($rights) { return $rights === 'stats.view'; }
function SEC_inGroup($group) { return $group === 2 || $group === 'Logged-in Users'; }
function MENU_PLG_getMenuItems() { return array('demo' => 'https://example.test/demo/'); }
function PLG_getMenuItems() { return array('Demo' => 'https://example.test/demo/'); }
function PLG_getUserOptions() { return array(); }

require_once dirname(__DIR__) . '/resolved_tree.php';

function menu_test_fail($message)
{
    fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
    exit(1);
}

function menu_test_assert($condition, $message)
{
    if (!$condition) {
        menu_test_fail($message);
    }
}

class MenuResolvedTestElement
{
    public $id;
    public $pid;
    public $menu_id = 1;
    public $label;
    public $type;
    public $subtype;
    public $order;
    public $active = 1;
    public $url = '';
    public $target = '';
    public $group_id = 2;
    public $access = 3;
    public $children = array();

    public function __construct($id, $pid, $label, $type, $subtype, $order, $url = '')
    {
        $this->id = $id;
        $this->pid = $pid;
        $this->label = $label;
        $this->type = $type;
        $this->subtype = $subtype;
        $this->order = $order;
        $this->url = $url;
    }

    public function getChildren()
    {
        return array_keys($this->children);
    }

    public function addChild($id)
    {
        $this->children[$id] = $id;
    }
}

$root = new MenuResolvedTestElement(0, 0, 'Top Level Menu', -1, '', 0);
$home = new MenuResolvedTestElement(1, 0, 'Home', 2, 0, 10);
$submenu = new MenuResolvedTestElement(2, 0, 'Submenu', 1, '', 20, '%site_url%/section/');
$url = new MenuResolvedTestElement(3, 2, 'External', 6, 'https://external.test/', 10, 'https://external.test/');
$plugin = new MenuResolvedTestElement(4, 0, 'Plugin', 4, 'demo', 30);
$static = new MenuResolvedTestElement(5, 0, 'Static', 5, 'about', 40);
$topic = new MenuResolvedTestElement(6, 0, 'Topic', 9, 'news', 50);
$core = new MenuResolvedTestElement(7, 0, 'Admin core', 3, 2, 60);
$callback = new MenuResolvedTestElement(8, 0, 'Legacy callback', 7, 'legacy_menu_callback', 70);
$separator = new MenuResolvedTestElement(9, 0, 'Section', 8, '', 80);

$root->addChild(1);
$root->addChild(2);
$root->addChild(4);
$root->addChild(5);
$root->addChild(6);
$root->addChild(7);
$root->addChild(8);
$root->addChild(9);
$submenu->addChild(3);

$Menus[1] = array(
    'menu_id' => 1,
    'menu_name' => 'navigation',
    'active' => 1,
    'menu_perm' => 3,
    'elements' => array(
        0 => $root,
        1 => $home,
        2 => $submenu,
        3 => $url,
        4 => $plugin,
        5 => $static,
        6 => $topic,
        7 => $core,
        8 => $callback,
        9 => $separator,
    ),
);

$tree = MENU_getResolvedTree('navigation');
menu_test_assert(count($tree) === 8, 'unexpected top-level node count');
menu_test_assert($tree[0]['label'] === 'Home', 'Home must remain first');
menu_test_assert($tree[0]['type'] === 2, 'Home must remain Geeklog Action type 2');
menu_test_assert($tree[0]['url'] === 'https://example.test/', 'Home URL was not resolved');
menu_test_assert($tree[0]['selected'] === true, 'Home should be selected on site root');
menu_test_assert($tree[1]['type'] === 1, 'submenu type was not preserved');
menu_test_assert(count($tree[1]['children']) === 1, 'submenu hierarchy was not preserved');
menu_test_assert($tree[1]['children'][0]['url'] === 'https://external.test/', 'nested URL was not preserved');
menu_test_assert($tree[2]['url'] === 'https://example.test/demo/', 'plugin URL was not resolved');
menu_test_assert($tree[3]['url'] === 'https://example.test/staticpages/index.php?page=about', 'static page URL was not resolved');
menu_test_assert($tree[4]['url'] === 'https://example.test/index.php?topic=news', 'topic URL was not resolved');
menu_test_assert($tree[5]['type'] === 3 && $tree[5]['resolved'] === false, 'complex Geeklog Core node must remain explicit and unresolved');
menu_test_assert($tree[6]['type'] === 7 && $tree[6]['resolved'] === false, 'PHP callback must not be misrepresented as resolved data');
menu_test_assert($tree[7]['type'] === 8 && $tree[7]['url'] === '', 'non-link item type 8 was not preserved');

echo "Resolved tree tests passed" . PHP_EOL;
