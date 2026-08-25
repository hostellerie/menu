<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.2.8                                                         |
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

$display = '';

// Only let admin users access this page
if (!SEC_hasRights('menu.admin')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the Menu Administration page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: " . $_SERVER['REMOTE_ADDR'],1);

    $display .= COM_startBlock($LANG_MENU00['access_denied']);
    $display .= $LANG_MENU00['access_denied_msg'];
    $display .= COM_endBlock();
    COM_output(COM_createHTMLDocument($display));
    exit;
}

/*
 * Displays a list of all menus
 */

function MENU_displayMenuList( ) {
    global $_CONF, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN, $LANG_ADMIN,
           $LANG_MENU_MENU_TYPES, $_MENU_CONF, $Menus;

    $retval = '';

    $menu_arr = array(
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php?mode=newmenu',
                  'text' => $LANG_MENU01['add_newmenu']),
            array('url'  => $_CONF['site_admin_url'] . '/plugins/menu/configuration.php',
                  'text' => $LANG_MENU01['configuration']),
    );
    $retval  .= COM_startBlock($LANG_MENU01['menu_builder'],'', COM_getBlockTemplate('_admin_block', 'header'));
    $retval  .= ADMIN_createMenu($menu_arr, $LANG_MENU_ADMIN[1],
                                $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');
    
    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file (array ('admin' => 'menulist.thtml'));
    $T->set_block('admin', 'menurow', 'mrow');
    $rowCounter = 0;
    if ( is_array($Menus) ) {
        foreach ($Menus AS $menu) {
            $id = $menu['menu_id'];
            $T->set_var('menu_id',$menu['menu_id']);
            $T->set_var('menu_name',$menu['menu_name']);
            $T->set_var('menuactive','<input type="checkbox" name="enabledmenu[' . $menu['menu_id'] . ']" onclick="submit()" value="1"' . ($menu['active'] == 1 ? ' checked="checked"' : '') . XHTML . '>');
            if ( $menu['menu_name'] != 'block' && $menu['menu_name'] != 'footer' && $menu['menu_name'] != 'navigation' ) {
                $T->set_var('delete_menu', '<form method="post" action="' . $_CONF['site_admin_url'] . '/plugins/menu/index.php" style="display:inline" onsubmit="return confirm(\'' . $LANG_MENU01['confirm_delete'] . '\');">' . MENU_adminTokenInput() . '<input type="hidden" name="mode" value="deletemenu"' . XHTML . '><input type="hidden" name="id" value="' . (int) $menu['menu_id'] . '"' . XHTML . '><button type="submit" style="border:0;background:none;padding:0;cursor:pointer"><img src="' . $_CONF['site_admin_url'] . '/plugins/menu/images/delete.png" alt="' . $LANG_MENU01['delete'] . '"' . XHTML . '></button></form>');
            } else {
                $T->set_var('delete_menu','');
            }
            $T->set_var('menu_tree',isset($Menus[$id]['elements']) ? $Menus[$id]['elements'][0]->editTree(0,2) : '');
            $elementDetails = '<b>' . $LANG_MENU01['type'] . ':</b> ' . $LANG_MENU_MENU_TYPES[$menu['menu_type']] . '<br' . XHTML . '>';
            $info       = COM_getTooltip($menu['menu_name'], $elementDetails, '', $menu['menu_name'], $template = 'help');
            $T->set_var('info',$info);
            $T->set_var('rowclass',($rowCounter % 2)+1);
            $T->parse('mrow','menurow',true);
            $rowCounter++;
        }
    }
    $T->set_var(array(
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
        'lang_admin'        => $LANG_MENU00['admin'],
        'version'           => $_MENU_CONF['pi_version'],
        'xhtml'             => XHTML,
        'layout_url'        => $_CONF['layout_url'],
        '$LANG_MENU01[clone]' => $LANG_MENU01['clone'],
        '$LANG_MENU01[edit]'  => $LANG_MENU01['edit'],
        '$LANG_MENU01[options]' => $LANG_MENU01['options'],
        '$LANG_MENU01[elements]' => $LANG_MENU01['elements'],
        '$LANG_MENU01[delete]' => $LANG_MENU01['delete'],
        '$LANG_MENU01[active]' => $LANG_MENU01['active']
    ));
    $T->parse('output', 'admin');
    $retval .= $T->finish($T->get_var('output'));

    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}

/*
 * Create a new menu
 */

function MENU_cloneMenu( $menu_id ) {
    global $_CONF, $_TABLES, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN, $_MENU_CONF,
           $LANG_MENU_MENU_TYPES, $LANG_ADMIN, $Menus;

    $retval = '';

    $menu_arr = array(
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php',
                  'text' => $LANG_MENU01['menu_list']),
            array('url'  => $_CONF['site_admin_url'] . '/plugins/menu/configuration.php',
                  'text' => $LANG_MENU01['configuration']),
    );
    $retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['add_newmenu'],'', COM_getBlockTemplate('_admin_block', 'header'));
    $retval  .= ADMIN_createMenu($menu_arr, $LANG_MENU_ADMIN[2],
                                $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');

    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'clonemenu.thtml'));

    $T->set_var(array(
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
        'form_action'       => $_CONF['site_admin_url'] . '/plugins/menu/index.php',
        'birdseed'          => '<a href="'.$_CONF['site_admin_url'].'/plugins/menu/index.php">'.$LANG_MENU01['menu_list'].'</a> :: '.$LANG_MENU01['clone'],
        'lang_admin'        => $LANG_MENU00['admin'],
        'version'           => $_MENU_CONF['pi_version'],
        'menu_id'           => $menu_id,
        'xhtml'             => XHTML,
        'LANG_MENU01[clone_menu_label]' => $LANG_MENU01['clone_menu_label'],
        'LANG_MENU01[save]' => $LANG_MENU01['save'],
        'LANG_MENU01[cancel]' => $LANG_MENU01['cancel']
    ));
    $T->parse('output', 'admin');
    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}

/*
 * Saves a clone menu element
 */

function MENU_saveCloneMenu( ) {
    global $_CONF, $_TABLES, $LANG_MENU00, $_MENU_CONF, $Menus, $_GROUPS;

    $menu_name = Geeklog\Input::fPost('menuname');
    $menu      = (int) Geeklog\Input::post('menu');

    $sql = "SELECT * FROM {$_TABLES['menu']} WHERE id=".$menu;
    $result = DB_query($sql);
    if ( DB_numRows($result) > 0 ) {
        $M = DB_fetchArray($result);
        $menu_type   = $M['menu_type'];
        $menu_active = $M['menu_active'];
        $group_id    = $M['group_id'];

        $sqlFieldList  = 'menu_name,menu_type,menu_active,group_id';
        $sqlDataValues = "'$menu_name',$menu_type,$menu_active,$group_id";
        DB_save($_TABLES['menu'], $sqlFieldList, $sqlDataValues);
        $menu_id = DB_insertId();
        $sql = "SELECT * FROM {$_TABLES['menu_config']} WHERE menu_id='".$menu."'";
        $result = DB_query($sql);
        while ($C = DB_fetchArray($result) ) {
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'".$C['conf_name']."','".$C['conf_value']."'");
        }

        $meadmin    = SEC_hasRights('menu.admin');
        $root       = SEC_inGroup('Root');
        $groups     = $_GROUPS;

        $sql = "SELECT * FROM {$_TABLES['menu_elements']} WHERE menu_id=".$menu;
        $result = DB_query($sql);
        while ($M = DB_fetchArray($result)) {
            $M['menu_id'] = $menu_id;
            $element            = new mbElement();
            $element->constructor( $M, $meadmin, $root, $groups );
            $element->id        = $element->createElementID($M['menu_id']);
            $element->saveElement();
        }
    }
    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
    $randID = rand();
    DB_save($_TABLES['vars'],'name,value',"'cacheid',$randID");
    MENU_initMENU(true);
}


/*
 * Create a new menu
 */

function MENU_createMenu( ) {
    global $_CONF, $_TABLES, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN, $_MENU_CONF,
           $LANG_MENU_MENU_TYPES, $LANG_ADMIN, $Menus;

    $retval = '';

    $menu_arr = array(
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php',
                  'text' => $LANG_MENU01['menu_list']),
            array('url'  => $_CONF['site_admin_url'] . '/plugins/menu/configuration.php',
                  'text' => $LANG_MENU01['configuration']),
    );
    $retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['add_newmenu'],'', COM_getBlockTemplate('_admin_block', 'header'));
    $retval  .= ADMIN_createMenu($menu_arr, $LANG_MENU_ADMIN[2],
                                $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');

    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'createmenu.thtml'));

    // build menu type select

    $menuTypeSelect = '<select id="menutype" name="menutype">' . LB;
    while ( $types = current($LANG_MENU_MENU_TYPES) ) {
        $menuTypeSelect .= '<option value="' . key($LANG_MENU_MENU_TYPES) . '"';
        $menuTypeSelect .= '>' . $types . '</option>' . LB;
        next($LANG_MENU_MENU_TYPES);
    }
    $menuTypeSelect .= '</select>' . LB;

    // build group select

    $rootUser = DB_getItem($_TABLES['group_assignments'],'ug_uid','ug_main_grp_id=1');
    $usergroups = SEC_getUserGroups($rootUser);
    $usergroups[$LANG_MENU01['non-logged-in']] = 998;
    ksort($usergroups);
    $group_select = '<select id="group" name="group">' . LB;
    for ($i = 0; $i < count($usergroups); $i++) {
        $group_select .= '<option value="' . $usergroups[key($usergroups)] . '"';
        $group_select .= '>' . key($usergroups) . '</option>' . LB;
        next($usergroups);
    }
    $group_select .= '</select>' . LB;

    $T->set_var(array(
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
        'form_action'       => $_CONF['site_admin_url'] . '/plugins/menu/index.php',
        'birdseed'          => '<a href="'.$_CONF['site_admin_url'].'/plugins/menu/index.php">'.$LANG_MENU01['menu_list'].'</a> :: '.$LANG_MENU01['add_newmenu'],
        'lang_admin'        => $LANG_MENU00['admin'],
        'version'           => $_MENU_CONF['pi_version'],
        'menutype_select'   => $menuTypeSelect,
        'group_select'      => $group_select,
        'xhtml'             => XHTML,
        'label'             => $LANG_MENU01['label'],
        'menu_type'         => $LANG_MENU01['menu_type'],
        'active'            => $LANG_MENU01['active'],
        'permission'        => $LANG_MENU01['permission'],
        'save'              => $LANG_MENU01['save'],
        'cancel'            => $LANG_MENU01['cancel'],
    ));
    $T->parse('output', 'admin');
    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}

