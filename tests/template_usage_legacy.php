<?php

// Geeklog 2.1.1-style header/footer template detection. PHP 5.6+.

define('VERSION', '2.1.1');
require_once dirname(__DIR__) . '/asset_usage.php';

function menu_template_legacy_fail($message)
{
    fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
    exit(1);
}

function menu_template_legacy_assert($condition, $message)
{
    if (!$condition) {
        menu_template_legacy_fail($message);
    }
}

class MenuLegacyTemplateFixture
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

$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'menu-template-legacy-' . uniqid('', true);
if (!mkdir($dir, 0700, true)) {
    menu_template_legacy_fail('unable to create temporary theme directory');
}

file_put_contents($dir . DIRECTORY_SEPARATOR . 'header.thtml', '<header>{header_navigation}</header>');
file_put_contents($dir . DIRECTORY_SEPARATOR . 'footer.thtml', '<footer>plain footer</footer>');

$_CONF = array('path_layout' => $dir . DIRECTORY_SEPARATOR);
$template = new MenuLegacyTemplateFixture($dir);

menu_template_legacy_assert(
    MENU_templateUsesVariable('header', $template, 'header_navigation'),
    'Geeklog 2.1.1 header_navigation should be detected'
);
menu_template_legacy_assert(
    !MENU_templateUsesVariable('header', $template, 'menu_footer'),
    'Geeklog 2.1.1 header must not claim unused menu_footer'
);
menu_template_legacy_assert(
    !MENU_templateUsesVariable('footer', $template, 'menu_footer'),
    'Geeklog 2.1.1 footer without menu_footer must not render it'
);

file_put_contents($dir . DIRECTORY_SEPARATOR . 'footer.thtml', '<footer>{menu_footer}</footer>');
menu_template_legacy_assert(
    MENU_templateUsesVariable('footer', $template, 'menu_footer'),
    'Geeklog 2.1.1 menu_footer should be detected'
);

@unlink($dir . DIRECTORY_SEPARATOR . 'header.thtml');
@unlink($dir . DIRECTORY_SEPARATOR . 'footer.thtml');
@rmdir($dir);

echo 'Geeklog 2.1.1 template usage tests passed' . PHP_EOL;
