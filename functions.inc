<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0-alpha1                                                  |
// +---------------------------------------------------------------------------+
// | functions.inc                                                             |
// |                                                                           |
// | This file does two things: 1) it implements the necessary Geeklog Plugin  |
// | API methods and 2) implements all the common code needed by this plugin.  |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2018 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |                                                                           |
// | Based on the original Sitetailor Plugin                                   |
// | Copyright (C) 2008-2011 by the following authors:                         |
// |                                                                           |
// | Mark R. Evans - mark AT glfusion DOT org                                  |
// +---------------------------------------------------------------------------+
// | Created with the Geeklog Plugin Toolkit.                                  |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+

global $_DB_table_prefix, $_TABLES;

/**
* @package Menu
*/

if (stripos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    die ('This file can not be used on its own.');
}

/**
 * Language file include
 */
$plugin_path = $_CONF['path'] . 'plugins/menu/';
require_once $plugin_path . 'storage.php';
require_once $plugin_path . 'presentation.php';
$langfile = $plugin_path . 'language/' . $_CONF['language'] . '.php';

if (file_exists($langfile)) {
    require_once $langfile;
} else {
    require_once $plugin_path . 'language/english.php';
}
/**
* Check and see if we need to load the plugin configuration
*/ 
if (!isset($_MENU_CONF)) {
    require_once $_CONF['path_system'] . 'classes/config.class.php';

    $menu_config = config::get_instance();
    $_MENU_CONF = $menu_config->get_config('menu');
    require_once __DIR__ . '/autoinstall.php';
    $info = plugin_autoinstall_menu('menu');
    $_MENU_CONF['pi_version'] = $info['info']['pi_version'];
    unset($info);
}
/**
 * Menu plugin table(s)
 */
$_TABLES['menu'] = $_DB_table_prefix . 'menu';
$_TABLES['menu_config'] = $_DB_table_prefix . 'menu_config';
$_TABLES['menu_elements']= $_DB_table_prefix . 'menu_elements';


if (VERSION >= '2.0.0') {
    require_once $_CONF['path'].'plugins/menu/classes/classMenuElement2.php';
} else {
    require_once $_CONF['path'].'plugins/menu/classes/classMenuElement.php';
}

// +---------------------------------------------------------------------------+
// | Geeklog Plugin API Implementation                                         |
// +---------------------------------------------------------------------------+


/**
* Implements a [menu:] autotag.
*
* @param    string  $op         operation to perform
* @param    string  $content    item (e.g. story text), including the autotag
* @param    array   $autotag    parameters used in the autotag
* @param    mixed               tag names (for $op='tagname') or formatted content
*
*/
function plugin_autotags_menu($op,$content='',$autotag='') {

    global $Menus, $_TABLES, $_CONF;

    if ($op == 'tagname' ) {
        return array('menu');
    } else if ($op == 'parse') {
        /*
         * Check to see if the menu name has spaces, if so, concatenate into a single parm
         */
        if ( isset($autotag['parm2']) ) {
            $autotag['parm1'] .= ' ' . $autotag['parm2'];
        }
        switch( $autotag['tag'] ) {
            case 'menu' :
                $menu = '';
                $menuID = trim($autotag['parm1']);
                $id = DB_getItem($_TABLES['menu'],'id','menu_name="'.$menuID.'"');
                if ( $id > 0 ) {
                    if ( $Menus[$id]['menu_type'] == 1 ) {
                        $menu = MENU_getMenu($menuID,"gl_menu","gl_menu",'',"parent");
                    } else if ( $Menus[$id]['menu_type'] == 2 ) {
                        $menu = MENU_getMenu($menuID,'st-fmenu','','','','st-f-last');
                    } else if ( $Menus[$id]['menu_type'] == 3 ) {
                        $menu = phpblock_getMenu('',trim($autotag['parm1']));
                    } else if ( $Menus[$id]['menu_type'] == 4 ) {
                        $menu = MENU_getMenu($menuID,'st-vmenu','','','');
                    }
                    $content = str_replace($autotag['tagstr'],$menu,$content);
                } else {
                    $content = str_replace($autotag['tagstr'],$menu,$content);
                }
                break;
        }
        return $content;
    }
}