/*
 * Saves a new menu element
 */

function MENU_saveNewMenu( ) {
    global $_CONF, $_TABLES, $LANG_MENU00, $_MENU_CONF, $Menus, $_GROUPS;
    
    $menuname   = Geeklog\Input::fPost('menuname');
    $menutype   = (int) Geeklog\Input::fPost('menutype');
    $menuactive = (int) Geeklog\Input::fPost('menuactive');
    $menugroup  = (int) Geeklog\Input::fPost('group');

    $sqlFieldList  = 'menu_name,menu_type,menu_active,group_id';
    $sqlDataValues = "'$menuname',$menutype,$menuactive,$menugroup";
    DB_save($_TABLES['menu'], $sqlFieldList, $sqlDataValues);

    $menu_id = DB_insertId();

    switch ( $menutype ) {
        case 1:
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_bg_color','#151515'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_bg_color','#3667c0'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_text_color','#CCCCCC'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_text_color','#FFFFFF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_text_color','#FFFFFF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_hover_text_color','#679EF1'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_background_color','#151515'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_hover_bg_color','#333333'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_highlight_color','#151515'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_shadow_color','#151515'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'use_images','1'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_bg_filename','menu_bg.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_hover_filename','menu_hover_bg.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_parent_filename','menu_parent.png'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_alignment','1'");
            break;
        case 2:
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_bg_color','#000000'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_bg_color','#000000'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_text_color','#3677C0'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_text_color','#679EF1'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_text_color','#FFFFFF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_hover_text_color','#679EF1'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_background_color','#151515'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_hover_bg_color','#333333'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_highlight_color','#151515'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_shadow_color','#151515'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'use_images','1'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_bg_filename','menu_bg.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_hover_filename','menu_hover_bg.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_parent_filename','menu_parent.png'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_alignment','1'");
            break;
        case 3:
        case 4:
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_bg_color','#DDDDDD'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_bg_color','#BBBBBB'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_text_color','#0000FF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_text_color','#FFFFFF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_text_color','#0000FF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_hover_text_color','#F7FF00'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_background_color','#DDDDDD'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_hover_bg_color','#BBBBBB'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_highlight_color','#999999'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_shadow_color','#999999'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'use_images','1'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_bg_filename','menu_bg.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_hover_filename','menu_hover_bg.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_parent_filename','vmenu_parent.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_alignment','1'");
            break;
    }

    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
    $randID = rand();
    DB_save($_TABLES['vars'],'name,value',"'cacheid',$randID");
    MENU_initMENU(true);
}

/*
 * Displays a list of all menu elements for the given menu
 */

function MENU_displayTree( $menu_id ) {
    global $_CONF, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN, $LANG_ADMIN,
           $_MENU_CONF, $Menus, $_SCRIPTS;

    $retval = '';
        
    $js = 'jQuery(function() {

    jQuery(".tbl_repeat tbody").tableDnD({
        onDrop: function(table, row) {
            var orders = jQuery.tableDnD.serialize();
            var menu_id = ' . $menu_id . ';
            jQuery.post(\'' . $_CONF['site_admin_url']. '/plugins/menu/index.php\', { orders : orders, menu_id : menu_id });
        }
    });

});';

    $_SCRIPTS->setJavaScript($js, true);
    $_SCRIPTS->setJavaScriptFile('menu', '/admin/plugins/menu/js/tablednd_0_6.js');

    $menu_arr = array(
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php?mode=new&amp;menuid='.$menu_id,
                  'text' => $LANG_MENU01['create_element']),
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php',
                  'text' => $LANG_MENU01['menu_list']),
    );
    $retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$Menus[$menu_id]['menu_name'],'', COM_getBlockTemplate('_admin_block', 'header'));
    $retval  .= ADMIN_createMenu($menu_arr, $LANG_MENU_ADMIN[3],
                                $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');
    
    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'menutree.thtml'));

    $menu_select = '<form name="jumpbox" id="jumpbox" action="' . $_CONF['site_admin_url'] . '/plugins/menu/index.php" method="get" style="margin:0;padding:0"><div>';
    $menu_select .= '<input type="hidden" name="mode" id="mode" value="menu"'.XHTML.'>' . LB;
    $menu_select .= '<strong>Menu</strong>' . ':&nbsp;<select name="menu" onchange="submit()">';
    foreach ($Menus AS $menu) {
        $menu_select .= '<option value="' . $menu['menu_id'].'"' . ($menu['menu_id'] == $menu_id ? ' selected="selected"' : '') . '>' . $menu['menu_name'] .'</option>' . LB;
    }
    $menu_select .= '</select>';
    $menu_select .= '&nbsp;<input type="submit" value="' . 'go' . '"' . XHTML . '>';
    $menu_select .= '</div></form>';

    $T->set_var(array(
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
        'birdseed'          => '<a href="'.$_CONF['site_admin_url'].'/plugins/menu/index.php">'.$LANG_MENU01['menu_list'].'</a> :: '.$Menus[$menu_id]['menu_name'].' :: '.$LANG_MENU01['elements'],
        'lang_admin'        => $LANG_MENU00['admin'],
        'version'           => $_MENU_CONF['pi_version'],
        'menu_tree'         => $Menus[$menu_id]['elements'][0]->editTree(0,2),
        'menuid'            => $menu_id,
        'menuname'          => $Menus[$menu_id]['menu_name'],
        'menu_select'       => $menu_select,
        'menuactive'        => $Menus[$menu_id]['active'] == 1 ? ' checked="checked"' : ' ',
        'xhtml'             => XHTML,
        'LANG_MENU01[enabled]' => $LANG_MENU01['enabled'],
        'LANG_MENU01[info]' => $LANG_MENU01['info'],
        'LANG_MENU01[edit]' => $LANG_MENU01['edit'],
        'LANG_MENU01[delete]' => $LANG_MENU01['delete'],
        'LANG_MENU01[order]' => $LANG_MENU01['order']
    ));

    $T->parse('output', 'admin');
    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}

/*
 * Moves a menu element up or down
 */
function MENU_moveElement( $menu_id, $mid, $direction ) {
    global $_CONF, $_TABLES, $_MENU_CONF, $Menus;

    switch ( $direction ) {
        case 'up' :
            $neworder = $Menus[$menu_id]['elements'][$mid]->order - 11;
            DB_query("UPDATE {$_TABLES['menu_elements']} SET element_order=" . $neworder . " WHERE menu_id=".$menu_id." AND id=" . $mid);
            break;
        case 'down' :
            $neworder = $Menus[$menu_id]['elements'][$mid]->order + 11;
            DB_query("UPDATE {$_TABLES['menu_elements']} SET element_order=" . $neworder . " WHERE menu_id=".$menu_id." AND id=" . $mid);
            break;
    }
    $pid = $Menus[$menu_id]['elements'][$mid]->pid;

    $Menus[$menu_id]['elements'][$pid]->reorderMenu();
    MENU_CACHE_remove_instance('menu');

    return;
}

/*
 * Creates a new menu element
 */

