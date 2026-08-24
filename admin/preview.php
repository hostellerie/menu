<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                        |
// +---------------------------------------------------------------------------+
// | preview.php                                                               |
// |                                                                           |
// | Isolated menu preview for the administration interface.                   |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

if (!SEC_hasRights('menu.admin')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$menu_id = isset($_GET['menu']) ? (int) $_GET['menu'] : 0;

if ($menu_id <= 0 || !isset($Menus[$menu_id])) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

/*
 * A preview must also work for a disabled menu. This change only affects the
 * current request; nothing is written to the database.
 */
$Menus[$menu_id]['active'] = 1;

$menu_name = $Menus[$menu_id]['menu_name'];
$menu_type = (int) $Menus[$menu_id]['menu_type'];
$menu_html = '';

switch ($menu_type) {
    case 1:
        $menu_html = MENU_getMenu($menu_name, 'gl_menu', 'gl_menu', '', 'parent');
        break;

    case 2:
        $menu_html = MENU_getMenu($menu_name, 'st-fmenu', '', '', '', 'st-f-last');
        break;

    case 3:
        $menu_html = phpblock_getMenu('', $menu_name);
        break;

    case 4:
        $menu_html = MENU_getMenu($menu_name, 'st-vmenu', '', '', '');
        break;
}

/*
 * Render the target menu CSS directly instead of relying on the surrounding
 * Geeklog document's asset pipeline. This keeps the iframe isolated and works
 * on both Geeklog 2.1.1 and 2.2.2.
 */
$menu_css = '';
$style_file = 'gl_horizontal-cascading.thtml';
$under = 50;
$over = 99;

switch ($menu_type) {
    case 2:
        $style_file = 'gl_horizontal-simple.thtml';
        break;
    case 3:
        $style_file = 'gl_vertical-cascading.thtml';
        $under = 25;
        break;
    case 4:
        $style_file = 'gl_vertical-simple.thtml';
        $under = 25;
        break;
}

$T = COM_newTemplate(CTL_plugin_templatePath('menu'));
$T->set_file(array('style' => $style_file));
$T->set_var('under', $under);
$T->set_var('over', $over);
$T->set_var('menu_id', $menu_id);
$T->set_var('site_url', $_CONF['site_url']);
$T->set_var('url1', '');
$T->set_var('url2', '');

if (isset($Menus[$menu_id]['config']) && is_array($Menus[$menu_id]['config'])) {
    foreach ($Menus[$menu_id]['config'] as $name => $value) {
        if ($name == 'use_images') {
            if ((int) $value === 1) {
                $T->set_var('url1', "url({$_CONF['site_url']}/images/menu/{menu_bg_filename}) repeat-x");
                $T->set_var('url2', "url({$_CONF['site_url']}/images/menu/{menu_hover_filename}) repeat-x");
            }
            continue;
        }
        $T->set_var($name, $value);
    }
}

$alignment = 1;
if (isset($Menus[$menu_id]['config']['menu_alignment'])) {
    $alignment = (int) $Menus[$menu_id]['config']['menu_alignment'];
}
$T->set_var('alignment', $alignment === 1 ? 'left' : 'right');
$T->parse('output', 'style');
$menu_css = $T->finish($T->get_var('output'));

$custom_css = MENU_dataDir() . 'css' . DIRECTORY_SEPARATOR . 'gl_menu' . $menu_id . '.css';
if (file_exists($custom_css)) {
    $menu_css .= "\n" . file_get_contents($custom_css);
}

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>' . "\n";
echo '<html><head><meta charset="utf-8">' . "\n";
echo '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n";
echo '<style type="text/css">' . MENU_compress($menu_css) . '</style>' . "\n";
echo '<style type="text/css">';
echo 'html,body{margin:0;padding:0;background:transparent;}';
echo 'body{padding:12px;box-sizing:border-box;min-height:80px;}';
/* Keep the real desktop menu visible when the iframe itself is narrow. */
if ($menu_type === 1) {
    echo '@media screen and (max-width:750px){#gl_menu' . $menu_id
        . '{display:block!important}.slicknav_menu{display:none!important}}';
}
echo '</style></head><body>' . "\n";

if ($menu_html === '') {
    echo '<p>Menu preview is empty for the current administrator.</p>';
} else {
    echo $menu_html;
}

echo "\n</body></html>";
