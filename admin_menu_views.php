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

function MENU_displayMenuList()
{
    global $_CONF, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN, $LANG_MENU_MENU_TYPES,
           $_MENU_CONF, $Menus;

    $retval = '';

    $menuArr = array(
        array('url' => $_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=newmenu',
              'text' => $LANG_MENU01['add_newmenu']),
        array('url' => $_CONF['site_admin_url'] . '/plugins/menu/configuration.php',
              'text' => $LANG_MENU01['configuration']),
    );
    $retval .= COM_startBlock($LANG_MENU01['menu_builder'], '', COM_getBlockTemplate('_admin_block', 'header'));
    $retval .= ADMIN_createMenu($menuArr, $LANG_MENU_ADMIN[1],
        $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');

    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'menulist.thtml'));
    $T->set_block('admin', 'menurow', 'mrow');
    $rowCounter = 0;

    if (is_array($Menus)) {
        foreach ($Menus as $menu) {
            $id = (int) $menu['menu_id'];
            $menuName = isset($menu['menu_name']) ? (string) $menu['menu_name'] : '';
            $safeMenuName = MENU_escapeStoredText($menuName);

            $T->set_var('menu_id', $id);
            $T->set_var('menu_name', $safeMenuName);
            $T->set_var('menuactive', '<form method="post" action="'
                . MENU_escapeHTML($_CONF['site_admin_url'])
                . '/plugins/menu/index.php" style="display:inline">'
                . MENU_adminTokenInput()
                . '<input type="hidden" name="mode" value="menuactivate"' . XHTML . '>'
                . '<input type="hidden" name="id" value="' . $id . '"' . XHTML . '>'
                . '<input type="hidden" name="active" value="' . ($menu['active'] == 1 ? '0' : '1') . '"' . XHTML . '>'
                . '<input type="checkbox" onclick="this.form.submit()"'
                . ($menu['active'] == 1 ? ' checked="checked"' : '') . XHTML . '></form>');

            if ($menuName !== 'block' && $menuName !== 'footer' && $menuName !== 'navigation') {
                $T->set_var('delete_menu', '<form method="post" action="'
                    . MENU_escapeHTML($_CONF['site_admin_url'])
                    . '/plugins/menu/index.php" style="display:inline" onsubmit="return confirm(\''
                    . MENU_escapeHTML($LANG_MENU01['confirm_delete']) . '\');">'
                    . MENU_adminTokenInput()
                    . '<input type="hidden" name="mode" value="deletemenu"' . XHTML . '>'
                    . '<input type="hidden" name="id" value="' . $id . '"' . XHTML . '>'
                    . '<button type="submit" style="border:0;background:none;padding:0;cursor:pointer"><img src="'
                    . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/images/delete.png" alt="'
                    . MENU_escapeHTML($LANG_MENU01['delete']) . '"' . XHTML . '></button></form>');
            } else {
                $T->set_var('delete_menu', '');
            }

            $T->set_var('menu_tree', isset($Menus[$id]['elements'])
                ? $Menus[$id]['elements'][0]->editTree(0, 2) : '');

            $menuType = isset($menu['menu_type']) ? (int) $menu['menu_type'] : 0;
            $menuTypeLabel = isset($LANG_MENU_MENU_TYPES[$menuType])
                ? MENU_escapeHTML($LANG_MENU_MENU_TYPES[$menuType]) : '';
            $elementDetails = '<b>' . MENU_escapeHTML($LANG_MENU01['type']) . ':</b> '
                . $menuTypeLabel . '<br' . XHTML . '>';
            $info = COM_getTooltip($safeMenuName, $elementDetails, '', $safeMenuName, 'help');
            $T->set_var('info', $info);
            $T->set_var('rowclass', ($rowCounter % 2) + 1);
            $T->parse('mrow', 'menurow', true);
            $rowCounter++;
        }
    }

    $T->set_var(array(
        'site_admin_url' => $_CONF['site_admin_url'],
        'site_url' => $_CONF['site_url'],
        'lang_admin' => $LANG_MENU00['admin'],
        'version' => $_MENU_CONF['pi_version'],
        'xhtml' => XHTML,
        'layout_url' => $_CONF['layout_url'],
        '$LANG_MENU01[clone]' => $LANG_MENU01['clone'],
        '$LANG_MENU01[edit]' => $LANG_MENU01['edit'],
        '$LANG_MENU01[options]' => $LANG_MENU01['options'],
        '$LANG_MENU01[elements]' => $LANG_MENU01['elements'],
        '$LANG_MENU01[delete]' => $LANG_MENU01['delete'],
        '$LANG_MENU01[active]' => $LANG_MENU01['active'],
    ));
    $T->parse('output', 'admin');
    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}

/*
 * Displays the clone menu form.
 */