function MENU_createElement ( $menu_id ) {
    global $_CONF, $_TABLES, $_MENU_CONF, $Menus, $LANG_MENU00, $LANG_MENU01,
           $LANG_MENU_ADMIN, $LANG_MENU_TYPES, $LANG_MENU_GLTYPES, $LANG_MENU_GLFUNCTION,
           $_SCRIPTS, $_PLUGINS;

    $_SCRIPTS->setJavaScriptLibrary('jquery');
    
    $js = "<script type=\"text/javascript\">
        jQuery('#menu').show();
    </script>
    <script type=\"text/javascript\">
    jQuery(document).ready(function () {
        jQuery('#pid').change(function(){
             var option_id = jQuery('#pid').val();
             var menu_id = $menu_id;
             var url = 'getorder.php?optionid='+option_id+'&menuid='+menu_id;
             jQuery('#displayafter').load(url);
        });
        
        jQuery('#urldiv').css('display','');
        jQuery('#targetdiv').css('display','none');
        jQuery('#glcorediv').css('display','none');
        jQuery('#plugin').css('display','none');
        jQuery('#staticpage').css('display','none');
        jQuery('#glfunc').css('display','none');
        jQuery('#phpdiv').css('display','none');
        jQuery('#topic').css('display','none');

        //var myValidator = new fValidator(\"newitem\");

    });
    function toggleFields() {
        selected = jQuery('#menutype').val();

        switch( selected ) {
            case '1' : // sub
                jQuery('#urldiv').css('display','');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '2' : // gl action
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '3' : // gl menus
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '4' : // plugins
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '5' :  // static pages
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '6' : // url
                jQuery('#urldiv').css('display','');
                jQuery('#targetdiv').css('display','');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '7' :  // php function
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','');
                jQuery('#topic').css('display','none');
                break;
            case '8' :
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '9' : // topic
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','');
                break;
        }
    }
    </script>";
    
    $_SCRIPTS->setJavaScript($js);
    
    $retval = '';

    $menu_arr = array(
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php?mode=menu&amp;menu='.$menu_id,
                  'text' => 'Back to ' . $Menus[$menu_id]['menu_name']),
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php',
                  'text' => $LANG_MENU01['menu_list']),
    );
    $retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['create_element'] .' >> ' . $Menus[$menu_id]['menu_name'],'', COM_getBlockTemplate('_admin_block', 'header'));
    $retval  .= ADMIN_createMenu($menu_arr, $LANG_MENU_ADMIN[4],
                                $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');

    // build types select

    $spCount = 0;
    
    if ( in_array('staticpages', $_PLUGINS) ) {
        $sp_select = '<div id="staticpage" class="optional">
              <label for="spname">' . $LANG_MENU01['static_pages'] .'</label> <select id="spname" name="spname">' . LB;
        $sql = "SELECT sp_id,sp_title,sp_label FROM {$_TABLES['staticpage']} WHERE draft_flag = 0 ORDER BY sp_title ";
        $result = DB_query($sql);
        while (list ($sp_id, $sp_title,$sp_label) = DB_fetchArray($result)) {
            if ( $sp_title == '' ) {
                $label = $sp_label;
            } else {
                $label = $sp_title;
            }
            $sp_select .= '<option value="' . $sp_id . '">' . $label . '</option>' . LB;
            $spCount++;
        }
        $sp_select .= '</select></div>' . LB;
    }

    if ( $spCount == 0 ) {
        $sp_select = '';
    }

    $topicCount = 0;
    $topic_select = '<div id="topic" class="optional">
          <label for="topicname">' . $LANG_MENU01['topic'] . '</label> <select id="topicname" name="topicname">' . LB;
    $sql = "SELECT tid,topic FROM {$_TABLES['topics']} ORDER BY topic";
    $result = DB_query($sql);
    while (list ($tid, $topic) = DB_fetchArray($result)) {
        $topic_select .= '<option value="' . $tid . '">' . $topic . '</option>' . LB;
        $topicCount++;
    }
    $topic_select .= '</select></div>' . LB;
      
    if ( $topicCount == 0 ) {
        $topic_select = '';
    }

    $type_select = '<select id="menutype" name="menutype" onChange="toggleFields();">' . LB;
    while ( $types = current($LANG_MENU_TYPES) ) {
        if ( $spCount == 0 && key($LANG_MENU_TYPES) == 5 ) {
            // skip it
        } else {
            if ( ($Menus[$menu_id]['menu_type'] == 2 || $Menus[$menu_id]['menu_type'] == 4 ) && (key($LANG_MENU_TYPES) == 1 || key($LANG_MENU_TYPES) == 3)){
                // skip it
            } else {
                $type_select .= '<option value="' . key($LANG_MENU_TYPES) . '"';
                $type_select .= '>' . $types . '</option>' . LB;
            }
        }
        next($LANG_MENU_TYPES);
    }
    $type_select .= '</select>' . LB;

    $gl_select = '<select id="gltype" name="gltype">' . LB;
    while ( $gltype = current($LANG_MENU_GLTYPES) ) {
        $gl_select .= '<option value="' . key($LANG_MENU_GLTYPES) . '"';
        $gl_select .= '>' . $gltype . '</option>' . LB;
        next($LANG_MENU_GLTYPES);
    }
    $gl_select .= '</select>' . LB;

    $plugin_select = '<select id="pluginname" name="pluginname">' . LB;
    $plugin_menus = MENU_PLG_getMenuItems(); // PLG_getMenuItems();

    $num_plugins = count($plugin_menus);
    for( $i = 1; $i <= $num_plugins; $i++ ) {
        $plugin_select .= '<option value="' . key($plugin_menus) . '">' . key($plugin_menus) . '</option>' . LB;
        next( $plugin_menus );
    }
    $plugin_select .= '</select>' . LB;

    $glfunction_select = '<select id="glfunction" name="glfunction">' . LB;
    while ( $glfunction = current($LANG_MENU_GLFUNCTION) ) {
        $glfunction_select .= '<option value="' . key($LANG_MENU_GLFUNCTION) . '"';
        $glfunction_select .= '>' . $glfunction . '</option>' . LB;
        next($LANG_MENU_GLFUNCTION);
    }
    $glfunction_select .= '</select>' . LB;

    if ( $Menus[$menu_id]['menu_type'] == 2 || $Menus[$menu_id]['menu_type'] == 4 ) {
        $parent_select = '<input type="hidden" name="pid" id="pid" value="0"'.XHTML.'>'.$LANG_MENU01['top_level'];
    } else {
        $parent_select = '<select name="pid" id="pid">' . LB;
        $parent_select .= '<option value="0">' . $LANG_MENU01['top_level'] . '</option>' . LB;
        $result = DB_query("SELECT id,element_label FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND element_type=1");
        while ($row = DB_fetchArray($result)) {
            $parent_select .= '<option value="' . $row['id'] . '">' . $row['element_label'] . '</option>' . LB;
        }
        $parent_select .= '</select>' . LB;
    }

    $order_select = '<select id="menuorder" name="menuorder">' . LB;
    $order_select .= '<option value="0">' . $LANG_MENU01['first_position'] . '</option>' . LB;

    $result = DB_query("SELECT id,element_label,element_order FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND pid=0 ORDER BY element_order ASC");
    while ($row = DB_fetchArray($result)) {
        $order_select .= '<option value="' . $row['id'] . '">' . $row['element_label'] . '</option>' . LB;
    }
    $order_select .= '</select>' . LB;

    // build group select

    $rootUser = DB_getItem($_TABLES['group_assignments'],'ug_uid','ug_main_grp_id=1');

    $usergroups = SEC_getUserGroups($rootUser);
    $usergroups[$LANG_MENU01['non-logged-in']] = 998;
    ksort($usergroups);
    $group_select = '<select id="group" name="group">' . LB;

    for ($i = 0; $i < count($usergroups); $i++) {
        $group_select .= '<option value="' . $usergroups[key($usergroups)] . '"';
        $group_select .= '>' . key($usergroups) . '</option>' . LB;
        next($usergroups);
    }
    $group_select .= '</select>' . LB;
    
    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'createelement.thtml'));

    $T->set_var(array(
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
        'form_action'       => $_CONF['site_admin_url'] . '/plugins/menu/index.php',
        'birdseed'          => '<a href="'.$_CONF['site_admin_url'].'/plugins/menu/index.php">'.$LANG_MENU01['menu_list'].'</a> :: <a href="'.$_CONF['site_admin_url'].'/plugins/menu/index.php?mode=menu&amp;menu='.$menu_id.'">'.$Menus[$menu_id]['menu_name'].'</a> :: '.$LANG_MENU01['create_element'],
        'menuname'          => isset($menu_name) ? $menu_name : '',
        'menuid'            => $menu_id,
        'type_select'       => $type_select,
        'gl_select'         => $gl_select,
        'parent_select'     => $parent_select,
        'order_select'      => $order_select,
        'plugin_select'     => $plugin_select,
        'sp_select'         => $sp_select,
        'topic_select'      => $topic_select,
        'glfunction_select' => $glfunction_select,
        'group_select'      => $group_select,
        'xhtml'             => XHTML,
        'LANG_MENU01[parent]'        => $LANG_MENU01['parent'],
        'LANG_MENU01[elementlabel]'  => $LANG_MENU01['elementlabel'],
        'LANG_MENU01[display_after]' => $LANG_MENU01['display_after'],
        'LANG_MENU01[type]'          => $LANG_MENU01['type'],
        'LANG_MENU01[url]'           => $LANG_MENU01['url'],
        'LANG_MENU01[target]'        => $LANG_MENU01['target'],
        'LANG_MENU01[php]'           => $LANG_MENU01['php'],
        'LANG_MENU01[coretype]'      => $LANG_MENU01['coretype'],
        'LANG_MENU01[plugins]'       => $LANG_MENU01['plugins'],
        'LANG_MENU01[static_pages]'  => $LANG_MENU01['static_pages'],
        'LANG_MENU01[topic]'         => $LANG_MENU01['topic'],
        'LANG_MENU01[geeklog_function]' => $LANG_MENU01['geeklog_function'],
        'LANG_MENU01[active]'        => $LANG_MENU01['active'],
        'LANG_MENU01[permission]'    => $LANG_MENU01['permission'],
        'LANG_MENU01[save]'          => $LANG_MENU01['save'],
        'LANG_MENU01[cancel]'        => $LANG_MENU01['cancel'],
        'LANG_MENU01[same_window]'   => $LANG_MENU01['same_window'],
        'LANG_MENU01[new_window]'    => $LANG_MENU01['new_window'],
    ));

    $T->parse('output', 'admin');
    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}

/*
 * Saves a new menu element
 */