function plugin_cclabel_menu()
{
    global $_CONF, $LANG_MENU_1;

    $retval = array();
    if (SEC_hasRights('menu.admin')) {
        $retval = array($LANG_MENU_1['plugin_name'],
            $_CONF['site_admin_url'] . '/plugins/menu/index.php',
            plugin_geticon_menu());
    }

    return $retval;
}

function plugin_getadminoption_menu()
{
    global $_CONF, $LANG_MENU_1;

    if (SEC_hasRights('menu.admin')) {
        return array($LANG_MENU_1['plugin_name'],
            $_CONF['site_admin_url'] . '/plugins/menu/index.php', 0);
    }
}

function plugin_geticon_menu()
{
    global $_CONF;
    return $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png';
}

function plugin_chkVersion_menu()
{
    global $_CONF;

    require_once $_CONF['path'] . 'plugins/menu/autoinstall.php';
    $inst_parms = plugin_autoinstall_menu('menu');

    return $inst_parms['info']['pi_version'];
}

function plugin_upgrade_menu()
{
    global $_CONF, $_TABLES;

    $installed_version = DB_getItem($_TABLES['plugins'], 'pi_version',
                                    "pi_name = 'menu'");
    $code_version = plugin_chkVersion_menu();
    if ($installed_version == $code_version) {
        return true;
    }

    require_once $_CONF['path'] . 'plugins/menu/autoinstall.php';

    if (!plugin_compatible_with_this_version_menu('menu')) {
        return 3002;
    }

    // Move plugin-owned files outside path_data before recording the new
    // plugin version. This operation is non-destructive and idempotent.
    if (!MENU_migrateLegacyData()) {
        COM_errorLog('Menu upgrade: unable to migrate legacy menu_data to ' . MENU_dataDir());
        return false;
    }

    // Keep the public image directory site-specific via path_images.
    if (!is_dir($_CONF['path_images'] . 'menu')) {
        @mkdir($_CONF['path_images'] . 'menu', 0755, true);
    }

    MENU_CACHE_cleanup_plugin('');

    $inst_parms = plugin_autoinstall_menu('menu');
    $pi_gl_version = $inst_parms['info']['pi_gl_version'];

    DB_query("UPDATE {$_TABLES['plugins']} SET pi_version = '$code_version', pi_gl_version = '$pi_gl_version' WHERE pi_name = 'menu'");

    return true;
}

function plugin_autouninstall_menu()
{
    $out = array (
        'tables' => array('menu','menu_config','menu_elements'),
        'groups' => array('Menu Admin'),
        'features' => array('menu.admin'),
        'php_blocks' => array(),
        'vars' => array()
    );

    return $out;
}

