<?php

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Menu administration element/configuration view builders.
 *
 * These functions compose admin UI only. State changes are implemented in
 * admin_menu_mutations.php or dedicated endpoints.
 */

/*
 * Displays a list of all menu elements for the given menu
 */

function MENU_displayTree( $menu_id ) {
    global $_CONF, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN,
           $_MENU_CONF, $Menus, $_SCRIPTS;

    $_SCRIPTS->setJavaScriptLibrary('jquery');
    $_SCRIPTS->setJavaScriptFile('menu_tablednd', '/admin/plugins/menu/js/tablednd_0_6.js');
    $_SCRIPTS->setJavaScriptFile('menu_order_handle', '/admin/plugins/menu/js/menu-order-handle.js');

    $retval = '';
    $safeMenuName = MENU_escapeStoredText($Menus[$menu_id]['menu_name']);

    $menu_arr = array(
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php?mode=new&amp;menuid='.$menu_id,
                  'text' => $LANG_MENU01['create_element']),
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php',
                  'text' => $LANG_MENU01['menu_list']),
    );
    $retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$safeMenuName,'', COM_getBlockTemplate('_admin_block', 'header'));
    $retval  .= ADMIN_createMenu($menu_arr, $LANG_MENU_ADMIN[3],
                                $_CONF['site_admin_url'] . '/plugins/menu/images/menu.png');
    
    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'menutree.thtml'));

    $menu_select = '<form name="jumpbox" id="jumpbox" action="' . $_CONF['site_admin_url'] . '/plugins/menu/index.php" method="get" style="margin:0;padding:0"><div>';
    $menu_select .= '<input type="hidden" name="mode" id="mode" value="menu"'.XHTML.'>' . LB;
    $menu_select .= '<strong>' . MENU_escapeHTML($LANG_MENU00['menulabel']) . '</strong>' . ':&nbsp;<select name="menu" onchange="submit()">';
    foreach ($Menus AS $menu) {
        $menu_select .= '<option value="' . $menu['menu_id'].'"' . ($menu['menu_id'] == $menu_id ? ' selected="selected"' : '') . '>' . MENU_escapeHTML($menu['menu_name']) .'</option>' . LB;
    }
    $menu_select .= '</select>';
    $menu_select .= '&nbsp;<input type="submit" value="' . MENU_escapeHTML($LANG_MENU01['go']) . '"' . XHTML . '>';
    $menu_select .= '</div></form>';

    $T->set_var(array(
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
        'birdseed'          => '<a href="' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/index.php">' . MENU_escapeHTML($LANG_MENU01['menu_list']) . '</a> :: ' . $safeMenuName . ' :: ' . MENU_escapeHTML($LANG_MENU01['elements']),
        'lang_admin'        => $LANG_MENU00['admin'],
        'version'           => $_MENU_CONF['pi_version'],
        'menu_tree'         => $Menus[$menu_id]['elements'][0]->editTree(0,2),
        'menuid'            => $menu_id,
        'menuname'          => $safeMenuName,
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
 * Creates a new menu element
 */

function MENU_createElement ( $menu_id ) {
    global $_CONF, $_TABLES, $_MENU_CONF, $Menus, $LANG_MENU00, $LANG_MENU01,
           $LANG_MENU_ADMIN, $LANG_MENU_TYPES, $LANG_MENU_GLTYPES, $LANG_MENU_GLFUNCTION,
           $_SCRIPTS, $_PLUGINS;

    $_SCRIPTS->setJavaScriptLibrary('jquery');
    $_SCRIPTS->setJavaScriptFile('menu_element_editor', '/admin/plugins/menu/js/element-editor.js');
    
    
    $retval = '';
    $safeMenuName = MENU_escapeStoredText($Menus[$menu_id]['menu_name']);

    $menu_arr = array(
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php?mode=menu&amp;menu='.$menu_id,
                  'text' => MENU_escapeHTML($LANG_MENU01['back_to']) . ' ' . $safeMenuName),
            array('url'  => $_CONF['site_admin_url'] .'/plugins/menu/index.php',
                  'text' => $LANG_MENU01['menu_list']),
    );
    $retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['create_element'] .' >> ' . $safeMenuName,'', COM_getBlockTemplate('_admin_block', 'header'));
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
            $sp_select .= '<option value="' . $sp_id . '">' . MENU_escapeHTML($label) . '</option>' . LB;
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
        $topic_select .= '<option value="' . $tid . '">' . MENU_escapeHTML($topic) . '</option>' . LB;
        $topicCount++;
    }
    $topic_select .= '</select></div>' . LB;
      
    if ( $topicCount == 0 ) {
        $topic_select = '';
    }

    $type_select = '<select id="menutype" name="menutype">' . LB;
    $allowedTypes = MENU_getAllowedElementTypes(
        $LANG_MENU_TYPES,
        $Menus[$menu_id]['menu_type'],
        $spCount > 0,
        null,
        $topicCount > 0
    );
    foreach ($allowedTypes as $typeId => $typeLabel) {
        $type_select .= '<option value="' . (int) $typeId . '">'
            . MENU_escapeHTML($typeLabel) . '</option>' . LB;
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
        $pluginKey = (string) key($plugin_menus);
        $plugin_select .= '<option value="' . MENU_escapeHTML($pluginKey) . '">' . MENU_escapeHTML($pluginKey) . '</option>' . LB;
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

    $parent_select = '<select name="pid" id="pid">' . LB;
    $parent_select .= '<option value="0">' . $LANG_MENU01['top_level'] . '</option>' . LB;
    $result = DB_query("SELECT id,element_label FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND element_type=1 ORDER BY element_order ASC, id ASC");
    while ($row = DB_fetchArray($result)) {
        $parent_select .= '<option value="' . (int) $row['id'] . '">' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
    }
    $parent_select .= '</select>' . LB;

    $order_select = '<select id="menuorder" name="menuorder">' . LB;
    $order_select .= '<option value="0">' . $LANG_MENU01['first_position'] . '</option>' . LB;

    $orderRows = array();
    $result = DB_query("SELECT id,element_label,element_order FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND pid=0 ORDER BY element_order ASC, id ASC");
    while ($row = DB_fetchArray($result)) {
        $orderRows[] = $row;
    }
    $lastOrderIndex = count($orderRows) - 1;
    foreach ($orderRows as $orderIndex => $row) {
        $order_select .= '<option value="' . (int) $row['id'] . '"';
        if ($orderIndex === $lastOrderIndex) {
            $order_select .= ' selected="selected"';
        }
        $order_select .= '>' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
    }
    $order_select .= '</select>' . LB;


    // build group select

    $rootUser = DB_getItem($_TABLES['group_assignments'],'ug_uid','ug_main_grp_id=1');

    $usergroups = SEC_getUserGroups($rootUser);
    $usergroups[$LANG_MENU01['non-logged-in']] = 998;
    ksort($usergroups);
    $allUsersGroupId = (int) DB_getItem($_TABLES['groups'], 'grp_id', "grp_name='All Users'");
    $group_select = '<select id="group" name="group">' . LB;

    foreach ($usergroups as $groupLabel => $groupId) {
        $groupId = (int) $groupId;
        $group_select .= '<option value="' . $groupId . '"';
        if ($groupId === $allUsersGroupId) {
            $group_select .= ' selected="selected"';
        }
        $group_select .= '>' . MENU_escapeHTML($groupLabel) . '</option>' . LB;
    }
    $group_select .= '</select>' . LB;
    
    $T = COM_newTemplate(CTL_plugin_templatePath('menu'));
    $T->set_var('security_token_input', MENU_adminTokenInput());
    $T->set_file(array('admin' => 'createelement.thtml'));

    $T->set_var(array(
        'site_admin_url'    => $_CONF['site_admin_url'],
        'site_url'          => $_CONF['site_url'],
        'form_action'       => $_CONF['site_admin_url'] . '/plugins/menu/index.php',
        'birdseed'          => '<a href="' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/index.php">' . MENU_escapeHTML($LANG_MENU01['menu_list']) . '</a> :: <a href="' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/index.php?mode=menu&amp;menu=' . (int) $menu_id . '">' . $safeMenuName . '</a> :: ' . MENU_escapeHTML($LANG_MENU01['create_element']),
        'menuname'          => $safeMenuName,
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
        'LANG_MENU01[same_window]'   => $LANG_MENU01['same_window'],
        'LANG_MENU01[new_window]'    => $LANG_MENU01['new_window'],
    ));

    $T->parse('output', 'admin');
    $retval .= $T->finish($T->get_var('output'));
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}