function MENU_saveNewMenuElement ( ) {
    global $_CONF, $_TABLES, $LANG_MENU00, $_MENU_CONF, $Menus, $_GROUPS;

    // build post vars
    $E['menu_id']        = (int) Geeklog\Input::fPost('menuid');
    $E['pid']            = (int) Geeklog\Input::fPost('pid');
    $E['element_label']  = htmlspecialchars(strip_tags(COM_checkWords(Geeklog\Input::post('menulabel'))));
    $E['element_type']   = (int) Geeklog\Input::fPost('menutype');
    $E['element_target'] = Geeklog\Input::fPost('urltarget');
    $afterElementID      = (int) Geeklog\Input::fPost('menuorder');
    $E['element_active'] = (int) Geeklog\Input::fPost('menuactive');
    $E['element_url']    = trim(Geeklog\Input::fPost('menuurl'));
    $E['group_id']       = (int) Geeklog\Input::fPost('group');

    switch($E['element_type']) {
        case 2 :
            $E['element_subtype'] = Geeklog\Input::fPost('glfunction');
            break;
        case 3 :
            $E['element_subtype'] = (int) Geeklog\Input::fPost('gltype');
            break;
        case 4 :
            $E['element_subtype'] = Geeklog\Input::fPost('pluginname');
            break;
        case 5 :
            $E['element_subtype'] = Geeklog\Input::fPost('spname');
            break;
        case 6 :
            $E['element_subtype'] = Geeklog\Input::fPost('menuurl');
            /*
             * check URL if it needs http:// appended...
             */
            if ( trim($E['element_subtype']) != '' ) {
                if(strpos($E['element_subtype'], "http") !== 0 && strpos($E['element_subtype'],"%site") === false && rtrim($E['element_subtype']) != '') {
                    $E['element_subtype'] = 'http://' . $E['element_subtype'];
                }
            }
            break;
        case 7 :
            $E['element_subtype'] = Geeklog\Input::fPost('phpfunction');
            break;
        case 9 :
            $E['element_subtype'] = Geeklog\Input::fPost('topicname');
            break;
        default :
            $E['element_subtype'] = '';
            break;
    }

    // check if URL needs the http:// added

    if ( trim($E['element_url']) != '' ) {
        if ( strpos($E['element_url'],"http") !== 0 && strpos($E['element_url'],"%site") === false && $E['element_url'][0] != '#' && rtrim($E['element_url']) != '' ) {
            $E['element_url'] = 'http://' . $E['element_url'];
        }
    }

    /*
     * Pull some constants..
     */

    $meadmin    = SEC_hasRights('menu.admin');
    $root       = SEC_inGroup('Root');
    $groups     = $_GROUPS;

    /* set element order */
    if ( $afterElementID == 0 ) {
        $aorder = 0;
    } else {
        $aorder = DB_getItem($_TABLES['menu_elements'],'element_order','id=' . $afterElementID);
    }
    $E['element_order'] = $aorder + 1;

    /*
     * build our class
     */

    $element            = new mbElement();
    $element->constructor( $E, $meadmin, $root, $groups );
    $element->id        = $element->createElementID($E['menu_id']);
    $element->saveElement();
    $pid                = $E['pid'];
    $menu_id            = $E['menu_id'];
    $Menus[$menu_id]['elements'][$pid]->reorderMenu();
    MENU_CACHE_remove_instance('menu');
}

/*
 * Edit an existing menu element
 */