function plugin_getheadercode_menu() {
    global $_CONF, $Menus, $_SCRIPTS;

    $menu_header = '';
    $js = '';
    $needsLegacyAssets = false;
    $legacyRendering = MENU_runtimeConfigEnabled('legacy_rendering', true);
    $loadLegacyCss = MENU_runtimeConfigEnabled('load_legacy_css', true);
    $loadLegacyJs = MENU_runtimeConfigEnabled('load_legacy_js', true);

    if (!$legacyRendering) {
        MENU_debugLog('Legacy header rendering disabled by configuration.');
        return '';
    }

    if (is_array($Menus)) {
        foreach ($Menus as $menu) {
            if (!empty($menu['active'])
                && !MENU_themeHandlesPresentation(isset($menu['menu_name']) ? $menu['menu_name'] : '')) {
                $needsLegacyAssets = true;
                break;
            }
        }
    }

    if ($needsLegacyAssets && $loadLegacyCss) {
        $_SCRIPTS->setCSSFile('menu_slicknav', '/menu/css/slicknav.css');
    }
    if ($needsLegacyAssets && $loadLegacyJs) {
        $_SCRIPTS->setJavaScriptFile('slicknav', '/menu/js/jquery.slicknav.js');
    }

    $this_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
    if ($this_dir == 'menu') {
        $menu_header .= '<meta http-equiv="cache-control" content="no-cache" />' . LB;
        $menu_header .= '<meta http-equiv="pragma" content="no-cache" />' . LB;
    }

    if (is_array($Menus)) {
        foreach ($Menus AS $menu) {
            if ($menu['active'] == 1) {
                if (MENU_themeHandlesPresentation(isset($menu['menu_name']) ? $menu['menu_name'] : '')) {
                    continue;
                }

                $cacheInstance = 'menu_css_' . $menu['menu_id'] . '__' . $_CONF['theme'];
                $retval = MENU_CACHE_check_instance($cacheInstance, 0);

                $custom_css = MENU_dataDir() . 'css' . DIRECTORY_SEPARATOR
                    . 'gl_menu' . $menu['menu_id'] . '.css';
                if (file_exists($custom_css)) {
                    $custom_ccs_code = LB . file_get_contents($custom_css);
                } else {
                    $custom_ccs_code = '';
                }

                if ($retval) {
                    $css_minify = MENU_compress($retval . $custom_ccs_code);
                    if ($loadLegacyCss) {
                        $menu_header .= LB . '<style type="text/css">' . $css_minify . '</style>' . LB;
                    }
                } else {
                    $ms = new Template($_CONF['path'].'plugins/menu/templates/');
                    $ms->set_var('under',50);
                    $ms->set_var('over',99);
                    switch ($menu['menu_type']) {
                        case 1:
                            $stylefile = 'gl_horizontal-cascading.thtml';
                            break;
                        case 2:
                            $stylefile = 'gl_horizontal-simple.thtml';
                            break;
                        case 3:
                            $stylefile = 'gl_vertical-cascading.thtml';
                            $ms->set_var('under',25);
                            break;
                        case 4:
                            $stylefile = 'gl_vertical-simple.thtml';
                            $ms->set_var('under',25);
                            break;
                        default:
                            $stylefile = 'gl_horizontal-cascading.thtml';
                            break;
                    }
                    $ms->set_file(array('style' => $stylefile));
                    $ms->set_var('menu_id',$menu['menu_id']);
                    $ms->set_var('site_url',$_CONF['site_url']);
                    if (is_array($menu['config'])) {
                        foreach ($menu['config'] AS $name => $value) {
                            if ($name == 'use_images' && $value == 0) {
                                $ms->set_var('url1','');
                                $ms->set_var('url2','');
                                continue;
                            } else if ($name == 'use_images' && $value == 1) {
                                $url1 = "url({$_CONF['site_url']}/images/menu/{menu_bg_filename}) repeat-x";
                                $url2 = "url({$_CONF['site_url']}/images/menu/{menu_hover_filename}) repeat-x";
                                $ms->set_var('url1',$url1);
                                $ms->set_var('url2',$url2);
                                continue;
                            }
                            $ms->set_var($name,$value);
                        }
                    }
                    if ($menu['config']['menu_alignment'] == 1) {
                        $ms->set_var('alignment','left');
                    } else {
                        $ms->set_var('alignment','right');
                    }

                    $ms->parse('output', 'style');
                    $fCSS = $ms->finish($ms->get_var('output'));

                    if ($menu['menu_type'] == 1 && $loadLegacyJs) {
                        $slicknave_css = " .slicknav_menu { display:none; } @media screen and (max-width: 750px) { #gl_menu" . $menu['menu_id'] . " { display:none; } .slicknav_menu { display:block; } }";
                    } else {
                        $slicknave_css = '';
                    }

                    $fCSS = $fCSS . $slicknave_css;
                    MENU_CACHE_create_instance($cacheInstance, $fCSS, 0);
                    $css_minify = MENU_compress($fCSS . $custom_ccs_code);
                    if ($loadLegacyCss) {
                        $menu_header .= LB . '<style type="text/css">' . $css_minify . '</style>' . LB;
                    }
                }

                $siteName = json_encode((string) $_CONF['site_name']);
                if ($siteName === false) {
                    $siteName = '"Menu"';
                }
                $js .= LB . "jQuery(document).ready(function() {
                    jQuery('#gl_menu" . $menu['menu_id'] . "').slicknav(({
                        label: " . $siteName . ",
                        allowParentLinks: true,
                    }));
                    jQuery( '.slicknav_nav .gl_menu" . $menu['menu_id'] . "' ).removeClass( 'gl_menu" . $menu['menu_id'] . "' );
                });" . LB;
            }
        }
    }

    if ($needsLegacyAssets && $loadLegacyJs) {
        $_SCRIPTS->setJavaScriptLibrary('jquery');
        if ($js !== '') {
            $_SCRIPTS->setJavaScript($js, true);
        }
    }

    return $menu_header;
}

