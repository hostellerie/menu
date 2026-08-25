<?php
/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.1                                                           |
// +---------------------------------------------------------------------------+
// | classMenuElement.php                                                      |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012 by the following authors:                              |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
// +---------------------------------------------------------------------------+
// | Copyright (C)  2008-2011 by the following authors:                        |
// |                                                                           |
// | Mark R. Evans          mark AT glfusion DOT org                           |
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

// this file can't be used on its own
if (!defined ('VERSION')) {
    die ('This file can not be used on its own.');
}

class mbElement {
    var $id;                // item id
    var $pid;               // parent id (0 = root level)
    var $menu_id;           // which menu does this belong to?
    var $label;             // text label for the menu item
    var $type;              // menu element type (submenu header, gl core, plugin, etc)
    var $subtype;           // generic subtype holder, contents depend on $type
    var $order;             // order of display (1 to ....)
    var $active;            // active entry or inactive
    var $url;               // URL to go on click
    var $target;            // Target window for URL
    var $group_id;          // group membership
    var $access;            // derived access level
    var $children;          // this elements child elements
    var $hidden;            // I have no idea -- Joe

    public function __construct() {
        $this->children         = array();
        $this->id               = 0;
        $this->menu_id          = 0;
        $this->group_id         = 0;
    }

    function constructor( $element, $meadmin, $root, $groups ) {
        $this->id               = isset($element['id']) ? $element['id'] : null;
        $this->pid              = $element['pid'];
        $this->menu_id          = $element['menu_id'];
        $this->label            = (!empty($element['element_label']) && $element['element_label'] != ' ') ? $element['element_label'] : '';
        $this->type             = $element['element_type'];
        $this->subtype          = $element['element_subtype'];
        $this->order            = $element['element_order'];
        $this->active           = $element['element_active'];
        $this->url              = (!empty($element['element_url']) && $element['element_url'] != ' ') ? $element['element_url'] : '';
        $this->target           = $element['element_target'];
        $this->group_id         = $element['group_id'];
        $this->setAccessRights($meadmin,$root,$groups);
    }

    function setChild( $id ) {
        $this->children[$id] = $id;
    }

    function saveElement( ) {
        global $_TABLES;

        $id       = (int) $this->id;
        $pid      = (int) $this->pid;
        $menuId   = (int) $this->menu_id;
        $type     = (int) $this->type;
        $order    = (int) $this->order;
        $active   = (int) $this->active;
        $groupId  = (int) $this->group_id;
        $label    = MENU_dbEscape($this->label);
        $subtype  = MENU_dbEscape($this->subtype);
        $url      = MENU_dbEscape($this->url);
        $target   = MENU_dbEscape($this->target);

        $sqlFieldList  = 'id,pid,menu_id,element_label,element_type,element_subtype,element_order,element_active,element_url,element_target,group_id';
        $sqlDataValues = $id . ',' . $pid . ',' . $menuId . ",'" . $label . "'," . $type . ",'" . $subtype . "'," . $order . ',' . $active . ",'" . $url . "','" . $target . "'," . $groupId;
        DB_save($_TABLES['menu_elements'], $sqlFieldList, $sqlDataValues);
    }

    function reorderMenu( ) {
        global $_TABLES, $Menus;

        $pid = intval($this->id);
        $menu_id = intval($this->menu_id);

        $orderCount = 10;

        $sql = "SELECT id,`element_order` FROM {$_TABLES['menu_elements']} WHERE menu_id=".$menu_id." AND pid=" . $pid . " ORDER BY `element_order` ASC";
        $result = DB_query($sql);
        while ($M = DB_fetchArray($result)) {
            $M['element_order'] = $orderCount;
            $orderCount += 10;
            DB_query("UPDATE {$_TABLES['menu_elements']} SET `element_order`=" . (int) $M['element_order'] . " WHERE menu_id=" . $menu_id . " AND id=" . (int) $M['id']);
        }
    }


    function createElementID( $menu_id ) {
        global $_TABLES;

        $sql = "SELECT MAX(id) + 1 AS next_id FROM " . $_TABLES['menu_elements'];
        $result = DB_query( $sql );
        $row = DB_fetchArray( $result );
        $id = $row['next_id'];
        if ( $id < 1 ) {
            $id = 1;
        }
        if ( $id == 0 ) {
            COM_errorLog("Site Tailor: Error - Returned 0 as element id");
            $id = 1;
        }
        return $id;
    }


    function setAccessRights( $meadmin, $root, $groups ) {
        global $_USER, $Menus;

        if ($meadmin || $root) {
            $this->access = 3;
        } else {
            if ( $this->group_id == 998 ) {
                if( COM_isAnonUser() ) {
                    $this->access = 3;
                } else {
                    $this->access = 0;
                }
            } else {
                if ( in_array( $this->group_id, $groups ) ) {
                    $this->access = 3;
                }
            }
        }
    }

    function getChildren() {
        return (array_keys($this->children));
    }

