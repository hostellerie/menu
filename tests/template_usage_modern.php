<?php

// Geeklog 2.2.2-style index template detection. PHP 5.6+.

define('VERSION', '2.2.2');
function COM_createHTMLDocument() {}
require_once dirname(__DIR__) . '/asset_usage.php';

function menu_template_modern_fail($message)
{
    fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
    exit(1);
}

function menu_template_modern_assert($condition, $message)
{
    if (!$condition) {
        menu_template_modern_fail($message);
    }
}

class MenuModernTemplateFixture
{
    private $root;

    public function __construct($root)
    {
        $this->root = $root;
    }

    public function getRoot()
    {
        return array($this->root);
    }
}

$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'menu-template-modern-' . uniqid('', true);
if (!mkdir($dir, 0700, true)) {
    menu_template_modern_fail('unable to create temporary theme directory');
}

file_put_contents(
    $dir . DIRECTORY_SEPARATOR . 'index.thtml',
    '<html><header>{header_navigation}</header><footer>plain footer</footer></html>'
);

$_CONF = array('path_layout' => $dir . DIRECTORY_SEPARATOR);
$template = new MenuModernTemplateFixture($dir);

menu_template_modern_assert(
    MENU_templateUsesVariable('header', $template, 'header_navigation'),
    'Geeklog 2.2.2 header_navigation should be detected in index.thtml'
);
menu_template_modern_assert(
    !MENU_templateUsesVariable('footer', $template, 'menu_footer'),
    'Geeklog 2.2.2 footer must not claim absent menu_footer'
);

file_put_contents(
    $dir . DIRECTORY_SEPARATOR . 'index.thtml',
    '<html><header>{header_navigation}</header><footer>{menu_footer}</footer></html>'
);
menu_template_modern_assert(
    MENU_templateUsesVariable('footer', $template, 'menu_footer'),
    'Geeklog 2.2.2 menu_footer should be detected in index.thtml'
);

@unlink($dir . DIRECTORY_SEPARATOR . 'index.thtml');
@rmdir($dir);

echo 'Geeklog 2.2.2 template usage tests passed' . PHP_EOL;