function MENU_editElement( $menu_id, $mid ) {
    global $_CONF, $_TABLES, $_MENU_CONF, $Menus, $LANG_MENU00, $LANG_MENU01,
           $LANG_MENU_ADMIN, $LANG_MENU_TYPES, $LANG_MENU_GLTYPES,
           $LANG_MENU_GLFUNCTION, $_SCRIPTS, $_PLUGINS;

    $_SCRIPTS->setJavaScriptLibrary('jquery');
    
    $js = "<script type=\"text/javascript\">
        jQuery('#menu').show();
    </script>
    <script type=\"text/javascript\">
    jQuery(document).ready(function () {
        jQuery('#pid').change(function(){
             var option_id = jQuery('#pid').val();
             var menu_id = $menu_id;
             var url = 'getorder.php?optionid='+option_id+'&menuid='+menu_id;
             jQuery('#displayafter').load(url);
        });
        jQuery('#menutype').change(function(){
            toggleFields();
        });
        toggleFields();
        

        //var myValidator = new fValidator(\"newitem\");

    });
    function toggleFields() {
        selected = jQuery('#menutype').val();

        switch( selected ) {
            case '1' : // sub
                jQuery('#urldiv').css('display','');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '2' : // gl actioin
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '3' : // gl menus
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '4' : // plugins
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '5' :  // static pages
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '6' : // url
                jQuery('#urldiv').css('display','');
                jQuery('#targetdiv').css('display','');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '7' :  // php function
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','');
                jQuery('#topic').css('display','none');
                break;
            case '8' :
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','none');
                break;
            case '9' : // topic
                jQuery('#urldiv').css('display','none');
                jQuery('#targetdiv').css('display','none');
                jQuery('#glcorediv').css('display','none');
                jQuery('#plugin').css('display','none');
                jQuery('#staticpage').css('display','none');
                jQuery('#glfunc').css('display','none');
                jQuery('#phpdiv').css('display','none');
                jQuery('#topic').css('display','');
                break;
        }
    }
    </script>";
    
    $_SCRIPTS->setJavaScript($js);
    
    $retval = '';

    $menu_arr = array(
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php?mode=menu&amp;menu='.$menu_id,
                  'text' => 'Back to ' . $Menus[$menu_id]['menu_name']),
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php',
                  'text' => $LANG_MENU01['menu_list']),
    );
    $retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['edit_element'] .' for ' . $Menus[$menu_id]['menu_name'],'', COM_getBlockTemplate('_admin_block', 'header'));
    $retval  .= ADMIN_createMenu($menu_arr, $LANG_MENU_ADMIN[5],
                                $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');


    // build types select

    if ( $Menus[$menu_id]['elements'][$mid]->type == 1 ) {
        $type_select = '<select id="menutype" name="menutype" disabled="disabled">' . LB;
    } else {
        $type_select = '<select id="menutype" name="menutype">' . LB;
    }
    while ( $types = current($LANG_MENU_TYPES) ) {
        if ( key($LANG_MENU_TYPES) < 4 ){
            // skip it
        } else {
            $type_select .= '<option value="' . key($LANG_MENU_TYPES) . '"';
            $type_select .= ($Menus[$menu_id]['elements'][$mid]->type==key($LANG_MENU_TYPES) ? ' selected="selected"' : '') . '>' . $types . '</option>' . LB;
        }
        next($LANG_MENU_TYPES);
    }
    $type_select .= '</select>' . LB;


    $glfunction_select = '<select id="glfunction" name="glfunction">' . LB;
    while ( $glfunction = current($LANG_MENU_GLFUNCTION) ) {
        $glfunction_select .= '<option value="' . key($LANG_MENU_GLFUNCTION) . '"';
        $glfunction_select .= ($Menus[$menu_id]['elements'][$mid]->subtype==key($LANG_MENU_GLFUNCTION) ? ' selected="selected"' : '') . '>' . $glfunction . '</option>' . LB;
        next($LANG_MENU_GLFUNCTION);
    }
    $glfunction_select .= '</select>' . LB;

    $gl_select = '<select id="gltype" name="gltype">' . LB;
    while ( $gltype = current($LANG_MENU_GLTYPES) ) {
        $gl_select .= '<option value="' . key($LANG_MENU_GLTYPES) . '"';
        $gl_select .= ($Menus[$menu_id]['elements'][$mid]->subtype==key($LANG_MENU_GLTYPES) ? ' selected="selected"' : '') . '>' . $gltype . '</option>' . LB;
        next($LANG_MENU_GLTYPES);
    }
    $gl_select .= '</select>' . LB;

    $plugin_select = '<select id="pluginname" name="pluginname">' . LB;
    $plugin_menus = MENU_PLG_getMenuItems(); // PLG_getMenuItems();

    $found = 0;
    $num_plugins = count($plugin_menus);
    for( $i = 1; $i <= $num_plugins; $i++ )
    {
        $plugin_select .= '<option value="' . key($plugin_menus) . '"';

        if ( $Menus[$menu_id]['elements'][$mid]->subtype==key($plugin_menus) ) {
            $plugin_select .= ' selected="selected"';
            $found++;
        }
        $plugin_select .= '>' . key($plugin_menus) . '</option>' . LB;

        next( $plugin_menus );
    }
    if ( $found == 0 ) {
        $plugin_select .= '<option value="'.$Menus[$menu_id]['elements'][$mid]->subtype.'" selected="selected">'.$LANG_MENU01['disabled_plugin'].'</option>'.LB;
    }
    $plugin_select .= '</select>' . LB;

    //Staticpage
    if ( in_array('staticpages', $_PLUGINS) ) {
        $sp_select = '<select id="spname" name="spname">' . LB;
        $sql = "SELECT sp_id,sp_title,sp_label FROM {$_TABLES['staticpage']} WHERE draft_flag = 0 ORDER BY sp_title";
        $result = DB_query($sql);
        while (list ($sp_id, $sp_title,$sp_label) = DB_fetchArray($result)) {
            if (trim($sp_label) == '') {
                $label = $sp_title;
            } else {
                $label = $sp_label;
            }
            $sp_select .= '<option value="' . $sp_id . '"' . ($Menus[$menu_id]['elements'][$mid]->subtype == $sp_id ? ' selected="selected"' : '') . '>' . $label . '</option>' . LB;
        }
        $sp_select .= '</select>' . LB;
    }

    //Topics
    $topic_select = '<select id="topicname" name="topicname">' . LB;
    $sql = "SELECT tid,topic FROM {$_TABLES['topics']} ORDER BY topic";
    $result = DB_query($sql);
    while (list ($tid, $topic) = DB_fetchArray($result)) {
        $topic_select .= '<option value="' . $tid . '"' . ($Menus[$menu_id]['elements'][$mid]->subtype == $tid ? ' selected="selected"' : '') . '>' . $topic . '</option>' . LB;
    }
    $topic_select .= '</select>' . LB;

    if ( $Menus[$menu_id]['menu_type'] == 2 || $Menus[$menu_id]['menu_type'] == 4 ) {
        $parent_select = '<input type="hidden" name="pid" id="pid" value="0"'.XHTML.'>'.$LANG_MENU01['top_level'];
    } else {
        $parent_select = '<select id="pid" name="pid">' . LB;
        $parent_select .= '<option value="0">' . $LANG_MENU01['top_level'] . '</option>' . LB;
        $result = DB_query("SELECT id,element_label FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND element_type=1");
        while ($row = DB_fetchArray($result)) {
            $parent_select .= '<option value="' . $row['id'] . '" ' . ($Menus[$menu_id]['elements'][$mid]->pid==$row['id'] ? 'selected="selected"' : '') . '>' . $row['element_label'] . '</option>' . LB;
        }
        $parent_select .= '</select>' . LB;
    }

    // build group select

    $rootUser = DB_getItem($_TABLES['group_assignments'],'ug_uid','ug_main_grp_id=1');

    $usergroups = SEC_getUserGroups($rootUser);
    $usergroups[$LANG_MENU01['non-logged-in']] = 998;
    ksort($usergroups);
    $group_select = '<select id="group" name="group">' . LB;

    for ($i = 0; $i < count($usergroups); $i++) {
        $group_select .= '<option value="' . $usergroups[key($usergroups)] . '"';
        if ($Menus[$menu_id]['elements'][$mid]->group_id==$usergroups[key($usergroups)] ) {
            $group_select .= ' selected="selected"';
        }
        $group_select .= '>' . key($usergroups) . '</option>' . LB;
        next($usergroups);
    }
    $group_select .= '</select>' . LB;

    $target_select = '<select id="urltarget" name="urltarget">' . LB;
    $target_select .= '<option value=""' . ($Menus[$menu_id]['elements'][$mid]->target == "" ? ' selected="selected"' : '') . '>' . $LANG_MENU01['same_window'] . '</option>' . LB;
    $target_select .= '<option value="_blank"' . ($Menus[$menu_id]['elements'][$mid]->target == "_blank" ? ' selected="selected"' : '') . '>' . $LANG_MENU01['new_window'] . '</option>' . LB;
    $target_select .= '</select>' . LB;

    if ( $Menus[$menu_id]['elements'][$mid]->active ) {
        $active_selected = ' checked="checked"';
    } else {
        $active_selected = '';
    }

    $order_select = '<select id="menuorder" name="menuorder">' . LB;
    $order_select .= '<option value="0">' . $LANG_MENU01['first_position'] . '</option>' . LB;
    $result = DB_query("SELECT id,element_label,element_order FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND pid=".$Menus[$menu_id]['elements'][$mid]->pid." ORDER BY element_order ASC");
    $order = 10;

    while ($row = DB_fetchArray($result)) {
        if ( $Menus[$menu_id]['elements'][$mid]->order != $order ) {
            $test_order = $order + 10;
            $order_select .= '<option value="' . $row['id'] . '"' . ($Menus[$menu_id]['elements'][$mid]->order == $test_order ? ' selected="selected"' : '') . '>' . $row['element_label'] . '</option>' . LB;
        }
        $order += 10;
    }
    $order_select .= '</select>' . LB;
    
    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'editelement.thtml'));

    $T->set_var(array(
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
        'form_action'       => $_CONF['site_admin_url'] . '/plugins/menu/index.php',
        'birdseed'          => '<a href="'.$_CONF['site_admin_url'].'/plugins/menu/index.php">Menu List</a> :: <a href="'.$_CONF['site_admin_url'].'/plugins/menu/index.php?mode=menu&amp;menu='.$menu_id.'">'.$Menus[$menu_id]['menu_name'].'</a> :: Edit Element',
        'menulabel'         => $Menus[$menu_id]['elements'][$mid]->label,
        'menuorder'         => $Menus[$menu_id]['elements'][$mid]->order,
        'order_select'      => $order_select,
        'menuurl'           => $Menus[$menu_id]['elements'][$mid]->url,
        'phpfunction'       => $Menus[$menu_id]['elements'][$mid]->subtype,
        'type_select'       => $type_select,
        'gl_select'         => $gl_select,
        'plugin_select'     => $plugin_select,
        'sp_select'         => $sp_select,
        'topic_select'      => $topic_select,
        'glfunction_select' => $glfunction_select,
        'parent_select'     => $parent_select,
        'group_select'      => $group_select,
        'target_select'     => $target_select,
        'active_selected'   => $active_selected,
        'menu'              => $menu_id,
        'mid'               => $mid,
        'xhtml'             => XHTML,
        'LANG_MENU01[parent]'        => $LANG_MENU01['parent'],
        'LANG_MENU01[elementlabel]'  => $LANG_MENU01['elementlabel'],
        'LANG_MENU01[display_after]' => $LANG_MENU01['display_after'],
        'LANG_MENU01[type]'          => $LANG_MENU01['type'],
        'LANG_MENU01[url]'           => $LANG_MENU01['url'],
        'LANG_MENU01[target]'        => $LANG_MENU01['target'],
        'LANG_MENU01[php]'           => $LANG_MENU01['php'],
        'LANG_MENU01[coretype]'      => $LANG_MENU01['coretype'],
        'LANG_MENU01[plugins]'       => $LANG_MENU01['plugins'],
        'LANG_MENU01[static_pages]'  => $LANG_MENU01['static_pages'],
        'LANG_MENU01[topic]'         => $LANG_MENU01['topic'],
        'LANG_MENU01[geeklog_function]' => $LANG_MENU01['geeklog_function'],
        'LANG_MENU01[active]'        => $LANG_MENU01['active'],
        'LANG_MENU01[permission]'    => $LANG_MENU01['permission'],
        'LANG_MENU01[save]'          => $LANG_MENU01['save'],
        'LANG_MENU01[cancel]'        => $LANG_MENU01['cancel']
        
    ));
    $T->parse('output', 'admin');

    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}

/*
 * Saves an edited menu element
 */

function MENU_saveEditMenuElement ( ) {
    global $_TABLES, $Menus;
    
    $id      = (int) Geeklog\Input::fPost('id');
    $menu_id = Geeklog\Input::fPost('menu');
    $pid     = (int) Geeklog\Input::fPost('pid');
    $label   = htmlspecialchars(strip_tags(COM_checkWords(Geeklog\Input::post('menulabel'))));
    $type    = (int) Geeklog\Input::fPost('menutype');
    $target  = Geeklog\Input::fPost('urltarget');

    if ($type == 0) {
        $type = 1;
    }

    switch($type) {
        case 2 :
            $subtype = Geeklog\Input::fPost('glfunction');
            break;
        case 3 :
            $subtype = (int) Geeklog\Input::fPost('gltype');
            break;
        case 4 :
            $subtype = Geeklog\Input::fPost('pluginname');
            break;
        case 5 :
            $subtype = Geeklog\Input::fPost('spname');
            break;
        case 6 :
            $subtype = Geeklog\Input::fPost('menuurl');
            if ( strpos($subtype,"http") !== 0 && strpos($subtype,"%site") === false && $subtype[0] != '#' && rtrim($subtype) != '' ) {
                $subtype = 'http://' . $subtype;
            }
            break;
        case 7 :
            $subtype = Geeklog\Input::fPost('phpfunction');
            break;
        case 9 :
            $subtype = Geeklog\Input::fPost('topicname');
            break;
        default :
            $subtype = '';
            break;
    }
    
    $active = (int) Geeklog\Input::fPost('menuactive');
    $url    = trim(Geeklog\Input::fPost('menuurl'));

    if (strpos($url,"http") !== 0 && strpos($url,"%site") === false && $url[0] != '#' && rtrim($url) != '') {
        $url = 'http://' . $url;
    }

    $group_id = (int) Geeklog\Input::fPost('group');
    $aid      = (int) Geeklog\Input::fPost('menuorder');
    $aorder   = DB_getItem($_TABLES['menu_elements'],'element_order','id=' . $aid);
    $neworder = $aorder + 1;

    $sql        = "UPDATE {$_TABLES['menu_elements']} SET pid=$pid, element_order=$neworder, element_label='$label', element_type='$type', element_subtype='$subtype', element_active=$active, element_url='$url', element_target='$target', group_id=$group_id WHERE id=$id";

    DB_query($sql);
    MENU_initMENU(true);
    $Menus[$menu_id]['elements'][$pid]->reorderMenu();
    MENU_initMENU(true);
}