function MENU_cloneMenu($menuId)
{
    global $_CONF, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN, $_MENU_CONF;

    $retval = '';
    $menuId = (int) $menuId;

    $menuArr = array(
        array('url' => $_CONF['site_admin_url'] . '/plugins/menu/index.php',
              'text' => $LANG_MENU01['menu_list']),
        array('url' => $_CONF['site_admin_url'] . '/plugins/menu/configuration.php',
              'text' => $LANG_MENU01['configuration']),
    );
    $retval .= COM_startBlock($LANG_MENU01['menu_builder'] . ' :: ' . $LANG_MENU01['add_newmenu'], '',
        COM_getBlockTemplate('_admin_block', 'header'));
    $retval .= ADMIN_createMenu($menuArr, $LANG_MENU_ADMIN[2],
        $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');

    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'clonemenu.thtml'));
    $T->set_var(array(
        'site_admin_url' => $_CONF['site_admin_url'],
        'site_url' => $_CONF['site_url'],
        'birdseed' => '<a href="' . MENU_escapeHTML($_CONF['site_admin_url'])
            . '/plugins/menu/index.php">' . MENU_escapeHTML($LANG_MENU01['menu_list'])
            . '</a> :: ' . MENU_escapeHTML($LANG_MENU01['clone']),
        'lang_admin' => $LANG_MENU00['admin'],
        'version' => $_MENU_CONF['pi_version'],
        'menu_id' => $menuId,
        'xhtml' => XHTML,
        'LANG_MENU01[clone_menu_label]' => $LANG_MENU01['clone_menu_label'],
        'LANG_MENU01[save]' => $LANG_MENU01['save'],
        'LANG_MENU01[cancel]' => $LANG_MENU01['cancel'],
    ));
    $T->parse('output', 'admin');
    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}

/*
 * Displays the create menu form.
 */

function MENU_createMenu()
{
    global $_CONF, $_TABLES, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN,
           $_MENU_CONF, $LANG_MENU_MENU_TYPES;

    $retval = '';

    $menuArr = array(
        array('url' => $_CONF['site_admin_url'] . '/plugins/menu/index.php',
              'text' => $LANG_MENU01['menu_list']),
        array('url' => $_CONF['site_admin_url'] . '/plugins/menu/configuration.php',
              'text' => $LANG_MENU01['configuration']),
    );
    $retval .= COM_startBlock($LANG_MENU01['menu_builder'] . ' :: ' . $LANG_MENU01['add_newmenu'], '',
        COM_getBlockTemplate('_admin_block', 'header'));
    $retval .= ADMIN_createMenu($menuArr, $LANG_MENU_ADMIN[2],
        $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');

    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'createmenu.thtml'));

    $menuTypeSelect = '<select id="menutype" name="menutype">' . LB;
    foreach ($LANG_MENU_MENU_TYPES as $typeId => $typeLabel) {
        $menuTypeSelect .= '<option value="' . (int) $typeId . '">'
            . MENU_escapeHTML($typeLabel) . '</option>' . LB;
    }
    $menuTypeSelect .= '</select>' . LB;

    $rootUser = DB_getItem($_TABLES['group_assignments'], 'ug_uid', 'ug_main_grp_id=1');
    $usergroups = SEC_getUserGroups($rootUser);
    $usergroups[$LANG_MENU01['non-logged-in']] = 998;
    ksort($usergroups);
    $groupSelect = '<select id="group" name="group">' . LB;
    foreach ($usergroups as $groupLabel => $groupId) {
        $groupSelect .= '<option value="' . (int) $groupId . '">'
            . MENU_escapeHTML($groupLabel) . '</option>' . LB;
    }
    $groupSelect .= '</select>' . LB;

    $T->set_var(array(
        'site_admin_url' => $_CONF['site_admin_url'],
        'site_url' => $_CONF['site_url'],
        'birdseed' => '<a href="' . MENU_escapeHTML($_CONF['site_admin_url'])
            . '/plugins/menu/index.php">' . MENU_escapeHTML($LANG_MENU01['menu_list'])
            . '</a> :: ' . MENU_escapeHTML($LANG_MENU01['add_newmenu']),
        'lang_admin' => $LANG_MENU00['admin'],
        'version' => $_MENU_CONF['pi_version'],
        'menutype_select' => $menuTypeSelect,
        'group_select' => $groupSelect,
        'xhtml' => XHTML,
        'label' => $LANG_MENU01['label'],
        'menu_type' => $LANG_MENU01['menu_type'],
        'active' => $LANG_MENU01['active'],
        'permission' => $LANG_MENU01['permission'],
        'save' => $LANG_MENU01['save'],
        'cancel' => $LANG_MENU01['cancel'],
    ));
    $T->parse('output', 'admin');
    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}