function MENU_initMenu() {
    global $Menus,$_GROUPS, $_MENU_CONF, $_TABLES, $_USER;

    $mbadmin = SEC_hasRights('menu.admin');
    $root    = SEC_inGroup('Root');

    if (empty($_USER['uid'])) {
        $uid = 1;
    } else {
        $uid = $_USER['uid'];
    }

    $result = DB_query("SELECT * FROM {$_TABLES['menu']}",1);
    while ($menu = DB_fetchArray($result)) {
        $menuID = $menu['id'];
        $Menus[$menu['id']]['menu_name']   = $menu['menu_name'];
        $Menus[$menu['id']]['menu_id']     = $menu['id'];
        $Menus[$menu['id']]['active']      = $menu['menu_active'];
        $Menus[$menu['id']]['menu_type']   = $menu['menu_type'];
        $Menus[$menu['id']]['group_id']    = $menu['group_id'];

        if ($mbadmin || $root) {
            $Menus[$menu['id']]['menu_perm'] = 3;
        } else {
            if ($menu['group_id'] == 998) {
                if (COM_isAnonUser()) {
                    $Menus[$menu['id']]['menu_perm'] = 3;
                } else {
                    $Menus[$menu['id']]['menu_perm'] = 0;
                }
            } else {
                if (in_array($menu['group_id'], $_GROUPS)) {
                    $Menus[$menu['id']]['menu_perm'] = 3;
                }
            }
        }

        $cfgResult = DB_query("SELECT * FROM {$_TABLES['menu_config']} WHERE menu_id=".intval($menu['id']));
        while ($cfgRow = DB_fetchArray($cfgResult)) {
            $Menus[$menu['id']]['config'][$cfgRow['conf_name']] = $cfgRow['conf_value'];
        }

        $sql = "SELECT * FROM {$_TABLES['menu_elements']} WHERE menu_id=".intval($menuID)." ORDER BY element_order ASC";
        $elementResult      = DB_query($sql, 1);
        $element            = new mbElement();
        $element->id        = 0;
        $element->menu_id   = $menuID;
        $element->label     = 'Top Level Menu';
        $element->type      = -1;
        $element->pid       = 0;
        $element->order     = 0;
        $element->url       = '';
        $element->owner_id  = $mbadmin;
        $element->group_id  = $root;
        if ($mbadmin) {
            $element->access = 3;
        }
        $Menus[$menuID]['elements'][0] = $element;

        while ($A = DB_fetchArray($elementResult)) {
            $element = new mbElement();
            $element->constructor($A,$mbadmin,$root,$_GROUPS);
            if ($element->access > 0) {
                $Menus[$menuID]['elements'][$element->id] = $element;
            }
        }
    }

    if (is_array($Menus)) {
        foreach ($Menus as $name => $menu) {
            foreach ($Menus[$name]['elements'] as $id => $element) {
                if ($id != 0 && isset($Menus[$name]['elements'][$element->pid]->id)) {
                    $Menus[$name]['elements'][$element->pid]->setChild($id);
                }
            }
        }
    }
}

function MENU_getMenu($name='navigation',$wrapper='',$ulclass='',$liclass='',$parentclass='',$lastclass='',$selected='',$noid=0) {
    global $Menus, $_CONF;

    if (!MENU_runtimeConfigEnabled('legacy_rendering', true)) {
        MENU_debugLog('Legacy render skipped for menu ' . (string) $name . '.');
        return '';
    }

    $optionHash = md5($wrapper.$ulclass.$liclass.$parentclass.$lastclass.$selected);

    $menuID = '';
    $lang = COM_getLanguageId();

    if (!empty($lang)) {
        $mlname = $name . '_'.$lang;
        $cacheInstance = 'menu_' . $mlname . '_' . MENU_CACHE_security_hash() . '_' . $optionHash . '__' . $_CONF['theme'];
        $retval = MENU_CACHE_check_instance($cacheInstance, 0);
        if ($retval && $noid == 0) {
            return $retval;
        }
        $retval = '';
        if (is_array($Menus)) {
            foreach ($Menus AS $id) {
                if ($id['menu_name'] == $mlname) {
                    $menuID = $id['menu_id'];
                    break;
                }
            }
        }
    }
    if ($menuID == '') {
        if (!empty($retval) && $noid == 0) {
            return $retval;
        }
        $retval = '';
        $menuID = '';
        if (is_array($Menus)) {
            foreach ($Menus AS $id) {
                if (strcasecmp(trim($id['menu_name']), trim($name)) == 0) {
                    $menuID = $id['menu_id'];
                    break;
                }
            }
        }
    }
    if ($menuID != '' && $Menus[$menuID]['active'] == 1 && $Menus[$menuID]['menu_perm'] == 3) {
        if ($Menus[$menuID]['menu_type'] == 1) {
            $retval .= '<div id="gl_menu'.($noid == 0 ? $menuID : '').'"'
                . MENU_legacyNavigationAttributes($name) . '>'. LB;
        }
        if ($wrapper != '') {
            $retval .= '<div class="'.$wrapper.($noid == 0 ? $menuID : '').'">' . LB;
        }
        $retval .= $Menus[$menuID]['elements'][0]->showTree(0,$ulclass,$liclass,$parentclass,$lastclass,$selected);
        if ($wrapper != '') {
            $retval .= '</div>' . LB;
        }
        if ($Menus[$menuID]['menu_type'] == 1) {
            $retval .= '</div>'. LB;
        }
    } else {
        return '';
    }

    return $retval;
}