/**
* Enable and Disable block
*/
function MENU_changeActiveStatusElement ($bid_arr)
{
    global $_CONF, $_TABLES;

    $menu_id = (int) Geeklog\Input::fPost('menu');

    // first, disable all on the requested side
    $sql = "UPDATE {$_TABLES['menu_elements']} SET element_active = '0' WHERE menu_id=".$menu_id;
    DB_query($sql);
    if (isset($bid_arr)) {
        foreach ($bid_arr as $bid => $side) {
            $bid = COM_applyFilter($bid, true);
            // the enable those in the array
            $sql = "UPDATE {$_TABLES['menu_elements']} SET element_active = '1' WHERE id='$bid'";
            DB_query($sql);
        }
    }
    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
    MENU_CACHE_remove_instance('js');

    return;
}

/**
* Enable and Disable block
*/
function MENU_changeActiveStatusMenu ($bid_arr)
{
    global $_CONF, $_TABLES;

    // first, disable all on the requested side
    $sql = "UPDATE {$_TABLES['menu']} SET menu_active = '0'";
    DB_query($sql);
    if (isset($bid_arr)) {
        foreach ($bid_arr as $bid => $side) {
            $bid = COM_applyFilter($bid, true);
            // the enable those in the array
            $sql = "UPDATE {$_TABLES['menu']} SET menu_active = '1' WHERE id='$bid'";
            DB_query($sql);
        }
    }
    MENU_CACHE_remove_instance('menu');

    return;
}

function MENU_deleteMenu($menu_id) {
    global $Menus, $_CONF, $_TABLES, $_USER;

    MENU_deleteChildElements(0,$menu_id);

    DB_query("DELETE FROM {$_TABLES['menu']} WHERE id=".$menu_id);
    DB_query("DELETE FROM {$_TABLES['menu_config']} WHERE menu_id=".$menu_id);

    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
}


/**
* Recursivly deletes all elements and child elements
*
*/
function MENU_deleteChildElements( $id, $menu_id ){
    global $Menus, $_CONF, $_TABLES, $_USER;

    $sql = "SELECT * FROM {$_TABLES['menu_elements']} WHERE pid=" . $id . " AND menu_id='" . $menu_id . "'";
    $aResult = DB_query( $sql );
    $rowCount = DB_numRows($aResult);
    for ( $z=0; $z < $rowCount; $z++ ) {
        $row = DB_fetchArray( $aResult );
        MENU_deleteChildElements( $row['id'],$menu_id );
    }
    $sql = "DELETE FROM " . $_TABLES['menu_elements'] . " WHERE id=" . $id;
    DB_query( $sql );

    MENU_CACHE_remove_instance('menu');
}

/*
 * Sets colors, etc. for the menu
 */