    function getChildcount() {
        global $Menus;

        $numChildren = 0;
        $children = $this->getChildren();
        $x = count($children);
        for ($i=0; $i < $x; $i++ ) {
            if ( $Menus[$this->menu_id]['elements'][$children[$i]]->active > 0 ) {
                if ( $Menus[$this->menu_id]['elements'][$children[$i]]->hidden == 1 ) {
                    if ( $Menus[$this->menu_id]['elements'][$children[$i]]->access == 3 ) {
                        $numChildren++;
                    }
                } else {
                    $numChildren++;
                }
            }
        }
        return $numChildren;
    }

// This will build the actual edit tree line.  It will be a table, where each row

    function editTree( $depth, $count ) {
        global $_CONF, $Menus, $level;
        global $LANG_MENU01, $LANG_MENU_TYPES,$LANG_MENU_GLTYPES,$LANG_MENU_GLFUNCTION;

        $plugin_menus = MENU_PLG_getMenuItems();

        static $count;
        $retval = '';
        $px = ($level - 1 ) * 15;

        if ( $this->label != 'Top Level Menu' ) {

            $style = ($count % 2) + 1;

            $retval .= '<tr id="mid_' . $this->id . '" class="pluginRow' . $style . '" onmouseover="className=\'pluginRollOver\';" onmouseout="className=\'pluginRow' . $style . '\';">';

            $retval .= '<td>';

            $elementDetails = '<b>' . $LANG_MENU01['type'] . ':</b> ' . $LANG_MENU_TYPES[$this->type] . '<br' . XHTML . '>';
            /*switch ($this->type) {
                case 1 :
                    break;
                case 2 :
                    $elementDetails .= '<b>' . $LANG_MENU_TYPES[$this->type] . '</b> ' . $LANG_MENU_GLFUNCTION[$this->subtype] . '<br' . XHTML . '>';
                    break;
                case 3 :
                    $elementDetails .= '<b>' . $LANG_MENU_TYPES[$this->type] . ':</b> ' . $LANG_MENU_GLTYPES[$this->subtype] . '<br' . XHTML . '>';
                    break;
                case 4 :
                    $elementDetails .= '<b>' . $LANG_MENU_TYPES[$this->type] . ':</b> ' . $this->subtype . '<br' . XHTML . '>';
                    break;
                case 5 :
                    $elementDetails .= '<b>' . $LANG_MENU_TYPES[$this->type] . ':</b> UNDEFINED <br' . XHTML . '>';
                    break;
                case 6 :
                    $elementDetails .= '<b>' . $LANG_MENU_TYPES[$this->type] . ':</b> ' . $this->url . '<br' . XHTML . '>';
                    break;
                case 7 :
                    $elementDetails .= '<b>' . $LANG_MENU_TYPES[$this->type] . ':</b> ' . $this->subtype . '<br' . XHTML . '>';
                    break;
                case 9 :
                    $elementDetails .= '<b>' . $LANG_MENU_TYPES[$this->type] . ':</b> ' . $this->subtype . '<br />'   ;
            }*/

            $actionUrl = $_CONF['site_admin_url'] . '/plugins/menu/index.php';
            $tokenInput = MENU_adminTokenInput();
            $moveup = '<form method="post" action="' . $actionUrl . '" style="display:inline">' . $tokenInput . '<input type="hidden" name="mode" value="move"' . XHTML . '><input type="hidden" name="where" value="up"' . XHTML . '><input type="hidden" name="mid" value="' . (int) $this->id . '"' . XHTML . '><input type="hidden" name="menu" value="' . (int) $this->menu_id . '"' . XHTML . '><button type="submit" style="border:0;background:none;padding:0;cursor:pointer">';
            $movedown = '<form method="post" action="' . $actionUrl . '" style="display:inline">' . $tokenInput . '<input type="hidden" name="mode" value="move"' . XHTML . '><input type="hidden" name="where" value="down"' . XHTML . '><input type="hidden" name="mid" value="' . (int) $this->id . '"' . XHTML . '><input type="hidden" name="menu" value="' . (int) $this->menu_id . '"' . XHTML . '><button type="submit" style="border:0;background:none;padding:0;cursor:pointer">';
            $edit = '<a href="' . $_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=edit&amp;mid=' . $this->id . '&amp;menu=' . $this->menu_id . '">';
            $delete = '<form method="post" action="' . $actionUrl . '" style="display:inline" onsubmit="return confirm(\'' . $LANG_MENU01['confirm_delete'] . '\');">' . $tokenInput . '<input type="hidden" name="mode" value="delete"' . XHTML . '><input type="hidden" name="mid" value="' . (int) $this->id . '"' . XHTML . '><input type="hidden" name="menuid" value="' . (int) $this->menu_id . '"' . XHTML . '><button type="submit" style="border:0;background:none;padding:0;cursor:pointer">';
            $info = '<img src="' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/images/info.png" alt="' . MENU_escapeHTML($LANG_MENU01['info']) . '" title="' . MENU_escapeHTML(strip_tags($LANG_MENU01['type'] . ': ' . $LANG_MENU_TYPES[$this->type])) . '"' . XHTML . '>';

            $retval .= "<div style=\"padding:0 5px;margin-left:" . $px . "px;\">" . ($this->type == 1 ? '<b>' : '') . MENU_escapeStoredText($this->label) . ($this->type == 1 ? '</b>' : '') . '</div>' . LB;

            $retval .= '</td>';
            $retval .= '<td class="aligncenter">';
            $retval .= '<form method="post" action="' . $actionUrl . '" style="display:inline">' . $tokenInput . '<input type="hidden" name="mode" value="activate"' . XHTML . '><input type="hidden" name="menu" value="' . (int) $this->menu_id . '"' . XHTML . '><input type="hidden" name="mid" value="' . (int) $this->id . '"' . XHTML . '><input type="hidden" name="active" value="' . ($this->active == 1 ? '0' : '1') . '"' . XHTML . '><input type="checkbox" onclick="this.form.submit()"' . ($this->active == 1 ? ' checked="checked"' : '') . XHTML . '></form>';
            $retval .= '</td>';
            $retval .= '<td class="aligncenter">';
            $retval .= $info;
            $retval .= '</td>';
            $retval .= '<td class="aligncenter">' . $edit . '<img src="' . $_CONF['site_admin_url'] . '/plugins/menu/images/edit.png" alt="' . $LANG_MENU01['edit'] . '"' . XHTML . '></a></td>';
            $retval .= '<td class="aligncenter">' . $delete . '<img src="' . $_CONF['site_admin_url'] . '/plugins/menu/images/delete.png" alt="' . $LANG_MENU01['delete'] . '"' . XHTML . '></button></form></td>';
            $retval .= '<td class="aligncenter">' . $moveup . '<img src="' . $_CONF['site_admin_url'] . '/plugins/menu/images/up.png" alt="' . $LANG_MENU01['move_up'] . '"' . XHTML . '></button></form></td><td class="aligncenter">' . $movedown . '<img src="' . $_CONF['site_admin_url'] . '/plugins/menu/images/down.png" alt="' . $LANG_MENU01['move_down'] . '"' . XHTML . '></button></form></td>';
            $retval .= '</tr>';
            $count++;

        }

        if ( !empty($this->children)) {
            $children = $this->getChildren();
            foreach($children as $child) {
                $level++;
                if ( isset($Menus[$this->menu_id]['elements'][$child]) ) {
                    $retval .= $Menus[$this->menu_id]['elements'][$child]->editTree($depth,$count);
                }
                $level--;
            }
        }
        return $retval;
    }

