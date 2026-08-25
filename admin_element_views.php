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
    global $_CONF, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN, $LANG_ADMIN,
           $_MENU_CONF, $Menus, $_SCRIPTS;

    $_SCRIPTS->setJavaScriptLibrary('jquery');
    $_SCRIPTS->setJavaScriptFile('menu_tablednd', '/admin/plugins/menu/js/tablednd_0_6.js');
    $_SCRIPTS->setJavaScriptFile('menu_order_handle', '/admin/plugins/menu/js/menu-order-handle.js');

    $retval = '';


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
        $menu_select .= '<option value="' . $menu['menu_id'].'"' . ($menu['menu_id'] == $menu_id ? ' selected="selected"' : '') . '>' . MENU_escapeHTML($menu['menu_name']) .'</option>' . LB;
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

    $type_select = '<select id="menutype" name="menutype" onChange="toggleFields();">' . LB;
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
    $group_select = '<select id="group" name="group">' . LB;

    for ($i = 0; $i < count($usergroups); $i++) {
        $group_select .= '<option value="' . $usergroups[key($usergroups)] . '"';
        $group_select .= '>' . MENU_escapeHTML(key($usergroups)) . '</option>' . LB;
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

    $type_select = '<select id="menutype" name="menutype" onChange="toggleFields();">' . LB;
    $allowedTypes = MENU_getAllowedElementTypes(
        $LANG_MENU_TYPES,
        $Menus[$menu_id]['menu_type'],
        in_array('staticpages', $_PLUGINS),
        $Menus[$menu_id]['elements'][$mid]->type,
        true
    );
    foreach ($allowedTypes as $typeId => $typeLabel) {
        $type_select .= '<option value="' . (int) $typeId . '"';
        $type_select .= ($Menus[$menu_id]['elements'][$mid]->type == $typeId ? ' selected="selected"' : '')
            . '>' . MENU_escapeHTML($typeLabel) . '</option>' . LB;
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
        $plugin_select .= '<option value="'.MENU_escapeHTML($Menus[$menu_id]['elements'][$mid]->subtype).'" selected="selected">'.$LANG_MENU01['disabled_plugin'].'</option>'.LB;
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
            $sp_select .= '<option value="' . $sp_id . '"' . ($Menus[$menu_id]['elements'][$mid]->subtype == $sp_id ? ' selected="selected"' : '') . '>' . MENU_escapeHTML($label) . '</option>' . LB;
        }
        $sp_select .= '</select>' . LB;
    }

    //Topics
    $topic_select = '<select id="topicname" name="topicname">' . LB;
    $sql = "SELECT tid,topic FROM {$_TABLES['topics']} ORDER BY topic";
    $result = DB_query($sql);
    while (list ($tid, $topic) = DB_fetchArray($result)) {
        $topic_select .= '<option value="' . $tid . '"' . ($Menus[$menu_id]['elements'][$mid]->subtype == $tid ? ' selected="selected"' : '') . '>' . MENU_escapeHTML($topic) . '</option>' . LB;
    }
    $topic_select .= '</select>' . LB;

    $parent_select = '<select id="pid" name="pid">' . LB;
    $parent_select .= '<option value="0">' . $LANG_MENU01['top_level'] . '</option>' . LB;
    $result = DB_query("SELECT id,element_label FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND element_type=1 ORDER BY element_order ASC, id ASC");
    while ($row = DB_fetchArray($result)) {
        if ((int) $row['id'] === (int) $mid) {
            continue;
        }
        $parent_select .= '<option value="' . (int) $row['id'] . '" '
            . ($Menus[$menu_id]['elements'][$mid]->pid == $row['id'] ? 'selected="selected"' : '')
            . '>' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
    }
    $parent_select .= '</select>' . LB;

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
        $group_select .= '>' . MENU_escapeHTML(key($usergroups)) . '</option>' . LB;
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
            $order_select .= '<option value="' . $row['id'] . '"' . ($Menus[$menu_id]['elements'][$mid]->order == $test_order ? ' selected="selected"' : '') . '>' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
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

    $legacyColorKeys = array(
        'main_menu_bg_color', 'main_menu_hover_bg_color',
        'main_menu_text_color', 'main_menu_hover_text_color',
        'submenu_text_color', 'submenu_hover_text_color',
        'submenu_background_color', 'submenu_hover_bg_color',
        'submenu_highlight_color', 'submenu_shadow_color',
    );
    foreach ($legacyColorKeys as $colorKey) {
        $menuConfig[$colorKey] = MENU_cssColor($menuConfig[$colorKey]);
    }

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
        $group_select .= '>' . MENU_escapeHTML(key($usergroups)) . '</option>' . LB;
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
        'menu_name'         => MENU_escapeHTML($Menus[$mid]['menu_name']),
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
        'menu_bg_preview'           => MENU_legacyImagePreview($menuConfig['menu_bg_filename'], true),
        'menu_hover_preview'        => MENU_legacyImagePreview($menuConfig['menu_hover_filename'], true),
        'menu_parent_preview'       => MENU_legacyImagePreview($menuConfig['menu_parent_filename'], false),
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