function MENU_menuConfig( $mid ) {
    global $_CONF, $_TABLES, $_MENU_CONF, $Menus, $LANG_MENU00, $LANG_MENU01,
           $LANG_MENU_ADMIN, $LANG_MENU_TYPES, $LANG_MENU_GLTYPES, $LANG_MENU_GLFUNCTION,
           $_SCRIPTS, $LANG_MENU_MENU_TYPES, $LANG_VC, $LANG_HS, $LANG_HC, $LANG_VS;

    $js = '      jQuery(document).ready(
        function()
        {
            jQuery("#tmbg_sample").colorPicker();
            jQuery("#tmh_sample").colorPicker();
            jQuery("#tmt_sample").colorPicker();
            jQuery("#tmth_sample").colorPicker();
            jQuery("#smt_sample").colorPicker();
            jQuery("#smth_sample").colorPicker();
            jQuery("#smbg_sample").colorPicker();
            jQuery("#smhbg_sample").colorPicker();
            jQuery("#smh_sample").colorPicker();
            jQuery("#sms_sample").colorPicker();
            
            jQuery("#load").hide();
        });
    ';
    $_SCRIPTS->setJavaScriptLibrary('jquery');
    $_SCRIPTS->setJavaScript($js, true);
        
    $_SCRIPTS->setJavaScriptFile('menu_colorpicker', '/admin/plugins/menu/js/colorpicker.js',true);
    $_SCRIPTS->setCSSFile('colorpicker', '/admin/plugins/menu/css/colorPicker.css',true);

    /* define the active attributes for each menu type */

    $menuAttributes = array( 'main_menu_bg_color'       => 'none',
                             'main_menu_hover_bg_color' => 'none',
                             'main_menu_text_color'     => 'none',
                             'main_menu_hover_text_color' => 'none',
                             'submenu_text_color'       => 'none',
                             'submenu_hover_text_color' => 'none',
                             'submenu_background_color' => 'none',
                             'submenu_hover_bg_color'   => 'none',
                             'submenu_highlight_color'  => 'none',
                             'submenu_shadow_color'     => 'none',
                             'menu_bg_filename'         => 'none',
                             'menu_hover_filename'      => 'none',
                             'menu_parent_filename'     => 'none',
                             'menu_alignment'           => 'none',
                             'use_images'               => 'none',
                        );

    $HCattributes = array(   'main_menu_bg_color',
                             'main_menu_hover_bg_color',
                             'main_menu_text_color',
                             'main_menu_hover_text_color',
                             'submenu_hover_text_color',
                             'submenu_background_color',
                             'submenu_highlight_color',
                             'submenu_shadow_color',
                             'menu_bg_filename',
                             'menu_hover_filename',
                             'menu_parent_filename',
                             'menu_alignment',
                             'use_images',
                        );
    $HSattributes = array(   'main_menu_text_color',
                             'main_menu_hover_text_color',
                             'submenu_highlight_color',
                        );

    $VCattributes = array(   'main_menu_bg_color',
                             'main_menu_hover_bg_color',
                             'main_menu_text_color',
                             'main_menu_hover_text_color',
                             'submenu_text_color',
                             'submenu_hover_text_color',
                             'submenu_highlight_color',
                             'menu_parent_filename',
                             'menu_alignment',
                        );

    $VSattributes = array(   'main_menu_text_color',
                             'main_menu_hover_text_color',
                             'menu_alignment',
                        );

    $retval = '';
    $menu_id = $mid;
    $menu_arr = array(
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php',
                  'text' => $LANG_MENU01['menu_list']),
    );
    $retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['menu_colors'] .' for ' . $Menus[$menu_id]['menu_name'],'', COM_getBlockTemplate('_admin_block', 'header'));
    $retval  .= ADMIN_createMenu($menu_arr, $LANG_MENU_ADMIN[6],
                                $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');



    foreach ($menuAttributes AS $name => $display ) {
        $menuConfig[$name] = '#000000';
    }

    if ( is_array($Menus[$mid]['config']) ) {
        foreach ($Menus[$mid]['config'] AS $name => $value ) {
            $menuConfig[$name] = $value;
        }
    } else {
        foreach ($menuAttributes AS $name => $display ) {
            $menuConfig[$name] = '#000000';
        }
    }


    $main_menu_bg_colorRGB         = '[' . MENU_hexrgb($menuConfig['main_menu_bg_color'],'r') .
                                      ',' . MENU_hexrgb($menuConfig['main_menu_bg_color'],'g') .
                                      ',' . MENU_hexrgb($menuConfig['main_menu_bg_color'],'b') . ']';

    $main_menu_hover_bg_colorRGB   = '[' . MENU_hexrgb($menuConfig['main_menu_hover_bg_color'],'r')  .
                                      ',' . MENU_hexrgb($menuConfig['main_menu_hover_bg_color'],'g')  .
                                      ',' . MENU_hexrgb($menuConfig['main_menu_hover_bg_color'],'b')  . ']';

    $main_menu_text_colorRGB       = '[' . MENU_hexrgb($menuConfig['main_menu_text_color'],'r')  .
                                      ',' . MENU_hexrgb($menuConfig['main_menu_text_color'],'g')  .
                                      ',' . MENU_hexrgb($menuConfig['main_menu_text_color'],'b')  . ']';

    $main_menu_hover_text_colorRGB = '[' . MENU_hexrgb($menuConfig['main_menu_hover_text_color'],'r') .
                                      ',' . MENU_hexrgb($menuConfig['main_menu_hover_text_color'],'g') .
                                      ',' . MENU_hexrgb($menuConfig['main_menu_hover_text_color'],'b') . ']';

    $submenu_text_colorRGB         = '[' .  MENU_hexrgb($menuConfig['submenu_text_color'],'r')  .
                                      ',' . MENU_hexrgb($menuConfig['submenu_text_color'],'g')  .
                                      ',' . MENU_hexrgb($menuConfig['submenu_text_color'],'b')  . ']';

    $submenu_hover_text_colorRGB   = '[' . MENU_hexrgb($menuConfig['submenu_hover_text_color'],'r') .
                                      ',' . MENU_hexrgb($menuConfig['submenu_hover_text_color'],'g') .
                                      ',' . MENU_hexrgb($menuConfig['submenu_hover_text_color'],'b') . ']';

    $submenu_hover_bg_colorRGB     = '[' . MENU_hexrgb($menuConfig['submenu_hover_bg_color'],'r') .
                                      ',' . MENU_hexrgb($menuConfig['submenu_hover_bg_color'],'g') .
                                      ',' . MENU_hexrgb($menuConfig['submenu_hover_bg_color'],'b') . ']';

    $submenu_background_colorRGB   = '[' . MENU_hexrgb($menuConfig['submenu_background_color'],'r') .
                                      ',' . MENU_hexrgb($menuConfig['submenu_background_color'],'g') .
                                      ',' . MENU_hexrgb($menuConfig['submenu_background_color'],'b') . ']';

    $submenu_highlight_colorRGB    = '[' . MENU_hexrgb($menuConfig['submenu_highlight_color'],'r')  .
                                      ',' . MENU_hexrgb($menuConfig['submenu_highlight_color'],'g')  .
                                      ',' . MENU_hexrgb($menuConfig['submenu_highlight_color'],'b')  . ']';

    $submenu_shadow_colorRGB       = '[' . MENU_hexrgb($menuConfig['submenu_shadow_color'],'r')  .
                                      ',' . MENU_hexrgb($menuConfig['submenu_shadow_color'],'g')  .
                                      ',' . MENU_hexrgb($menuConfig['submenu_shadow_color'],'b')  . ']';

    $menu_active_check = ($Menus[$mid]['active'] == 1  ? ' checked="checked"' : '');

    $menu_align_left_checked  = ($menuConfig['menu_alignment'] == 1 ? 'checked="checked"' : '');
    $menu_align_right_checked = ($menuConfig['menu_alignment'] == 0 ? 'checked="checked"' : '');

    $use_images_checked = ($menuConfig['use_images'] == 1 ? ' checked="checked"' : '');
    $use_colors_checked = ($menuConfig['use_images'] == 0 ? ' checked="checked"' : '');

    // build menu type select

    $menuTypeSelect = '<select id="menutype" name="menutype">' . LB;
    while ( $types = current($LANG_MENU_MENU_TYPES) ) {
        $menuTypeSelect .= '<option value="' . key($LANG_MENU_MENU_TYPES) . '"';
        if (key($LANG_MENU_MENU_TYPES) == $Menus[$menu_id]['menu_type']) {
            $menuTypeSelect .= ' selected="selected"';
        }
        $menuTypeSelect .= '>' . $types . '</option>' . LB;
        next($LANG_MENU_MENU_TYPES);
    }
    $menuTypeSelect .= '</select>' . LB;

    // build group select

    $rootUser = DB_getItem($_TABLES['group_assignments'],'ug_uid','ug_main_grp_id=1');
    $usergroups = SEC_getUserGroups($rootUser);
    $usergroups[$LANG_MENU01['non-logged-in']] = 998;
    ksort($usergroups);
    $group_select = '<select id="group" name="group">' . LB;
    for ($i = 0; $i < count($usergroups); $i++) {
        $group_select .= '<option value="' . $usergroups[key($usergroups)] . '"';
        if ( $usergroups[key($usergroups)] == $Menus[$menu_id]['group_id']) {
            $group_select .= ' selected="selected"';
        }
        $group_select .= '>' . key($usergroups) . '</option>' . LB;
        next($usergroups);
    }
    $group_select .= '</select>' . LB;
    
    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'menuconfig.thtml'));

    $T->set_var(array(
        'group_select'      => $group_select,
        'menutype'          => $Menus[$menu_id]['menu_type'],
        'menutype_select'   => $menuTypeSelect,
        'menuactive'        => $Menus[$menu_id]['active'] == 1 ? ' checked="checked"' : ' ',
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
        'form_action'       => $_CONF['site_admin_url'] . '/plugins/menu/index.php',
        'birdseed'          => '<a href="'.$_CONF['site_admin_url'].'/plugins/menu/index.php">Menu List</a> :: '.$Menus[$mid]['menu_name'].' :: Configuration',
        'menu_id'           => $mid,
        'menu_name'         => $Menus[$mid]['menu_name'],
        'tmbgcolor'         => $menuConfig['main_menu_bg_color'],
        'tmbgcolorrgb'      => $main_menu_bg_colorRGB,
        'tmhcolor'          => $menuConfig['main_menu_hover_bg_color'],
        'tmhcolorrgb'       => $main_menu_hover_bg_colorRGB,
        'tmtcolor'          => $menuConfig['main_menu_text_color'],
        'tmtcolorrgb'       => $main_menu_text_colorRGB,
        'tmthcolor'         => $menuConfig['main_menu_hover_text_color'],
        'tmthcolorrgb'      => $main_menu_hover_text_colorRGB,
        'smtcolor'          => $menuConfig['submenu_text_color'],
        'smtcolorrgb'       => $submenu_text_colorRGB,
        'smthcolor'         => $menuConfig['submenu_hover_text_color'],
        'smthcolorrgb'      => $submenu_hover_text_colorRGB,
        'smbgcolor'         => $menuConfig['submenu_background_color'],
        'smbgcolorrgb'      => $submenu_background_colorRGB,
        'smhbgcolor'         => $menuConfig['submenu_hover_bg_color'],
        'smhbgcolorrgb'      => $submenu_hover_bg_colorRGB,
        'smhcolor'          => $menuConfig['submenu_highlight_color'],
        'smhcolorrgb'       => $submenu_highlight_colorRGB,
        'smscolor'          => $menuConfig['submenu_shadow_color'],
        'smscolorrgb'       => $submenu_shadow_colorRGB,
        'enabled'           => $menu_active_check,
        'graphics_selected' => $use_images_checked,
        'colors_selected'   => $use_colors_checked,
        'menu_bg_filename'          => $menuConfig['menu_bg_filename'],
        'menu_hover_filename'       => $menuConfig['menu_hover_filename'],
        'menu_parent_filename'      => $menuConfig['menu_parent_filename'],
        'alignment_left_checked'    => $menu_align_left_checked,
        'alignment_right_checked'   => $menu_align_right_checked,
        'xhtml'                     => XHTML,
        'LANG_MENU01[menu_properties]' => $LANG_MENU01['menu_properties'],
        'LANG_MENU01[active]' => $LANG_MENU01['active'],
        'LANG_MENU01[permission]' => $LANG_MENU01['permission'],
        'LANG_MENU01[menu_alignment]' => $LANG_MENU01['menu_alignment'],
        'LANG_MENU01[alignment_question]' => $LANG_MENU01['alignment_question'],
        'LANG_MENU01[align_left]' => $LANG_MENU01['align_left'],
        'LANG_MENU01[align_right]' => $LANG_MENU01['align_right'],
        'LANG_MENU01[menu_color_options]' => $LANG_MENU01['menu_color_options'],
        'LANG_MENU01[select_color]' => $LANG_MENU01['select_color'],
        'LANG_MENU01[not_used]' => $LANG_MENU01['not_used'],
        'LANG_MENU01[menu_graphics]' => $LANG_MENU01['menu_graphics'],
        'LANG_MENU01[graphics_or_colors]' => $LANG_MENU01['graphics_or_colors'],
        'LANG_MENU01[graphics]' => $LANG_MENU01['graphics'],
        'LANG_MENU01[colors]' => $LANG_MENU01['colors'],
        'LANG_MENU01[menu_bg_image]' => $LANG_MENU01['menu_bg_image'],
        'LANG_MENU01[currently]' => $LANG_MENU01['currently'],
        'LANG_MENU01[menu_hover_image]' => $LANG_MENU01['menu_hover_image'],
        'LANG_MENU01[parent_item_image]' => $LANG_MENU01['parent_item_image'],
        'LANG_MENU01[save]' => $LANG_MENU01['save'],
        'LANG_MENU01[reset]' => $LANG_MENU01['reset'],
        'LANG_MENU01[defaults]' => $LANG_MENU01['defaults'],
        'LANG_MENU01[confirm_reset]' => $LANG_MENU01['confirm_reset']
    ));

    if ( $Menus[$menu_id]['menu_type'] == 1 ) {
        $T->set_var('show_warning','1');
    }

    /* check menu type and call the proper foreach call to
       set the display for the items.
    */

    switch ($Menus[$mid]['menu_type']) {
        case 1: // horizontal cascading...
            foreach ($HCattributes AS $name) {
                $menuAttributes[$name] = 'show';
                $T->set_var('lang_'.$name,$LANG_HC[$name]);
            }
            break;
        case 2: // horizontal simple
            foreach ($HSattributes AS $name) {
                $menuAttributes[$name] = 'show';
                $T->set_var('lang_'.$name,$LANG_HS[$name]);
            }
            break;
        case 3: // vertical cascading
            foreach ($VCattributes AS $name) {
                $menuAttributes[$name] = 'show';
                $T->set_var('lang_'.$name,$LANG_VC[$name]);
            }
            break;
        case 4: // vertical simple
            foreach ($VSattributes AS $name) {
                $menuAttributes[$name] = 'show';
                $T->set_var('lang_'.$name,$LANG_VS[$name]);
            }
            break;
    }

    foreach ($menuAttributes AS $name => $display ) {
        $T->set_var($name.'_show', $display);
    }

    $T->parse('output', 'admin');

    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}

/*
 * Saves the menu configuration
 */

