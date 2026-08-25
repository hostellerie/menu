<?php

// Contract tests for legacy Menu CSS sanitization.

define('VERSION', '2.2.2');

$testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'menu-css-' . uniqid('', true) . DIRECTORY_SEPARATOR;
@mkdir($testDir, 0755, true);

function MENU_imageDir()
{
    global $testDir;
    return $testDir;
}

function MENU_imageUrl()
{
    return 'https://example.test/images/menu/';
}

require_once dirname(__DIR__) . '/css_security.php';

function cssSecurityAssert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

cssSecurityAssert(MENU_cssColor('#a1B2c3') === '#A1B2C3', 'valid six-digit color should be normalized');
cssSecurityAssert(MENU_cssColor('#fff') === '#000000', 'three-digit colors are intentionally rejected');
cssSecurityAssert(
    MENU_cssColor('#fff;background:url(javascript:alert(1))') === '#000000',
    'CSS injection payload must be rejected'
);
cssSecurityAssert(MENU_cssColor('red') === '#000000', 'named colors are outside the legacy allow-list');

cssSecurityAssert(MENU_cssImageFilename('../evil.png') === '', 'path traversal must not resolve to a CSS image');
cssSecurityAssert(MENU_cssImageFilename("x');color:red;/*.png") === '', 'CSS-breaking filename must be rejected');
cssSecurityAssert(MENU_cssImageFilename('missing.png') === '', 'missing images must not be rendered');

$filename = 'menu_bg_0123456789abcdef.png';
file_put_contents($testDir . $filename, 'test');
cssSecurityAssert(MENU_cssImageFilename($filename) === $filename, 'existing safe image filename should be accepted');
cssSecurityAssert(
    MENU_cssImageBackground($filename, 'repeat-x') === 'url("https://example.test/images/menu/' . $filename . '") repeat-x',
    'safe background fragment should use the site-specific image URL'
);
cssSecurityAssert(MENU_cssImageBackground('../evil.png') === '', 'unsafe image must produce no background fragment');

@unlink($testDir . $filename);
@rmdir($testDir);

echo "Legacy CSS security contract OK" . PHP_EOL;