function phpblock_getMenu($arg1,$arg2) {
    global $Menus, $_GROUPS, $_CONF;

    $menuID = null;
    if (is_array($Menus)) {
        foreach ($Menus AS $id) {
            if ($id['menu_name'] == $arg2) {
                $menuID = $id['menu_id'];
                break;
            }
        }
    }
    if ($menuID === null || $Menus[$menuID]['active'] != 1 || $Menus[$menuID]['menu_perm'] == 0) {
        return;
    }

    if ($Menus[$menuID]['menu_type'] == 4) {
        $menu = MENU_getMenu($arg2,'st-vmenu','','','');
        return $menu != '' ? $menu : '';
    }
    if ($Menus[$menuID]['config']['menu_alignment'] == 1) {
        $parent = 'parent-l';
        $class  = 'gl_menu-vert-l';
    } else {
        $parent = 'parent-r';
        $class  = 'gl_menu-vert-r';
    }
    $menu = MENU_getMenu($arg2,$class,'','',$parent);
    if ($menu != '') {
        $retval = '<div id="'.$class.$menuID.'">';
        $retval .= $menu;
        $retval .= '</div>';
    } else {
        $retval = '';
    }

    return $retval;
}

function MENU_getImageFile($image) {
    global $_CONF;
    return $_CONF['site_admin_url'] . '/plugins/menu/images/' . $image;
}

function MENU_PLG_getMenuItems()
{
    global $_PLUGINS;

    $menu = array();
    $mbmenu = array();
    foreach ($_PLUGINS as $pi_name) {
        $function = 'plugin_getmenuitems_' . $pi_name;
        if (function_exists($function)) {
            $menuitems = $function();
            if (is_array($menuitems)) {
                $url = current($menuitems);
                $mbmenu[$pi_name] = $url;
                $menu = array_merge($menu, $mbmenu);
            }
        }
    }
    return $menu;
}

MENU_initMenu();

function plugin_templatesetvars_menu($templatename, &$template)
{
    if (!DB_checkTableExists('menu')) return;
    $menu = MENU_getMenu('navigation',"gl_menu","gl_menu",'',"parent");
    $template->set_var('header_navigation',$menu);
    $menu_footer = MENU_getMenu('footer',"st-fmenu","st-fmenu",'',"parent");
    $template->set_var('menu_footer',$menu_footer);
    $template->set_var('breadcrumbs', '');
}

MENU_ensureDataDirs();

$MENU_TEMPLATE_OPTIONS = array(
    'path_cache'    => MENU_dataDir() . 'cache' . DIRECTORY_SEPARATOR,
    'path_prefixes' => array(
                        $_CONF['path_themes'],
                        $_CONF['path'],
                        '/'
                       ),
    'incl_phpself_header' => true,
    'cache_by_language' => true,
    'default_vars' => array(
                        'site_url' => $_CONF['site_url'],
                        'site_admin_url' => $_CONF['site_admin_url'],
                        'layout_url' => $_CONF['layout_url'],
                        'xhtml' => (defined('XHTML') ? XHTML : ' /'),
                      ),
    'hook' => array(),
);

