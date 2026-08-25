<?php

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Menu administration mutations.
 *
 * State-changing helpers live here so admin/index.php can remain focused on
 * routing and page composition. Request authorization/CSRF enforcement stays
 * centralized in admin_security.php.
 */

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
    MENU_invalidateRuntimeCache(true);

    return;
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
    $menunameSql = MENU_dbEscape($menuname);
    $sqlDataValues = "'$menunameSql',$menutype,$menuactive,$menugroup";
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
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'use_images','0'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_bg_filename',''");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_hover_filename',''");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_parent_filename',''");
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
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'use_images','0'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_bg_filename',''");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_hover_filename',''");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_parent_filename',''");
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
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'use_images','0'");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_bg_filename',''");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_hover_filename',''");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_parent_filename',''");
            DB_save($_TABLES['menu_config'],"menu_id,conf_name,conf_value","$menu_id,'menu_alignment','1'");
            break;
    }

    MENU_invalidateRuntimeCache(true);
}

/*
 * Saves an edited menu element
 */

function MENU_saveEditMenuElement ( ) {
    global $_TABLES, $Menus;
    
    $id      = (int) Geeklog\Input::fPost('id');
    $menu_id = (int) Geeklog\Input::fPost('menu');
    $pid     = (int) Geeklog\Input::fPost('pid');
    $label   = trim(strip_tags(COM_checkWords(Geeklog\Input::post('menulabel'))));
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
            $subtype = trim(Geeklog\Input::fPost('menuurl'));
            if ($subtype !== ''
                && strpos($subtype, "http") !== 0
                && strpos($subtype, "%site") === false
                && $subtype[0] != '#') {
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

    if ($url !== ''
        && strpos($url, "http") !== 0
        && strpos($url, "%site") === false
        && $url[0] != '#') {
        $url = 'http://' . $url;
    }

    $group_id = (int) Geeklog\Input::fPost('group');
    $aid      = (int) Geeklog\Input::fPost('menuorder');
    $aorder   = (int) DB_getItem($_TABLES['menu_elements'], 'element_order', 'id=' . $aid . ' AND menu_id=' . $menu_id);
    $neworder = $aorder + 1;

    $labelSql = MENU_dbEscape($label);
    $subtypeSql = MENU_dbEscape($subtype);
    $urlSql = MENU_dbEscape($url);
    $targetSql = MENU_dbEscape($target);
    $sql = "UPDATE {$_TABLES['menu_elements']} SET pid=$pid, element_order=$neworder, element_label='$labelSql', element_type=$type, element_subtype='$subtypeSql', element_active=$active, element_url='$urlSql', element_target='$targetSql', group_id=$group_id WHERE id=$id AND menu_id=$menu_id";

    DB_query($sql);
    $Menus[$menu_id]['elements'][$pid]->reorderMenu();
    MENU_invalidateRuntimeCache(true);
}

/**
* Enable and Disable block
*/
function MENU_changeActiveStatusElement($bid_arr = null)
{
    global $_TABLES;

    $menuId = (int) Geeklog\Input::fPost('menu');
    $mid = (int) Geeklog\Input::fPost('mid');
    $active = (int) Geeklog\Input::fPost('active');
    $active = $active === 1 ? 1 : 0;

    if ($menuId > 0 && $mid > 0) {
        DB_query("UPDATE {$_TABLES['menu_elements']} SET element_active=" . $active
            . " WHERE id=" . $mid . " AND menu_id=" . $menuId);
    }

    MENU_invalidateRuntimeCache(true);
}

/**
* Enable and Disable block
*/
function MENU_changeActiveStatusMenu($bid_arr = null)
{
    global $_TABLES;

    $menuId = (int) Geeklog\Input::fPost('id');
    $active = (int) Geeklog\Input::fPost('active');
    $active = $active === 1 ? 1 : 0;

    if ($menuId > 0) {
        DB_query("UPDATE {$_TABLES['menu']} SET menu_active=" . $active . " WHERE id=" . $menuId);
    }

    MENU_invalidateRuntimeCache(true);
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
    $sql = "DELETE FROM " . $_TABLES['menu_elements'] . " WHERE id=" . $id . " AND menu_id=" . (int) $menu_id;
    DB_query($sql);
}

/**
 * Delete one element and all of its descendants, then normalize the root
 * branch order and invalidate runtime caches once.
 *
 * @param int $id
 * @param int $menuId
 */
function MENU_deleteElementTree($id, $menuId)
{
    global $Menus;

    $id = (int) $id;
    $menuId = (int) $menuId;
    if ($id <= 0 || $menuId <= 0) {
        return;
    }

    MENU_deleteChildElements($id, $menuId);
    if (isset($Menus[$menuId]['elements'][0])) {
        $Menus[$menuId]['elements'][0]->reorderMenu();
    }
    MENU_invalidateRuntimeCache(true);
}

/**
 * Toggle whether legacy rendering/configuration is enabled for one menu.
 *
 * @param int $menuId
 * @param int $active
 */
function MENU_setMenuConfigEnabled($menuId, $active)
{
    global $_TABLES;

    $menuId = (int) $menuId;
    $active = ((int) $active === 1) ? 1 : 0;
    if ($menuId <= 0) {
        return;
    }

    DB_query("UPDATE {$_TABLES['menu_config']} SET enabled=" . $active
        . " WHERE menu_id=" . $menuId);
    MENU_invalidateRuntimeCache(true);
}

/**
 * Persist a drag/drop order previously validated by admin_security.php.
 *
 * @param int    $menuId
 * @param string $ordersString
 */
function MENU_saveElementOrder($menuId, $ordersString)
{
    global $_TABLES, $Menus;

    $menuId = (int) $menuId;
    if ($menuId <= 0) {
        return;
    }

    $orders = explode('&', (string) $ordersString);
    $elementIds = array();
    foreach ($orders as $item) {
        $parts = explode('=', $item, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $rowId = rawurldecode($parts[1]);
        if (!preg_match('/^mid_([1-9][0-9]*)$/', $rowId, $matches)) {
            continue;
        }
        $mid = (int) $matches[1];
        if (isset($Menus[$menuId]['elements'][$mid])) {
            $elementIds[] = $mid;
        }
    }

    foreach ($elementIds as $key => $mid) {
        $newOrder = ((int) $key + 1) * 10;
        DB_query("UPDATE {$_TABLES['menu_elements']} SET element_order=" . $newOrder
            . " WHERE menu_id=" . $menuId . " AND id=" . (int) $mid);
    }

    MENU_invalidateRuntimeCache(false);
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

    $legacyColorKeys = array(
        'main_menu_bg_color', 'main_menu_hover_bg_color',
        'main_menu_text_color', 'main_menu_hover_text_color',
        'submenu_text_color', 'submenu_hover_text_color',
        'submenu_background_color', 'submenu_hover_bg_color',
        'submenu_highlight_color', 'submenu_shadow_color',
    );
    foreach ($legacyColorKeys as $colorKey) {
        $mc[$colorKey] = MENU_cssColor($mc[$colorKey]);
    }
    $mc['menu_alignment'] = ($mc['menu_alignment'] === 1) ? 1 : 0;
    $mc['use_images'] = ($mc['use_images'] === 1) ? 1 : 0;
    $menutype                         = (int) Geeklog\Input::fPost('menutype');
    $menuactive                       = (int) Geeklog\Input::fPost('menuactive');
    $menugroup                        = (int) Geeklog\Input::fPost('group');

    $menuname   = $Menus[$menu_id]['menu_name'];

    $sqlFieldList  = 'id,menu_name,menu_type,menu_active,group_id';
    $menunameSql = MENU_dbEscape($menuname);
    $sqlDataValues = "$menu_id,'$menunameSql',$menutype,$menuactive,$menugroup";
    DB_save($_TABLES['menu'], $sqlFieldList, $sqlDataValues);

    foreach ($mc AS $name => $value) {
        $nameSql = MENU_dbEscape($name);
        $valueSql = MENU_dbEscape($value);
        DB_save($_TABLES['menu_config'], 'menu_id,conf_name,conf_value', "$menu_id,'$nameSql','$valueSql'");
    }

    // Optional images belong only to the legacy renderer. Validate them from
    // their actual file contents and store them in the site-specific Geeklog
    // image directory. Existing images are preserved when no valid replacement
    // is uploaded.
    $imageUploads = array(
        'bgimg' => array('prefix' => 'menu_bg', 'config' => 'menu_bg_filename'),
        'hvimg' => array('prefix' => 'menu_hover_bg', 'config' => 'menu_hover_filename'),
        'piimg' => array('prefix' => 'menu_parent', 'config' => 'menu_parent_filename'),
    );

    foreach ($imageUploads as $field => $settings) {
        $file = isset($_FILES[$field]) ? $_FILES[$field] : array();
        $configName = $settings['config'];
        $oldFilename = isset($Menus[$menu_id]['config'][$configName])
            ? $Menus[$menu_id]['config'][$configName]
            : '';
        $newFilename = MENU_storeLegacyImageUpload($file, $settings['prefix'], $oldFilename);

        if ($newFilename !== '') {
            $configNameSql = MENU_dbEscape($configName);
            $newFilenameSql = MENU_dbEscape($newFilename);
            DB_save(
                $_TABLES['menu_config'],
                'menu_id,conf_name,conf_value',
                "$menu_id,'$configNameSql','$newFilenameSql'"
            );
        }
    }
    MENU_invalidateRuntimeCache(true);
    return;
}
