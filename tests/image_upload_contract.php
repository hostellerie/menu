<?php

function assertTrue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

define('VERSION', '2.2.2');

$root = dirname(__DIR__);
$helper = file_get_contents($root . '/image_upload.php');
$admin = file_get_contents($root . '/admin/index.php');
$mutations = file_get_contents($root . '/admin_menu_mutations.php');
$adminCode = $admin . "\n" . $mutations;
$sql = file_get_contents($root . '/sql/mysql_install.php');

assertTrue(strpos($helper, 'is_uploaded_file($tmpName)') !== false, 'real PHP upload required');
assertTrue(strpos($helper, 'getimagesize($tmpName)') !== false, 'image contents inspected');
assertTrue(strpos($helper, 'IMAGETYPE_PNG') !== false, 'PNG allow-listed');
assertTrue(strpos($helper, 'IMAGETYPE_GIF') !== false, 'GIF allow-listed');
assertTrue(strpos($helper, 'IMAGETYPE_JPEG') !== false, 'JPEG allow-listed');
assertTrue(strpos($helper, '$size > 2097152') !== false, '2 MB upload limit present');
assertTrue(strpos($helper, '$width > 4096') !== false, 'dimension limit present');
assertTrue(strpos($helper, 'MENU_imageDir()') !== false, 'site-specific image directory used');
assertTrue(strpos($helper, "basename((string) \$oldFilename)") !== false, 'old filename constrained to basename');

assertTrue(strpos($admin, "plugins/menu/image_upload.php") !== false, 'upload helper loaded');
assertTrue(strpos($mutations, "MENU_storeLegacyImageUpload") !== false, 'centralized upload helper used');
assertTrue(strpos($adminCode, "\$file['type']") === false, 'browser supplied MIME is not trusted');
assertTrue(strpos($adminCode, "path_html'] . 'images/menu/") === false, 'legacy hard-coded upload path removed');

assertTrue(strpos($sql, "'use_images', '1'") === false, 'fresh install does not enable missing image assets');
assertTrue(strpos($sql, "'menu_bg_filename', 'menu_bg.gif'") === false, 'missing background default removed');
assertTrue(strpos($sql, "'menu_hover_filename', 'menu_hover_bg.gif'") === false, 'missing hover default removed');
assertTrue(strpos($sql, "'menu_parent_filename', 'menu_parent.png'") === false, 'missing parent default removed');
assertTrue(strpos($sql, "'menu_parent_filename', 'vmenu_parent.gif'") === false, 'missing vertical parent default removed');

echo "Legacy image upload contract: OK\n";