function MENU_cache_clean_directories($path, $needle = '', $since = 0)
{
    $retval = true;

    if ($dir = @opendir($path)) {
        while (false !== ($entry = readdir($dir))) {
            if ($entry == '.' || $entry == '..' || $entry == '.svn' || is_link($entry)) {
            } elseif (is_dir($path . '/' . $entry)) {
                $retval = $retval && MENU_cache_clean_directories($path . '/' . $entry, $needle);
                $retval = $retval && @rmdir($path . '/' . $entry);
            } elseif (empty($needle) || strpos($entry, $needle) !== false) {
                if (!$since || @filectime($path . '/' . $entry) <= $since) {
                    $retval = $retval && @unlink($path . '/' . $entry);
                } else {
                    $retval = false;
                }
            } else {
                $retval = false;
            }
        }
        @closedir($dir);
    }

    return $retval;
}

function MENU_CACHE_cleanup_plugin($plugin)
{
    global $MENU_TEMPLATE_OPTIONS;

    if (!empty($plugin)) {
        $plugin = str_replace(array('..', '/', '\\'), '', $plugin);
        $plugin = '__' . $plugin . '__';
    }
    $path_cache = substr($MENU_TEMPLATE_OPTIONS['path_cache'], 0, -1);
    MENU_cache_clean_directories($path_cache, $plugin);
}

function MENU_CACHE_remove_instance($iid)
{
    global $MENU_TEMPLATE_OPTIONS;

    $iid = str_replace(array('..', '/', '\\', ':'), '', $iid);
    $iid = str_replace('-','_',$iid);
    $path_cache = substr($MENU_TEMPLATE_OPTIONS['path_cache'], 0, -1);
    MENU_cache_clean_directories($path_cache, 'instance__'.$iid);
}

function MENU_CACHE_create_instance($iid, $data, $bypass_lang = false)
{
    global $MENU_TEMPLATE_OPTIONS, $_CONF;

    if (!MENU_runtimeConfigEnabled('enable_cache', true)) {
        return;
    }

    if ($MENU_TEMPLATE_OPTIONS['cache_by_language']) {
        if (!is_dir($MENU_TEMPLATE_OPTIONS['path_cache'] . $_CONF['language'])) {
            @mkdir($MENU_TEMPLATE_OPTIONS['path_cache'] . $_CONF['language'], 0755, true);
            @touch($MENU_TEMPLATE_OPTIONS['path_cache'] . $_CONF['language'] . '/index.html');
        }
    }

    $filename = MENU_CACHE_instance_filename($iid, $bypass_lang);
    @file_put_contents($filename, $data);
}

function MENU_CACHE_check_instance($iid, $bypass_lang = false)
{
    if (!MENU_runtimeConfigEnabled('enable_cache', true)) {
        return false;
    }

    $filename = MENU_CACHE_instance_filename($iid, $bypass_lang);
    if (file_exists($filename)) {
        $str = @file_get_contents($filename);
        return $str === false ? false : $str;
    }
    return false;
}

function MENU_CACHE_get_instance_update($iid, $bypass_lang = false)
{
    if (!MENU_runtimeConfigEnabled('enable_cache', true)) {
        return false;
    }

    $filename = MENU_CACHE_instance_filename($iid, $bypass_lang);
    return @filemtime($filename);
}

function MENU_CACHE_instance_filename($iid,$bypass_lang = false)
{
    global $MENU_TEMPLATE_OPTIONS, $_CONF;

    $path_cache = $MENU_TEMPLATE_OPTIONS['path_cache'];
    if (!$bypass_lang && $MENU_TEMPLATE_OPTIONS['cache_by_language']) {
        $path_cache .= $_CONF['language'] . '/';
    }
    $iid = COM_sanitizeFilename($iid, true);
    $filename = $path_cache.'instance__'.$iid.'.php';

    return $filename;
}

function MENU_CACHE_security_hash()
{
    global $_GROUPS, $_USER;

    static $hash = null;

    if (empty($hash)) {
        $groups = implode(',',$_GROUPS);
        $hash = strtolower(md5($groups));
        if (!empty($_USER['tzid'])) {
            $hash .= 'tz'.md5($_USER['tzid']);
        }
    }
    return $hash;
}

function MENU_compress($buffer) {
    $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
    $buffer = str_replace(array("\r\n", "\r", "\n"), '', $buffer);
    return $buffer;
}