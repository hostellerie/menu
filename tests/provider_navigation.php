<?php
/** Standalone provider navigation tests: php tests/provider_navigation.php */
error_reporting(E_ALL);
define('VERSION', 'test');
define('PLG_RET_OK', 1);
$_PLUGINS = array('exampleplugin');
require_once dirname(__DIR__) . '/provider_navigation.php';

function MENU_resolvedNodeSelected($url)
{
    return false;
}
function PLG_invokeService($plugin, $action, $args, &$output, &$message)
{
    if ($plugin !== 'exampleplugin' || $action !== 'getNavigationTree' || $args['tree'] !== 'main') {
        return 0;
    }
    $output = array(
        'contract_version' => 1, 'provider' => 'exampleplugin', 'tree' => 'main',
        'nodes' => array(
            array('id' => 'exampleplugin:home', 'label' => 'Home', 'url' => '/exampleplugin/'),
            array('id' => 'exampleplugin:resources', 'label' => 'Resources', 'url' => '/exampleplugin/resources/',
                'children' => array(
                    array('id' => 'exampleplugin:resources.items', 'label' => 'Items', 'url' => '/exampleplugin/items/')
                )
            )
        )
    );
    return PLG_RET_OK;
}
function menu_provider_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

menu_provider_assert(MENU_providerMarker('menu-provider:exampleplugin:main')['kind'] === 'provider', 'provider marker');
menu_provider_assert(MENU_providerMarker('menu-anchor:exampleplugin:resources')['kind'] === 'anchor', 'anchor marker');
menu_provider_assert(MENU_providerMarker('https://example.com') === null, 'regular URL unchanged');
menu_provider_assert(MENU_providerSafeUrl('https://example.com/path'), 'valid https');
menu_provider_assert(MENU_providerSafeUrl('/exampleplugin/'), 'valid local path');
menu_provider_assert(!MENU_providerSafeUrl('javascript:alert(1)'), 'reject js');
menu_provider_assert(!MENU_providerSafeUrl('//evil.example'), 'reject protocol-relative');
menu_provider_assert(!MENU_providerSafeUrl('menu-provider:exampleplugin:main'), 'marker never clickable');

$nodes = MENU_providerGetTree('exampleplugin', 'main');
menu_provider_assert(count($nodes) === 2, 'provider nodes loaded');
menu_provider_assert($nodes[1]['provider_node_id'] === 'exampleplugin:resources', 'namespaced stable id');
menu_provider_assert(count($nodes[1]['children']) === 1, 'nested node');

$overlay = array('menu_anchor' => 'exampleplugin:resources', 'children' => array(
    array('id' => 15, 'label' => 'My custom page', 'url' => '/staticpages/index.php?page=custom', 'children' => array())
));
$composed = MENU_providerCompose(array($nodes[0], $nodes[1], $overlay));
menu_provider_assert(count($composed) === 2, 'marker omitted from root');
menu_provider_assert(count($composed[1]['children']) === 2, 'custom entry attached');
menu_provider_assert($composed[1]['children'][1]['id'] === 15, 'custom element remains intact');
$orphan = MENU_providerCompose(array($overlay));
menu_provider_assert(count($orphan) === 0, 'orphan anchor hidden, not exposed');

$bad = array('id' => 'exampleplugin:malicious', 'label' => '<script>test</script>', 'url' => 'javascript:evil()');
$seen = array();
$budget = 300;
menu_provider_assert(MENU_providerNormalizeNodes(array($bad), 'exampleplugin', $seen, 0, $budget) === false, 'unsafe URL rejected');
$seen = array();
$budget = 300;
$duplicate = array(
    array('id' => 'exampleplugin:a', 'label' => 'One', 'url' => '/one'),
    array('id' => 'exampleplugin:a', 'label' => 'Two', 'url' => '/two')
);
menu_provider_assert(MENU_providerNormalizeNodes($duplicate, 'exampleplugin', $seen, 0, $budget) === false, 'duplicate ids rejected');
$seen = array();
$budget = 300;
menu_provider_assert(MENU_providerNormalizeNodes(array(array('id' => 'other:x', 'label' => 'Wrong provider', 'url' => '/')), 'exampleplugin', $seen, 0, $budget) === false, 'cross-provider spoof rejected');

$rendered = MENU_providerRenderHtml($composed);
menu_provider_assert(strpos($rendered, 'My custom page') !== false, 'legacy HTML includes custom entry');
menu_provider_assert(strpos($rendered, 'menu-anchor:') === false, 'no marker leaked');
$_PLUGINS = array();
menu_provider_assert(MENU_providerGetTree('exampleplugin', 'main') === array(), 'disabled provider fail closed');
echo "Menu provider navigation: OK\n";