    function isLastChild() {
        global $Menus;

        $pid = $this->pid;
        $children = $Menus[$this->menu_id]['elements'][$pid]->getChildren();
        $arrayIndex = count($children)-1;
        if ( $this->id == $children[$arrayIndex] ) {
            return true;
        }
        return false;
    }

    function replace_macros() {
        global $_CONF;
        $this->url = str_replace( "%version%", VERSION, $this->url );
        $this->url = str_replace( "%site_url%", $_CONF['site_url'], $this->url );
        $this->url = str_replace( "%site_admin_url%", $_CONF['site_admin_url'], $this->url );
        return;
    }

    function showTree( $depth,$ulclass='',$liclass='',$parentaclass='',$lastclass,$selected='' ) {
        global $_SP_CONF,$_USER, $_TABLES, $LANG01, $LANG29, $_CONF,$meLevel,
               $_DB_dbms,$_GROUPS, $config,$Menus, $_TOPICS, $_PLUGINS;

        $oulclass       = $ulclass;
        $oliclass       = $liclass;
        $oparentaclass  = $parentaclass;
        $olastclass     = $lastclass;

        if ( $ulclass != '' )
            $ulclass = $ulclass . $this->menu_id;
        if ( $liclass != '' )
            $liclass = $liclass . $this->menu_id;
        if ( $parentaclass != '' )
            $parentaclass = $parentaclass . $this->menu_id;
        if ( $lastclass != '' )
            $lastclass = $lastclass . $this->menu_id;

        $retval = '';
        $menu = '';

        if ( $this->active != 1 && $this->id != 0 ) {
            return '';
        }

        if (isset($_REQUEST['topic']) ){
            $topic = COM_applyFilter($_REQUEST['topic']);
        } else {
            $topic = '';
        }

        if( COM_isAnonUser() ) {
            $anon = 1;
        } else {
            $anon = 0;
        }
        $allowed = true;

        if ( $this->group_id != 998 && $this->id != 0 && !SEC_inGroup($this->group_id) ) {
            return '';
        }

        if ( $this->group_id == 1 && !isset($_GROUPS['Root']) ) {
            return '';
        }

        // need to build the URL
        switch ( $this->type ) {
            case '1' : // subtype - do nothing
                $this->replace_macros();
                break;
            case '2' : // Geeklog action
                switch ($this->subtype) {
                    case 0: // home
                        $this->url = $_CONF['site_url'] . '/';
                        break;
                    case 1: // contribute
                        if( empty( $topic )) {
                            $this->url = $_CONF['site_url'] . '/submit.php?type=story';
                        } else {
                            $this->url = $_CONF['site_url']
                                 . '/submit.php?type=story&amp;topic=' . $topic;
                        }
                        $label = $LANG01[71];
                        if( $anon && ( $_CONF['loginrequired'] || $_CONF['submitloginrequired'] )) {
                            $allowed = false;
                        }
                        break;
                    case 2: // directory
                        $this->url = $_CONF['site_url'] . '/directory.php';
                        if( !empty( $topic )) {
                            $this->url = COM_buildUrl( $this->url . '?topic='
                                                 . urlencode( $topic ));
                        }
                        if( $anon && ( $_CONF['loginrequired'] ||
                                $_CONF['directoryloginrequired'] )) {
                            $allowed = false;
                        }
                        break;
                    case 3: // prefs
                        $this->url = $_CONF['site_url'] . '/usersettings.php?mode=edit';
                        if( $anon && ( $_CONF['loginrequired'] ||
                                $_CONF['profileloginrequired'] )) {
                            $allowed = false;
                        }
                        break;
                    case 4: // search
                        $this->url = $_CONF['site_url'] . '/search.php';
                        if( $anon && ( $_CONF['loginrequired'] ||
                                $_CONF['searchloginrequired'] )) {
                            $allowed = false;
                        }
                        break;
                    case 5: // stats
                        $this->url = $_CONF['site_url'] . '/stats.php';
                        if ( !SEC_hasRights('stats.view') ) {
                            $allowed = false;
                        }

                        break;
                    default : // unknown?
                        $this->url = $_CONF['site_url'] . '/';
                        break;
                }
                break;
            case '3' : // Geeklog menu
                switch ($this->subtype) {
                    case 1 : // user menu
                        if ( $this->id != 0 && $this->access > 0 && $parentaclass != '' ) {
                            $menu .= "<li>" . '<a class="' . $parentaclass . '" name="'.$parentaclass.'" href="#">' . MENU_escapeStoredText($this->label) . '</a>' . LB;
                        } else {
                            $menu .= "<li>" . '<a href="#">' . MENU_escapeStoredText($this->label) . '</a></li>' . LB;
                        }
                        if ( $this->id == 0 && $ulclass != '' ) {
                            $menu .= '<ul class="' . $ulclass . '">' . LB;
                        } else {
                            $menu .= '<ul>' . LB;
                        }
                        if( !empty( $_USER['uid'] ) && ( $_USER['uid'] > 1 )) {
                            $plugin_options = PLG_getAdminOptions();
                            $num_plugins = count($plugin_options);
                            if (SEC_isModerator() OR
                                    SEC_hasRights('story.edit,block.edit,topic.edit,user.edit,plugin.edit,user.mail,syndication.edit', 'OR') OR
                                    ($num_plugins > 0))
                            {
                                $url = $_CONF['site_admin_url'] . '/index.php';
                                $label =  $LANG29[34];
                                $menu .= '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                            }
                            // what's our current URL?
                            $thisUrl = COM_getCurrentURL();

                            // This function will show the user options for all installed plugins
                            // (if any)

                            $plugin_options = PLG_getUserOptions();
                            $nrows = count( $plugin_options );

                            for( $i = 0; $i < $nrows; $i++ ) {
                                $plg = current( $plugin_options );
                                $label = $plg->adminlabel;
                                if( !empty( $plg->numsubmissions )) {
                                    $label .= ' (' . $plg->numsubmissions . ')';
                                }
                                $url = $plg->adminurl;
                                $menu .= '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                next( $plugin_options );
                            }
                            $url = $_CONF['site_url'] . '/usersettings.php?mode=edit';
                            $label = $LANG01[48];
                            $menu .= '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                            $url = $_CONF['site_url'] . '/users.php?mode=logout';
                            $label = $LANG01[19];
                            $menu .= '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                        } else {
                            $url = $_CONF['site_url'] . '/users.php?mode=login';
                            $label = 'Login';
                            $menu .= '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                        }
                        $menu .= '</ul>' . LB . '</li>' . LB;
                        break;
                    case 2 : // admin menu

                        if( !empty( $_USER['username'] )) {

                            /*
                             * Set the initial $menu opening
                             */

                            if ( $this->id != 0 && $this->access > 0 && $parentaclass != '' ) {
                                $menu .= "<li>" . '<a class="' . $parentaclass . '" name="'.$parentaclass.'" href="#">' . MENU_escapeStoredText($this->label) . '</a>' . LB;
                            } else {
                                $menu .= "<li>" . '<a href="#">' . MENU_escapeStoredText($this->label) . '</a></li>' . LB;
                            }
                            if ( $this->id == 0 && $ulclass != '' ) {
                                $menu .= '<ul class="' . $ulclass . '">' . LB;
                            } else {
                                $menu .= '<ul>' . LB;
                            }

                            $link_array = array();

                            /*
                             * Get all plugin menu options
                             */

                            $plugin_options = PLG_getAdminOptions();
                            $num_plugins = count( $plugin_options );

                            /*
                             * Build the standard Geeklog admin options
                             */

                            /*
                             * Story moderation entry
                             */

                            if( SEC_isModerator() OR SEC_hasRights( 'story.edit,block.edit,topic.edit,user.edit,plugin.edit,user.mail,syndication.edit', 'OR' ) OR ( $num_plugins > 0 )) {
                                // what's our current URL?
                                $thisUrl = COM_getCurrentURL();

                                $topicsql = '';
                                if( SEC_isModerator() || SEC_hasRights( 'story.edit' )) {
                                    $tresult = DB_query( "SELECT tid FROM {$_TABLES['topics']}"
                                                         . COM_getPermSQL() );
                                    $trows = DB_numRows( $tresult );
                                    if( $trows > 0 ) {
                                        $tids = array();
                                        for( $i = 0; $i < $trows; $i++ ) {
                                            $T = DB_fetchArray( $tresult );
                                            $tids[] = $T['tid'];
                                        }
                                        if( sizeof( $tids ) > 0 ) {
                                            //$topicsql = " (ta.tid IN ('" . implode( "','", $tids ) . "'))";
                                            // Geeklog 2.0
											$topicsql = " AND (ta.tid IN ('" . implode( "','", $tids ) . "'))";
                                        }
                                    }
                                }

                                $modnum = 0;
                                if( SEC_hasRights( 'story.edit,story.moderate', 'OR' ) ||
									(($_CONF['commentsubmission'] == 1) && SEC_hasRights('comment.moderate')) ||
									(( $_CONF['usersubmission'] == 1 ) && SEC_hasRights( 'user.edit,user.delete' ))) {
									
                                    if( SEC_hasRights( 'story.moderate' )) {
                                        if( empty( $topicsql )) {
                                            $modnum += DB_count( $_TABLES['storysubmission'] );
                                        } else {
                                            //$sresult = DB_query( "SELECT COUNT(*) AS count FROM {$_TABLES['storysubmission']} WHERE" . $topicsql );
                                            //Geeklog 2.0
											$sresult = DB_query( "SELECT COUNT(DISTINCT sid) AS count FROM {$_TABLES['storysubmission']}, {$_TABLES['topic_assignments']} ta WHERE ta.type = 'article' AND ta.id = sid " . $topicsql );
                                            $S = DB_fetchArray( $sresult );
                                            $modnum += $S['count'];
                                        }
                                    }
                                    if(( in_array('staticpages', $_PLUGINS) && $_CONF['listdraftstories'] == 1 ) && SEC_hasRights( 'story.edit' )) {
                                        $sql = "SELECT COUNT(*) AS count FROM {$_TABLES['stories']}, {$_TABLES['topic_assignments']} ta WHERE (draft_flag = 1)";
                                        if( !empty( $topicsql )) {
                                            $sql .= $topicsql;
                                        }
                                        $result = DB_query( $sql . COM_getPermSQL( 'AND', 0, 3 ));
                                        $A = DB_fetchArray( $result );
                                        $modnum += $A['count'];
                                    }
									
									if (($_CONF['commentsubmission'] == 1) && SEC_hasRights('comment.moderate')) {
										$modnum += DB_count($_TABLES['commentsubmissions']);
									}

                                    if( $_CONF['usersubmission'] == 1 ) {
                                        if( SEC_hasRights( 'user.edit' ) && SEC_hasRights( 'user.delete' )) {
                                            $modnum += DB_count( $_TABLES['users'], 'status', '2' );
                                        }
                                    }
                                }
                                // now handle submissions for plugins
                                $modnum += PLG_getSubmissionCount();

                                if( SEC_hasRights( 'story.edit' )) {
                                    $url = $_CONF['site_admin_url'] . '/story.php';
                                    $label = $LANG01[11];
                                    if( empty( $topicsql )) {
                                        $numstories = DB_count( $_TABLES['stories'] );
                                    } else {
                                        //$nresult = DB_query( "SELECT COUNT(*) AS count from {$_TABLES['stories']} WHERE" . $topicsql . COM_getPermSql( 'AND' ));
                                        // Geeklog 2.0
										$nresult = DB_query( "SELECT COUNT(DISTINCT sid) AS count FROM {$_TABLES['stories']}, {$_TABLES['topic_assignments']} ta WHERE ta.type = 'article' AND ta.id = sid " . $topicsql . COM_getPermSql( 'AND' ));
                                        $N = DB_fetchArray( $nresult );
                                        $numstories = $N['count'];
                                    }

                                    $label .= ' (' . COM_numberFormat($numstories) . ')';
                                    $link_array[$LANG01[11]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if( SEC_hasRights( 'block.edit' )) {
                                    $result = DB_query( "SELECT COUNT(*) AS count FROM {$_TABLES['blocks']}" . COM_getPermSql());
                                    list( $count ) = DB_fetchArray( $result );

                                    $url = $_CONF['site_admin_url'] . '/block.php';
                                    $label = $LANG01[12] . ' (' . COM_numberFormat($count) . ')';
                                    $link_array[$LANG01[12]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if( SEC_hasRights( 'topic.edit' )) {
                                    $result = DB_query( "SELECT COUNT(*) AS count FROM {$_TABLES['topics']}" . COM_getPermSql());
                                    list( $count ) = DB_fetchArray( $result );

                                    $url = $_CONF['site_admin_url'] . '/topic.php';
                                    $label = $LANG01[13] . ' (' . COM_numberFormat($count) . ')';
                                    $link_array[$LANG01[13]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if( SEC_hasRights( 'user.edit' )) {
                                    $url = $_CONF['site_admin_url'] . '/user.php';
                                    $label = $LANG01[17] . ' (' . COM_numberFormat(DB_count($_TABLES['users']) -1) . ')';
                                    $link_array[$LANG01[17]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if( SEC_hasRights( 'group.edit' )) {
                                    if (SEC_inGroup('Root')) {
                                        $grpFilter = '';
                                    } else {
                                        $thisUsersGroups = SEC_getUserGroups ();
                                        $grpFilter = 'WHERE (grp_id IN (' . implode (',', $thisUsersGroups) . '))';
                                    }
                                    $result = DB_query( "SELECT COUNT(*) AS count FROM {$_TABLES['groups']} $grpFilter;" );
                                    $A = DB_fetchArray( $result );

                                    $url = $_CONF['site_admin_url'] . '/group.php';
                                    $label = $LANG01[96] . ' (' . COM_numberFormat($A['count']) . ')';
                                    $link_array[$LANG01[96]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if( SEC_hasRights( 'user.mail' )) {
                                    $url = $_CONF['site_admin_url'] . '/mail.php';
                                    $label = $LANG01[105] . ' (N/A)';
                                    $link_array[$LANG01[105]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if(( $_CONF['backend'] == 1 ) && SEC_hasRights( 'syndication.edit' )) {
                                    $url = $_CONF['site_admin_url'] . '/syndication.php';
                                    $label = $LANG01[38] . ' (' . COM_numberFormat(DB_count($_TABLES['syndication'])) . ')';
                                    $link_array[$LANG01[38]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if(( $_CONF['trackback_enabled'] || $_CONF['pingback_enabled'] || $_CONF['ping_enabled'] ) && SEC_hasRights( 'story.ping' )) {
                                    $url = $_CONF['site_admin_url'] . '/trackback.php';
                                    $label = $LANG01[116] . ' (' . COM_numberFormat( DB_count( $_TABLES['pingservice'] )) . ')';
                                    $link_array[$LANG01[116]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }
                                if( SEC_hasRights( 'plugin.edit' )) {
                                    $url = $_CONF['site_admin_url'] . '/plugins.php';
                                    $label = $LANG01[77] . ' (' . COM_numberFormat( DB_count( $_TABLES['plugins'] )) . ')';
                                    $link_array[$LANG01[77]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }
                                if (SEC_inGroup('Root')) {
                                    $url = $_CONF['site_admin_url'] . '/configuration.php';
                                    $label = $LANG01[129] . ' (' . COM_numberFormat(count($config->_get_groups())) . ')';
                                    $link_array[$LANG01[129]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                // This will show the admin options for all installed plugins (if any)

                                for( $i = 0; $i < $num_plugins; $i++ ) {
                                    $plg = current( $plugin_options );

                                    $url = $plg->adminurl;
                                    $label = $plg->adminlabel;

                                    if( empty( $plg->numsubmissions )) {
                                        $label .= ' (N/A)';
                                    } else {
                                        $label .= ' (' . COM_numberFormat( $plg->numsubmissions ) . ')';
                                    }
                                    $link_array[$plg->adminlabel] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;

                                    next( $plugin_options );
                                }

                                if(isset($_CONF['allow_mysqldump']) && ( $_CONF['allow_mysqldump'] == 1 ) AND ( $_DB_dbms == 'mysql' ) AND SEC_inGroup( 'Root' )) {
                                    $url = $_CONF['site_admin_url'] . '/database.php';
                                    $label = $LANG01[103] . ' (N/A)';
                                    $link_array[$LANG01[103]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if( $_CONF['link_documentation'] == 1 ) {
                                    $doclang = COM_getLanguageName();
                                    if ( @file_exists($_CONF['path_html'] . 'docs/' . $doclang . '/index.html') ) {
                                        $docUrl = $_CONF['site_url'].'/docs/'.$doclang.'/index.html';
                                    } else {
                                        $docUrl = $_CONF['site_url'].'/docs/english/index.html';
                                    }
                                    $url = $docUrl;
                                    $label = $LANG01[113] . ' (N/A)';
                                    $link_array[$LANG01[113]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if( SEC_inGroup( 'Root' )) {
                                    $url = 'http://geeklog.net/versionchecker.php?version=' . VERSION;
                                    $label = $LANG01[107] . ' (' . VERSION . ')';
                                    $link_array[$LANG01[107]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }
                                if (SEC_isModerator()) {
                                    $url = $_CONF['site_admin_url'] . '/moderation.php';
                                    $label = $LANG01[10] . ' (' . COM_numberFormat( $modnum ) . ')';
                                    $link_array[$LANG01[10]] = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                }

                                if( $_CONF['sort_admin'] ) {
                                    uksort( $link_array, 'strcasecmp' );
                                }
                                // C&C entry
                                $url = $_CONF['site_admin_url'] . '/index.php';
                                $label = $LANG29[34];
                                $menu_item = '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;

                                $link_array = array( $menu_item ) + $link_array;

                                foreach( $link_array as $link ) {
                                    $menu .= $link;
                                }

                                $menu .= '</ul>' . LB . '</li>' . LB;
                            }
                        }
                        break;

                    case 3 : // topics menu
                        if ( $this->id != 0 && $this->access > 0 && $parentaclass != '' ) {
                            $menu .= "<li>" . '<a class="' . $parentaclass . '" name="'.$parentaclass.'" href="#">' . MENU_escapeStoredText($this->label) . '</a>' . LB;
                        } else {
                            $menu .= "<li>" . '<a href="#">' . MENU_escapeStoredText($this->label) . '</a></li>' . LB;
                        }
                        if ( $this->id == 0 && $ulclass != '' ) {
                            $menu .= '<ul class="' . $ulclass . '">' . LB;
                        } else {
                            $menu .= '<ul>' . LB;
                        }
                        $langsql = COM_getLangSQL( 'tid', 'AND' );

                        $sql = "SELECT tid,topic,imageurl FROM {$_TABLES['topics']} WHERE hidden=0" . $langsql;
                        if( !empty( $_USER['uid'] ) && ( $_USER['uid'] > 1 )) {
                            if (COM_versionCompare(VERSION, '2.2.2', '>=')) {
                                $tids = [];
                            } else {
                                $tids = DB_getItem( $_TABLES['userindex'], 'tids', "uid = {$_USER['uid']}" );
                            }
                            if( !empty( $tids )) {
                                $safeTids = array();
                                foreach (preg_split('/\s+/', trim((string) $tids)) as $tid) {
                                    $tid = (int) $tid;
                                    if ($tid > 0) {
                                        $safeTids[] = $tid;
                                    }
                                }
                                if (!empty($safeTids)) {
                                    $sql .= " AND (tid NOT IN (" . implode(',', $safeTids)
                                         . "))" . COM_getPermSQL( 'AND' );
                                } else {
                                    $sql .= COM_getPermSQL( 'AND' );
                                }
                            } else {
                                $sql .= COM_getPermSQL( 'AND' );
                            }
                        } else {
                            $sql .= COM_getPermSQL( 'AND' );
                        }
 
                        if( $_CONF['sortmethod'] == 'alpha' ) {
                            $sql .= ' ORDER BY topic ASC';
                        } else {
                            $sql .= ' ORDER BY sortnum';
                        }
                        $result = DB_query( $sql );


                        while( $A = DB_fetchArray( $result ) ) {
                            $topicname = stripslashes( $A['topic'] );
                            $url =  $_CONF['site_url'] . '/index.php?topic=' . $A['tid'];
                            $label = $topicname;
                            /*
                            $countstring = '';
                            if( $_CONF['showstorycount'] || $_CONF['showsubmissioncount'] ) {
                                $countstring .= ' (';
                                if( $_CONF['showstorycount'] ) {
                                    if( empty( $storycount[$A['tid']] )) {
                                        $countstring .= 0;
                                    } else {
                                        $countstring .= COM_numberFormat( $storycount[$A['tid']] );
                                    }
                                }
                                if( $_CONF['showsubmissioncount'] ) {
                                    if( $_CONF['showstorycount'] ) {
                                        $countstring .= '/';
                                    }
                                    if( empty( $submissioncount[$A['tid']] )) {
                                        $countstring .= 0;
                                    } else {
                                        $countstring .= COM_numberFormat( $submissioncount[$A['tid']] );
                                    }
                                }

                                $countstring .= ')';
                            }
                            $label .= $countstring;
							*/
                            $menu .= '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                        }
                        $menu .= '</ul>' . LB . '</li>' . LB;
                        break;

                    case 4 : // static pages menu
                        if ( $this->id != 0 && $this->access > 0 && $parentaclass != '' ) {
                            $menu .= "<li>" . '<a class="' . $parentaclass . '" href="#">' . MENU_escapeStoredText($this->label) . '</a>' . LB;
                        } else {
                            $menu .= "<li>" . '<a href="#">' . MENU_escapeStoredText($this->label) . '</a></li>' . LB;
                        }
                        if ( $this->id == 0 && $ulclass != '' ) {
                            $menu .= '<ul class="' . $ulclass . '">' . LB;
                        } else {
                            $menu .= '<ul>' . LB;
                        }
                        $order = '';
                        if (!empty ($_SP_CONF['sort_menu_by'])) {
                            $order = ' ORDER BY ';
                            if ($_SP_CONF['sort_menu_by'] == 'date') {
                                $order .= 'sp_date DESC';
                            } else if ($_SP_CONF['sort_menu_by'] == 'label') {
                                $order .= 'sp_label';
                            } else if ($_SP_CONF['sort_menu_by'] == 'title') {
                                $order .= 'sp_title';
                            } else { // default to "sort by id"
                                $order .= 'sp_id';
                            }
                        }
                        $result = DB_query ('SELECT sp_id, sp_label FROM ' . $_TABLES['staticpage'] . ' WHERE sp_onmenu = 1 AND draft_flag = 0' . COM_getPermSql ('AND') . $order);
                        $nrows = DB_numRows ($result);
                        $menuitems = array ();
                        for ($i = 0; $i < $nrows; $i++) {
                            $A = DB_fetchArray ($result);
                            $url = COM_buildURL ($_CONF['site_url'] . '/staticpages/index.php?page=' . $A['sp_id']);
                            $label = $A['sp_label'];
                            $menu .= '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                        }
                        $menu .= '</ul>' . LB . '</li>' . LB;
                        break;
                    case 5 : // plugin menu
                        if ( $this->id != 0 && $this->access > 0 && $parentaclass != '' ) {
                            $menu .= "<li>" . '<a class="' . $parentaclass . '" href="#">' . MENU_escapeStoredText($this->label) . '</a>' . LB;
                        } else {
                            $menu .= "<li>" . '<a href="#">' . MENU_escapeStoredText($this->label) . '</a>' . LB;
                        }
                        if ( $this->id == 0 && $ulclass != '' ) {
                            $menu .= '<ul class="' . $ulclass . '">' . LB;
                        } else {
                            $menu .= '<ul>' . LB;
                        }
                        $plugin_menu = PLG_getMenuItems();
                        if( count( $plugin_menu ) == 0 ) {
                            $this->access = 0;
                        } else {
                            for( $i = 1; $i <= count( $plugin_menu ); $i++ ) {
                                $url = current($plugin_menu);
                                $label = key($plugin_menu);
                                $menu .= '<li><a href="' . MENU_safeHref($url) . '">' . MENU_escapeStoredText($label) . '</a></li>' . LB;
                                next( $plugin_menu );
                            }
                        }
                        $menu .= '</ul>' . LB . '</li>' . LB;
                        break;

                    case 6 : // header menu

                    default : // unknown
                }
                break;
            case '4' : // plugin
                $plugin_menus = MENU_PLG_getMenuItems();
                if ( isset($plugin_menus[$this->subtype]) ) {
                    $this->url = $plugin_menus[$this->subtype];
                } else {
                    $this->access = 0;
                }
                break;
            case '5' : // static page
                $this->url = COM_buildURL ($_CONF['site_url'] . '/staticpages/index.php?page=' . $this->subtype);
                break;
            case '6' : // external URL
                /*
                 * Check to see if any internal macros
                 */
                $this->replace_macros();
                break;
            case '7' : // php function
                if (!MENU_runtimeConfigEnabled('allow_php_elements', false)) {
                    MENU_debugLog('PHP menu element ' . (int) $this->id . ' skipped by configuration.');
                    break;
                }
                $functionName = $this->subtype;
                if (function_exists($functionName)) {
                    /* Pass the type of menu to custom php function */
                    $menu = "<li>" . '<a class="' . $parentaclass . '" name="'.$parentaclass.'" href="#">' . MENU_escapeStoredText($this->label) . '</a>' . LB;
                    $menu .= $functionName();
                    $menu .= '</li>';
                }
                break;
            case '9' : // topic
                $this->url = $_CONF['site_url'] . '/index.php?topic=' . $this->subtype;
                break;
            default : // unknown
                break;
        }
        if ( $this->id != 0 && $this->group_id == 998 && (SEC_inGroup('Root') || SEC_inGroup('menu Admin')) ) {
            return $retval;
        }
        if ( $allowed == 0 ) {
            return $retval;
        }
        /* here we actually define the menu item.... */
        if ( $this->type == 3 || $this->type == 7) {
            $retval .= $menu;
        } else {
            if ( $this->id != 0 && $this->access > 0 ) {
                if ($this->isLastChild() && $lastclass != '') {
                    $lastClass = ' class="'.$lastclass.'"';
                } else {
                    $lastClass = '';
                }

                if ( $this->type == 1 && $parentaclass != '' ) {
                    $retval .= "<li".$lastClass.">" . '<a class="' . $parentaclass . '" name="'.$parentaclass.'" href="' . MENU_safeHref($this->url) . '"' . MENU_legacyParentAttributes() . '>' . MENU_escapeStoredText($this->label) . '</a>' . LB;
                } else {
                    if ($this->type == 8 ) {
                        $retval .= "<li".$lastClass.'><a><strong>' . MENU_escapeStoredText($this->label) . '</strong></a></li>' . LB;
                    } else {
                        $retval .= "<li".$lastClass.">" . '<a href="' . MENU_safeHref($this->url) . '"' . MENU_legacyLinkAttributes($this->url, $this->target) . '>' . MENU_escapeStoredText($this->label) . '</a></li>' . LB;
                    }
                }
            }
        }
        if ( !empty($this->children)) {
            $howmany = $this->getChildcount();
            if ( $howmany > 0 ) {
                $children = $this->getChildren();
                if ( $this->id == 0 && $ulclass != '' ) {
                    $retval .= '<ul class="' . $ulclass . '">' . LB;
                } else {
                    $retval .= '<ul>' . LB;
                }
                foreach($children as $child) {
                    $meLevel++;
                    $retval .= $Menus[$this->menu_id]['elements'][$child]->showTree($depth,$oulclass,$oliclass,$oparentaclass,$olastclass,$selected);
                    $meLevel--;
                }
                if ( $this->id == 0 ) {
                    $retval .= '</ul>' . LB;
                } else {
                    $retval .= '</ul>' . LB . '</li>' . LB;
                }
            }
        } else {
            if ($parentaclass != '' && $this->type == 1) {
                $retval .= '</li>';
            }
        }
        return $retval;
    }
}
?>