function MENU_saveMenuConfig($menu_id=0) {
    global $_CONF, $_TABLES, $_MENU_CONF, $Menus;

    $menu_id                          = (int) Geeklog\Input::fPost('menu_id');
    $mc['main_menu_bg_color']         = Geeklog\Input::fPost('tmbg_sample');
    $mc['main_menu_hover_bg_color']   = Geeklog\Input::fPost('tmh_sample');
    $mc['main_menu_text_color']       = Geeklog\Input::fPost('tmt_sample');
    $mc['main_menu_hover_text_color'] = Geeklog\Input::fPost('tmth_sample');
    $mc['submenu_text_color']         = Geeklog\Input::fPost('smt_sample');
    $mc['submenu_hover_text_color']   = Geeklog\Input::fPost('smth_sample');
    $mc['submenu_background_color']   = Geeklog\Input::fPost('smbg_sample');
    $mc['submenu_hover_bg_color']     = Geeklog\Input::fPost('smhbg_sample');
    $mc['submenu_highlight_color']    = Geeklog\Input::fPost('smh_sample');
    $mc['submenu_shadow_color']       = Geeklog\Input::fPost('sms_sample');
    $mc['menu_alignment']             = (int) Geeklog\Input::fPost('malign', 0);
    $mc['use_images']                 = (int) Geeklog\Input::fPost('gorc', 0);
    $menutype                         = (int) Geeklog\Input::fPost('menutype');
    $menuactive                       = (int) Geeklog\Input::fPost('menuactive');
    $menugroup                        = (int) Geeklog\Input::fPost('group');

    $menuname   = $Menus[$menu_id]['menu_name'];

    $sqlFieldList  = 'id,menu_name,menu_type,menu_active,group_id';
    $sqlDataValues = "$menu_id,'$menuname',$menutype,$menuactive,$menugroup";
    DB_save($_TABLES['menu'], $sqlFieldList, $sqlDataValues);

    foreach ($mc AS $name => $value) {
        DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'$name','$value'");
    }

    $file = array();
    $file = $_FILES['bgimg'];
    if ( isset($file['tmp_name']) && $file['tmp_name'] != '' ) {

        switch ( $file['type'] ) {
            case 'image/png' :
            case 'image/x-png' :
                $ext = '.png';
                break;
            case 'image/gif' :
                $ext = '.gif';
                break;
            case 'image/jpg' :
            case 'image/jpeg' :
            case 'image/pjpeg' :
                $ext = '.jpg';
                break;
            default :
                $ext = 'unknown';
                $retval = 2;
                break;
        }
        if ( $ext != 'unknown' ) {
            $imgInfo = @getimagesize($file['tmp_name']);
            if ( $imgInfo != false ) {
                $newFilename = 'menu_bg' . substr(md5(uniqid(rand())),0,8) . $ext;
                $rc = move_uploaded_file($file['tmp_name'],$_CONF['path_html'] . 'images/menu/' . $newFilename);
                if ( $rc ) {
                    @unlink($_CONF['path_html'] . '/menu/images/' . $Menus[$menu_id]['config']['bgimage']);
                    DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_bg_filename','$newFilename'");
                }
            }
        }
    }
    $file = array();
    $file = $_FILES['hvimg'];
    if ( isset($file['tmp_name']) && $file['tmp_name'] != '' ) {
        switch ( $file['type'] ) {
            case 'image/png' :
            case 'image/x-png' :
                $ext = '.png';
                break;
            case 'image/gif' :
                $ext = '.gif';
                break;
            case 'image/jpg' :
            case 'image/jpeg' :
            case 'image/pjpeg' :
                $ext = '.jpg';
                break;
            default :
                $ext = 'unknown';
                $retval = 2;
                break;
        }
        if ( $ext != 'unknown' ) {
            $imgInfo = @getimagesize($file['tmp_name']);
            if ( $imgInfo != false ) {
                $newFilename = 'menu_hover_bg' . substr(md5(uniqid(rand())),0,8) . $ext;
                $rc = move_uploaded_file($file['tmp_name'],$_CONF['path_html'] . 'images/menu/' . $newFilename);
                if ( $rc ) {
                    @unlink($_CONF['path_html'] . '/menu/images/' . $Menus[$menu_id]['config']['hoverimage']);
                    DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_hover_filename','$newFilename'");
                }
            }
        }
    }
    $file = array();
    $file = $_FILES['piimg'];
    if ( isset($file['tmp_name']) && $file['tmp_name'] != '' ) {
        switch ( $file['type'] ) {
            case 'image/png' :
            case 'image/x-png' :
                $ext = '.png';
                break;
            case 'image/gif' :
                $ext = '.gif';
                break;
            case 'image/jpg' :
            case 'image/jpeg' :
            case 'image/pjpeg' :
                $ext = '.jpg';
                break;
            default :
                $ext = 'unknown';
                $retval = 2;
                break;
        }
        if ( $ext != 'unknown' ) {
            $imgInfo = @getimagesize($file['tmp_name']);
            if ( $imgInfo != false ) {
                $newFilename = 'menu_parent' . substr(md5(uniqid(rand())),0,8) . $ext;
                $rc = move_uploaded_file($file['tmp_name'],$_CONF['path_html'] . 'images/menu/' . $newFilename);
                if ( $rc ) {
                    @unlink($_CONF['path_html'] . '/menu/images/' . $Menus[$menu_id]['config']['parentimage']);
                    DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_parent_filename','$newFilename'");
                }
            }
        }
    }
    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
    $randID = rand();
    DB_save($_TABLES['vars'],'name,value',"'cacheid',$randID");
    return;
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
            MENU_CACHE_remove_instance('menu');
            COM_redirect($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;menu=' . $menu_id);
            break;
        case 'save' :
            // save the new or edited element
            $menu_id = (int) Geeklog\Input::fPost('menuid');
            MENU_saveNewMenuElement();
            MENU_CACHE_remove_instance('menu');
            COM_redirect($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;menu=' . $menu_id);
            break;
        case 'savenewmenu' :
            MENU_saveNewMenu();
            $content = MENU_displayMenuList( );
            break;
        case 'saveclonemenu' :
            MENU_saveCloneMenu();
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
            MENU_changeActiveStatusElement(Geeklog\Input::post('enableditem'));
            MENU_initMENU();
            $content = MENU_displayTree( $menu_id );
            $currentSelect = $LANG_MENU01['menu_builder'];
            break;
        case 'menuactivate' :
            MENU_changeActiveStatusMenu(Geeklog\Input::post('enabledmenu'));
            MENU_initMENU();
            $content = MENU_displayMenuList( );
            $currentSelect = $LANG_MENU01['menu_builder'];
            break;
        case 'delete' :
            // delete the element
            $id      = (int) Geeklog\Input::fPost('mid');
            $menu_id = (int) Geeklog\Input::fPost('menuid');
            MENU_deleteChildElements( $id, $menu_id );
            $Menus[$menu_id]['elements'][0]->reorderMenu();
            echo COM_refresh($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;menu=' . $menu_id);
            exit;
            break;
        case 'deletemenu' :
            // delete the element
            $menu_id = (int) Geeklog\Input::fPost('id');
            MENU_deleteMenu($menu_id);
            COM_redirect($_CONF['site_admin_url'] . '/plugins/menu/index.php');
            break;
        case 'config' :
            $content = MENU_menuConfig($menu_id);
            $currentSelect = $LANG_MENU01['configuration'];
            $currentSelect = $LANG_MENU01['menu_builder'];
            break;
        case 'savecfg' :
            $menu_id = (int) Geeklog\Input::fPost('menu_id');
            MENU_saveMenuConfig($menu_id);
            MENU_initMENU();
            $content = MENU_menuConfig( $menu_id );
            $currentSelect = $LANG_MENU01['menu_colors'];
            break;
        case 'disablemenu' :
            $action = (int) Geeklog\Input::fPost('menuactive');
            $mid    = (int) Geeklog\Input::fPost('menutodisable');
            $sql = "UPDATE {$_TABLES['menu_config']} SET enabled = " . $action . " WHERE menu_id=" . $mid . ";";
            DB_query($sql);
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
    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
    $menu_id = (int) Geeklog\Input::fPost('menu_id');

    switch ( $Menus[$menu_id]['menu_type']) {
        case 1: // horizontal cascading (navigation menu)
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_bg_color','#151515'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_bg_color','#3667c0'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_text_color','#CCCCCC'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_text_color','#FFFFFF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_text_color','#FFFFFF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_hover_text_color','#679EF1'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_background_color','#151515'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_hover_bg_color','#333333'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_highlight_color','#333333'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_shadow_color','#000000'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'use_images','1'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_bg_filename','menu_bg.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_hover_filename','menu_hover_bg.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_parent_filename','menu_parent.png'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_alignment','1'");
            break;
        case 2: // horizontal simple (footer menu)
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_text_color','#3677C0'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_text_color','#679EF1'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_highlight_color','#999999'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_alignment','1'");
            break;
        case 3: //vertical simple
        case 4: // vertical cascading (block)
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_bg_color','#DDDDDD'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_bg_color','#BBBBBB'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_text_color','#0000FF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'main_menu_hover_text_color','#FFFFFF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_text_color','#0000FF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_hover_text_color','#FFFFFF'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'submenu_highlight_color','#999999'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_parent_filename','vmenu_parent.gif'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_alignment','1'");
            break;
    }
    MENU_initMenu();
    $content = MENU_displayMenuList( );
} else if ( isset($_POST['cancel']) && isset($_POST['menu']) ) {
    $menu_id = (int) Geeklog\Input::fPost('menu');
    $content = MENU_displayTree( $menu_id );
} else if ( isset($_POST['orders']) && isset($_POST['menu_id']) ) {
    $menu_id   = (int) Geeklog\Input::fPost('menu_id');
    $orders = explode('&', $_POST['orders']);
    $array = array();
    
    foreach($orders as $item) {
        $item = explode('=', $item);
        $item = explode('_', $item[1]);
        $array[] = $item[1];
    }
    foreach($array as $key => $mid) {
            $key = ($key+1) * 10;
            DB_query("UPDATE {$_TABLES['menu_elements']} SET element_order=" . $key . " WHERE menu_id=" . $menu_id . " AND id=" . $mid);        
    }
    $pid = $Menus[$menu_id]['elements'][$mid]->pid;

    MENU_CACHE_remove_instance('menu');
    
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
