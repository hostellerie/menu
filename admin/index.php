<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | index.php                                                                 |
// |                                                                           |
// | Plugin administration page                                                |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2014-2018 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// |                                                                           |
// | Based on the original Sitetailor Plugin                                   |
// | Copyright (C) 2008-2009 by the following authors:                         |
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

/**
* @package Menu
*/

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';
require_once $_CONF['path'].'system/lib-admin.php';
require_once $_CONF['path'].'plugins/menu/image_upload.php';
require_once $_CONF['path'].'plugins/menu/admin_menu_views.php';
require_once $_CONF['path'].'plugins/menu/admin_menu_mutations.php';
require_once $_CONF['path'].'plugins/menu/admin_element_views.php';

$display = '';

// Only let admin users access this page
MENU_adminEnforceCsrf();

if (!SEC_hasRights('menu.admin')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the Menu Administration page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: " . $_SERVER['REMOTE_ADDR'],1);

    $display .= COM_startBlock($LANG_MENU00['access_denied']);
    $display .= $LANG_MENU00['access_denied_msg'];
    $display .= COM_endBlock();
    COM_output(COM_createHTMLDocument($display));
    exit;
}











function MENU_hexrgb($hexstr, $rgb) {
    $int = hexdec($hexstr);
    switch($rgb) {
        case "r":
            return 0xFF & $int >> 0x10;
            break;
        case "g":
            return 0xFF & ($int >> 0x8);
            break;
        case "b":
            return 0xFF & $int;
            break;
        default:
            return array(
                "r" => 0xFF & $int >> 0x10,
                "g" => 0xFF & ($int >> 0x8),
                "b" => 0xFF & $int
            );
            break;
    }
}

/*
 * Main processing loop
 */

$msg = (int) Geeklog\Input::fGet('msg', 0);
$mode = Geeklog\Input::fGetOrPost('mode', '');
$menu_id = (int) Geeklog\Input::fRequest('menumid', 0);
$menu_id = (int) Geeklog\Input::fRequest('menu', $menu_id);
$mid = (int) Geeklog\Input::fRequest('mid', 0);

if ( (isset($_POST['execute']) || $mode != '') && !isset($_POST['cancel']) && !isset($_POST['defaults'])) {
    switch ( $mode ) {
        case 'clone' :
            $menu = (int) Geeklog\Input::fGet('id');
            $content = MENU_cloneMenu($menu);
            break;
        case 'menu' :
            // display the tree
            $content = MENU_displayTree( $menu_id );
            break;
        case 'new' :
            $menu = (int) Geeklog\Input::fGet('menuid');
            $content = MENU_createElement($menu);
            break;
        case 'move' :
            // do something with the direction
            $direction = Geeklog\Input::fPost('where');
            $mid       = (int) Geeklog\Input::fPost('mid');
            $menu_id   = (int) Geeklog\Input::fPost('menu');
            MENU_moveElement( $menu_id, $mid, $direction );
            COM_redirect($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;menu=' . $menu_id);
            break;
        case 'edit' :
            // call the editor
            $mid     = (int) Geeklog\Input::fGet('mid');
            $menu_id = (int) Geeklog\Input::fGet('menu');
            $content = MENU_editElement( $menu_id, $mid );
            $currentSelect = $LANG_MENU01['menu_builder'];
            break;
        case 'saveedit' :
            MENU_saveEditMenuElement();
            COM_redirect($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;menu=' . $menu_id);
            break;
        case 'savenewmenu' :
            MENU_saveNewMenu();
            $content = MENU_displayMenuList( );
            break;
        case 'saveeditmenu' :
            MENU_saveEditMenu();
            $content = MENU_displayMenuList( );
            break;
        case 'editmenu' :
            $menu_id = (int) Geeklog\Input::fGet('menu_id');
            $content = MENU_editMenu( $menu_id );
            break;
        case 'activate' :
            MENU_changeActiveStatusElement();
            $content = MENU_displayTree( $menu_id );
            $currentSelect = $LANG_MENU01['menu_builder'];
            break;
        case 'menuactivate' :
            MENU_changeActiveStatusMenu();
            $content = MENU_displayMenuList( );
            $currentSelect = $LANG_MENU01['menu_builder'];
            break;
        case 'delete' :
            $id      = (int) Geeklog\Input::fPost('mid');
            $menu_id = (int) Geeklog\Input::fPost('menuid');
            MENU_deleteElementTree($id, $menu_id);
            echo COM_refresh($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;menu=' . $menu_id);
            exit;
        case 'config' :
            $content = MENU_menuConfig($menu_id);
            $currentSelect = $LANG_MENU01['configuration'];
            $currentSelect = $LANG_MENU01['menu_builder'];
            break;
        case 'savecfg' :
            $menu_id = (int) Geeklog\Input::fPost('menu_id');
            MENU_saveMenuConfig($menu_id);
            $content = MENU_menuConfig( $menu_id );
            $currentSelect = $LANG_MENU01['menu_colors'];
            break;
        case 'disablemenu' :
            $action = (int) Geeklog\Input::fPost('menuactive');
            $mid    = (int) Geeklog\Input::fPost('menutodisable');
            MENU_setMenuConfigEnabled($mid, $action);
            COM_redirect($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;mid=' . $mid);
            break;
        case 'menucolor' :
            $content = MENU_menuConfig($menu_id);
            $currentSelect = $LANG_MENU01['menu_colors'];
            break;
        case 'menuconfig' :
            $menu_id = (int) Geeklog\Input::fRequest('menuid');
            $content = MENU_menuConfig($menu_id);
            $currentSelect = $LANG_MENU01['menu_colors'];
            break;
        case 'newmenu' :
            $content = MENU_createMenu( );
            $currentSelect = $LANG_MENU01['menu_builder'];
            break;
        default :
            $content = MENU_displayMenuList( );
            break;
    }
} else if ( isset($_POST['defaults']) ) {
    $menu_id = (int) Geeklog\Input::fPost('menu_id');
    MENU_restoreMenuDefaults($menu_id);
    $content = MENU_displayMenuList();
} else if ( isset($_POST['cancel']) && isset($_POST['menu']) ) {
    $menu_id = (int) Geeklog\Input::fPost('menu');
    $content = MENU_displayTree( $menu_id );
} else if ( isset($_POST['orders']) && isset($_POST['menu_id']) ) {
    $menu_id = (int) Geeklog\Input::fPost('menu_id');
    MENU_saveElementOrder($menu_id, Geeklog\Input::post('orders', ''));
    exit;

} else {
    // display the tree
    $content = MENU_displayMenuList( );
}

$display .= '<noscript>' . LB;
$display .= '    <div class="pluginAlert aligncenter" style="border:1px dashed #ccc;margin-top:10px;padding:15px;">' . LB;
$display .= '    <p>' . $LANG_MENU01['javascript_required'] . '</p>' . LB;
$display .= '    </div>' . LB;
$display .= '</noscript>' . LB;
$display .= '<div id="menu" style="display:none;">' . LB;
$display .= $content;
$display .= '</div>';
COM_output( COM_createHTMLDocument($display) );
