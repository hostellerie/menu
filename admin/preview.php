<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                        |
// +---------------------------------------------------------------------------+
// | preview.php                                                               |
// |                                                                           |
// | Isolated native and active-theme menu previews.                           |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

if (!SEC_hasRights('menu.admin')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$menu_id = isset($_GET['menu']) ? (int) $_GET['menu'] : 0;
$mode = isset($_GET['mode']) ? strtolower(trim((string) $_GET['mode'])) : 'native';

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

/**
 * Resolve and load the active theme's optional generic plugin preview provider.
 *
 * @return bool
 */
function MENU_previewLoadThemeProvider()
{
    global $_CONF;

    $manifest = MENU_themePresentationManifest();
    if (!isset($manifest['preview']['menu'])) {
        return false;
    }

    $relative = str_replace('\\', '/', (string) $manifest['preview']['menu']);
    $relative = ltrim($relative, '/');
    if ($relative === '' || strpos($relative, '..') !== false || strpos($relative, "\0") !== false) {
        return false;
    }

    $layoutPath = isset($_CONF['path_layout']) ? rtrim($_CONF['path_layout'], "/\\") : '';
    if ($layoutPath === '') {
        return false;
    }

    $file = $layoutPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($file)) {
        return false;
    }

    require_once $file;

    return function_exists('theme_plugin_presentation_preview');
}

/**
 * Return whether the active theme can preview this menu resource.
 *
 * @param string $menuName
 * @return bool
 */
function MENU_previewThemeAvailable($menuName)
{
    $resource = function_exists('MENU_presentationBaseResource')
        ? MENU_presentationBaseResource($menuName) : (string) $menuName;

    return MENU_themeHandlesPresentation($menuName)
        && MENU_previewLoadThemeProvider()
        && function_exists('theme_plugin_presentation_preview')
        && strcasecmp($resource, 'navigation') === 0;
}

if ($mode === 'tabs') {
    $themeAvailable = MENU_previewThemeAvailable($menu_name);
    $themeName = isset($_CONF['theme']) ? (string) $_CONF['theme'] : '';
    $base = rtrim($_CONF['site_admin_url'], '/') . '/plugins/menu/preview.php?menu=' . $menu_id;

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<style>';
    echo 'html,body{margin:0;padding:0;background:#fff;font:14px/1.4 Arial,sans-serif;color:#222}';
    echo '.menu-preview-tabs{display:flex;gap:4px;padding:8px 8px 0;border-bottom:1px solid #ccc;background:#f6f6f6}';
    echo '.menu-preview-tab{appearance:none;border:1px solid #bbb;border-bottom:0;background:#e9e9e9;padding:8px 14px;cursor:pointer;border-radius:4px 4px 0 0;font-weight:600}';
    echo '.menu-preview-tab[aria-selected=true]{background:#fff;position:relative;top:1px}';
    echo '.menu-preview-panel{display:none;padding:0}.menu-preview-panel.is-active{display:block}';
    echo '.menu-preview-frame{display:block;width:100%;height:500px;border:0;background:#fff;box-sizing:border-box}';
    echo '</style></head><body>';
    echo '<div class="menu-preview-tabs" role="tablist" aria-label="Menu preview modes">';
    echo '<button class="menu-preview-tab" type="button" role="tab" id="tab-native" aria-controls="panel-native" aria-selected="true">Menu preview</button>';
    if ($themeAvailable) {
        echo '<button class="menu-preview-tab" type="button" role="tab" id="tab-theme" aria-controls="panel-theme" aria-selected="false">Theme preview — '
            . htmlspecialchars($themeName, ENT_QUOTES, 'UTF-8') . '</button>';
    }
    echo '</div>';
    echo '<div class="menu-preview-panel is-active" id="panel-native" role="tabpanel" aria-labelledby="tab-native">';
    echo '<iframe class="menu-preview-frame" src="' . htmlspecialchars($base . '&amp;mode=native', ENT_QUOTES, 'UTF-8')
        . '" title="Native Menu preview"></iframe></div>';
    if ($themeAvailable) {
        echo '<div class="menu-preview-panel" id="panel-theme" role="tabpanel" aria-labelledby="tab-theme">';
        echo '<iframe class="menu-preview-frame" src="' . htmlspecialchars($base . '&amp;mode=theme', ENT_QUOTES, 'UTF-8')
            . '" title="Active theme preview"></iframe></div>';
    }
    echo '<script>(function(){var tabs=document.querySelectorAll(".menu-preview-tab");for(var i=0;i<tabs.length;i++){tabs[i].onclick=function(){for(var j=0;j<tabs.length;j++){tabs[j].setAttribute("aria-selected","false");var p=document.getElementById(tabs[j].getAttribute("aria-controls"));if(p){p.className="menu-preview-panel";}}this.setAttribute("aria-selected","true");var panel=document.getElementById(this.getAttribute("aria-controls"));if(panel){panel.className="menu-preview-panel is-active";}};}}());</script>';
    echo '</body></html>';
    exit;
}

if ($mode === 'theme') {
    if (!MENU_previewThemeAvailable($menu_name)) {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>';
        echo '<p>The active theme does not provide a preview for this menu.</p>';
        echo '</body></html>';
        exit;
    }

    $resource = function_exists('MENU_presentationBaseResource')
        ? MENU_presentationBaseResource($menu_name) : $menu_name;
    $document = theme_plugin_presentation_preview('menu', $resource, array(
        'menu_id' => $menu_id,
        'menu_name' => $menu_name,
    ));

    header('Content-Type: text/html; charset=utf-8');
    if ($document === '') {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>';
        echo '<p>Theme preview is empty for the current administrator.</p>';
        echo '</body></html>';
    } else {
        echo $document;
    }
    exit;
}

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

$image_url = MENU_imageUrl();

if (isset($Menus[$menu_id]['config']) && is_array($Menus[$menu_id]['config'])) {
    foreach ($Menus[$menu_id]['config'] as $name => $value) {
        if ($name == 'use_images') {
            if ((int) $value === 1) {
                $T->set_var('url1', 'url(' . $image_url . '{menu_bg_filename}) repeat-x');
                $T->set_var('url2', 'url(' . $image_url . '{menu_hover_filename}) repeat-x');
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

/*
 * Older vertical templates contain the historical /images/menu/ URL directly.
 * Rewrite it in the isolated preview so multisite path_images is respected.
 */
if ($image_url !== '') {
    $menu_css = str_replace(
        rtrim($_CONF['site_url'], '/') . '/images/menu/',
        $image_url,
        $menu_css
    );
}

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
