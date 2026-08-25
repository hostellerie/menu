<?php

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Menu administration view builders.
 *
 * These functions render administration pages only. Mutations remain in
 * dedicated controllers/helpers so presentation does not own persistence.
 */

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
            $T->set_var('menuactive', '<form method="post" action="' . $_CONF['site_admin_url'] . '/plugins/menu/index.php" style="display:inline">' . MENU_adminTokenInput() . '<input type="hidden" name="mode" value="menuactivate"' . XHTML . '><input type="hidden" name="id" value="' . (int) $menu['menu_id'] . '"' . XHTML . '><input type="hidden" name="active" value="' . ($menu['active'] == 1 ? '0' : '1') . '"' . XHTML . '><input type="checkbox" onclick="this.form.submit()"' . ($menu['active'] == 1 ? ' checked="checked"' : '') . XHTML . '></form>');
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
        $group_select .= '>' . MENU_escapeHTML(key($usergroups)) . '</option>' . LB;
        next($usergroups);
    }
    $group_select .= '</select>' . LB;

    $T->set_var(array(
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
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
