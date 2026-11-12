<?php
/** Standalone provider graphical editor tests: php tests/provider_editor.php */
error_reporting(E_ALL);
define('VERSION', 'test');
define('PLG_RET_OK', 1);
$_PLUGINS = array('exampleplugin');
$_TABLES = array('menu_elements' => 'gl_menu_elements');
$records = array();
$nextId = 100;
$lastInserted = 0;
$invalidations = 0;
class MenuProviderAdminTestElement {
    public $active = 1;
    public $access = 3;
    public $type = 6;
    public $url = 'menu-provider:exampleplugin:main';
    public $id = 4;
    public $group_id = 2;
}
$Menus = array(1 => array('elements' => array(4 => new MenuProviderAdminTestElement())));
require_once dirname(__DIR__) . '/provider_admin.php';
function MENU_resolvedNodeSelected($url) { return false; }
function PLG_invokeService($plugin, $action, $args, &$output, &$message) {
    if ($plugin !== 'exampleplugin' || $action !== 'getNavigationTree') { return 0; }
    $output = array(
        'contract_version' => 1, 'provider' => 'exampleplugin', 'tree' => 'main',
        'nodes' => array(
            array('id' => 'exampleplugin:resources', 'label' => 'Resources', 'url' => '/resources',
                'children' => array(array('id' => 'exampleplugin:resources.docs', 'label' => 'Docs', 'url' => '/docs')))
        )
    );
    return PLG_RET_OK;
}
function MENU_dbEscape($x) { return addslashes($x); }
function DB_query($sql) {
    global $records;
    if (strpos($sql, 'SELECT id FROM') !== false) {
        $found = array();
        foreach ($records as $id => $record) {
            if ($record['type'] === 'marker' && strpos($sql, "'" . addslashes($record['url']) . "'") !== false) {
                $found[] = array('id' => $id);
            }
        }
        return $found;
    }
    if (strpos($sql, 'MAX(element_order)') !== false) {
        return array(array('max_order' => 10));
    }
    return array();
}
function DB_fetchArray(&$result) { return array_shift($result); }
function DB_save($table, $columns, $values) {
    global $records, $nextId, $lastInserted;
    ++$nextId;
    $lastInserted = $nextId;
    $records[$lastInserted] = array(
        'type' => strpos($values, 'menu-') !== false ? 'marker' : 'link',
        'url' => strpos($values, 'menu-before:') !== false ? 'menu-before:exampleplugin:resources'
            : (strpos($values, 'menu-after:') !== false ? 'menu-after:exampleplugin:resources'
            : 'menu-anchor:exampleplugin:resources')
    );
}
function DB_insertId() { global $lastInserted; return $lastInserted; }
function MENU_invalidateRuntimeCache($flag) { global $invalidations; ++$invalidations; }
function menu_admin_assert($condition, $msg) {
    if (!$condition) { fwrite(STDERR, 'FAIL: ' . $msg . PHP_EOL); exit(1); }
}
$sources = MENU_providerAdminSources(1);
menu_admin_assert(count($sources) === 1, 'configured provider discovered');
menu_admin_assert(MENU_providerAdminNodeExists($sources[0]['nodes'], 'exampleplugin:resources.docs'), 'nested ID available');
menu_admin_assert(!MENU_providerAdminNodeExists($sources[0]['nodes'], 'exampleplugin:private'), 'unavailable ID denied');
menu_admin_assert(strpos(MENU_providerAdminRenderNodes($sources[0]['nodes']), 'resources.docs') !== false, 'graphical tree shown');
menu_admin_assert(strpos(MENU_providerAdminNodeOptions($sources[0]['nodes'], 0), '<option') !== false, 'selectable provider nodes');

$base = array('menu' => 1, 'marker_id' => 4, 'node_id' => 'exampleplugin:resources',
    'custom_label' => 'Extra link', 'custom_url' => '/extra/', 'placement' => 'before');
menu_admin_assert(MENU_providerAdminSave($base) === '', 'valid before placement saved');
menu_admin_assert(count($records) === 2 && $invalidations === 1, 'marker and link persisted, cache invalidated');
menu_admin_assert(MENU_providerAdminSave($base) === '', 'existing marker can be reused');
menu_admin_assert(count($records) === 3, 'second link does not duplicate placement marker');
$bad = $base; $bad['node_id'] = 'exampleplugin:private';
menu_admin_assert(MENU_providerAdminSave($bad) !== '', 'hidden node not writable');
$bad = $base; $bad['custom_url'] = 'javascript:alert(1)';
menu_admin_assert(MENU_providerAdminSave($bad) !== '', 'unsafe URL denied');
$bad = $base; $bad['marker_id'] = 8888;
menu_admin_assert(MENU_providerAdminSave($bad) !== '', 'foreign provider marker denied');
$bad = $base; $bad['placement'] = 'unknown';
menu_admin_assert(MENU_providerAdminSave($bad) !== '', 'unknown placement denied');
menu_admin_assert(count($records) === 3, 'invalid submissions do not create records');
echo "Provider editor tests passed\n";
