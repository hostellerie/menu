<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.0                                                           |
// +---------------------------------------------------------------------------+
// | english.php                                                               |
// |                                                                           |
// | English language file                                                     |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012 by the following authors:                              |
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

/**
* @package Menu
*/

/**
* Import Geeklog plugin messages for reuse
*
* @global array $LANG32
*/
global $LANG32;

// +---------------------------------------------------------------------------+
// | Array Format:                                                             |
// | $LANGXX[YY]:  $LANG - variable name                                       |
// |               XX    - specific array name                                 |
// |               YY    - phrase id or number                                 |
// +---------------------------------------------------------------------------+

$LANG_MENU_1 = array(
    'plugin_name' => 'Menu',
    'hello' => 'Hello, world!' // this is an example only - feel free to remove
);

$LANG_MENU00 = array (
    'menulabel'         => 'Menu',
    'plugin'            => 'menu',
    'access_denied'     => 'Access Denied',
    'access_denied_msg' => 'You do not have the proper security privilege to access to this page.  Your user name and IP have been recorded.',
    'admin'             => 'Menu Administration'
);

$LANG_MENU01 = array (
    'instructions'      => 'Menu allows you to easily customize your site logo and control the display of the site slogan.',
    'javascript_required' => 'Menu Requires that you have JavaScript enabled.',
    'logo_options'      => 'Menu Logo Options',
    'use_graphic_logo'  => 'Use Graphic Logo',
    'use_text_logo'     => 'Use Text Logo',
    'use_no_logo'       => 'Do Not Display a Logo',
    'display_site_slogan'   => 'Display Site Slogan',
    'upload_logo'       => 'Upload New Logo',
    'current_logo'      => 'Current Logo',
    'no_logo_graphic'   => 'No Logo Graphic available',
    'logo_help'         => 'Uploaded graphic logo images are not resized, the standard size for Menu logo is 100 pixels tall and should be less than 500 pixels wide.  You can upload larger images, but you will need to modify the site CSS in styles.css to ensure it displays properly.',
    'save'              => 'Save',
    'create_element'    => 'Create Menu Element',
    'add_new'           => 'Add New Menu Item',
    'add_newmenu'       => 'Create New Menu',
    'edit_menu'         => 'Edit Menu',
    'menu_list'         => 'Menu Listing',
    'configuration'     => 'Configuration',
    'edit_element'      => 'Edit Menu Item',
    'menu_element'      => 'Menu Element',
    'menu_type'         => 'Menu Type',
    'elements'          => 'Elements',
    'enabled'           => 'Enabled',
    'edit'              => 'Edit',
    'delete'            => 'Delete',
    'move_up'           => 'Move Up',
    'move_down'         => 'Move Down',
    'order'             => 'Order',
    'id'                => 'ID',
    'parent'            => 'Parent',
    'label'             => 'Menu Name',
    'elementlabel'      => 'Menu Item Label',
    'display_after'     => 'Display After',
    'type'              => 'Type',
    'url'               => 'URL',
    'php'               => 'PHP Function',
    'coretype'          => 'Geeklog Menu',
    'group'             => 'Group',
    'permission'        => 'Visible To',
    'active'            => 'Active',
    'top_level'         => 'Top Level Menu',
    'confirm_delete'    => 'Are you sure you want to delete this menu item?',
    'type_submenu'      => 'Sub Menu',
    'type_url_same'     => 'Parent Window',
    'type_url_new'      => 'New Window with navigation',
    'type_url_new_nn'   => 'New Window without navigation',
    'type_core'         => 'Geeklog Menu',
    'type_php'          => 'PHP Function',
    'gl_user_menu'      => 'User Menu',
    'gl_admin_menu'     => 'Admin Menu',
    'gl_topics_menu'    => 'Topics Menu',
    'gl_sp_menu'        => 'Static Pages Menu',
    'gl_plugin_menu'    => 'Plugin Menu',
    'gl_header_menu'    => 'Header Menu',
    'plugins'           => 'Plugin',
    'static_pages'      => 'Static Pages',
    'geeklog_function'  => 'Action',
    'save'              => 'Save',
    'cancel'            => 'Cancel',
    'action'            => 'Action',
    'first_position'    => 'First Position',
    'info'              => 'Info',
    'non-logged-in'     => 'Non Logged-In Users Only',
    'target'            => 'URL Window',
    'same_window'       => 'Same Window',
    'new_window'        => 'New Window',
    'menu_color_options'    => 'Menu Color Options',
    'top_menu_bg'           => 'Main Menu BG',
    'top_menu_hover'        => 'Main Menu Hover',
    'top_menu_text'         => 'Main Menu Text',
    'top_menu_text_hover'   => 'Main Menu Text Hover / Sub Menu Text',
    'sub_menu_text_hover'   => 'Sub Menu Text Hover',
    'sub_menu_text'         => 'Sub Menu Text Color',
    'sub_menu_bg'           => 'Sub Menu BG',
    'sub_menu_hover_bg'     => 'Sub Menu Hover BG',
    'sub_menu_highlight'    => 'Sub Menu Highlight',
    'sub_menu_shadow'       => 'Sub Menu Shadow',
    'menu_builder'          => 'Menu Builder',
    'logo'                  => 'Logo',
    'menu_colors'           => 'Menu Options',
    'options'               => 'Options',
    'menu_graphics'         => 'Menu Graphics',
    'graphics_or_colors'    => 'Use Graphics or Colors?',
    'graphics'              => 'Graphics',
    'colors'                => 'Colors',
    'menu_bg_image'         => 'Main Menu BG Image',
    'currently'             => 'Currently',
    'menu_hover_image'      => 'Menu Hover Image',
    'parent_item_image'     => 'Sub Menu Parent Indicator',
    'not_used'              => 'Not used if Use Graphics is selected below.',
	'select_color'			=> 'Select Color',
	'menu_alignment'		=> 'Menu Alignment',
	'alignment_question'	=> 'Align the Menu to the',
	'align_left'			=> 'Left',
	'align_right'			=> 'Right',
	'blocks'                => 'Block Styles',
	'reset'                 => 'Reset Form',
	'defaults'              => 'Reset To Default Values',
	'confirm_reset'         => 'This will reset the menu colors and graphics to the installation values. Are you sure you want to continue? When done, make sure to clear your local browser cache as well.',
	'menu_properties'       => 'Menu Properties for',
	'disabled_plugin'       => 'Not found or disabled plugin',
	'clone'                 => 'Copy',
	'clone_menu_label'      => 'Name for Cloned Menu',
	'topic'                 => 'Topics',
);

$LANG_HC = array (
    'main_menu_bg_color'         => 'Main Menu BG',
    'main_menu_hover_bg_color'   => 'Main Menu Hover',
    'main_menu_text_color'       => 'Main Menu Text',
    'main_menu_hover_text_color' => 'Main Menu Text Hover / Sub Menu Text',
	'menu_bg_filename'           => 'Menu Background Image File Name',
	'menu_hover_filename'        => 'Menu Hover Image File Name',
	'menu_parent_filename'      => 'Sub Menu Parent Indicator',
	'menu_alignment'            => 'Menu Alignment',
	'use_images'                => 'Use Images',
    'submenu_hover_text_color'   => 'Sub Menu Text Hover',
    'submenu_background_color'   => 'Sub Menu BG',
    'submenu_hover_bg_color'     => 'Sub Menu Hover BG',
    'submenu_highlight_color'    => 'Sub Menu Highlight',
    'submenu_shadow_color'       => 'Sub Menu Shadow',
);
$LANG_HS = array (
    'main_menu_text_color'          => 'Text',
    'main_menu_hover_text_color'    => 'Hover',
    'submenu_highlight_color'       => 'Seperator',
);
$LANG_VC = array(
    'main_menu_bg_color'           => 'Menu BG',
    'main_menu_hover_bg_color'     => 'Menu BG Hover',
    'main_menu_text_color'         => 'Menu Text',
    'main_menu_hover_text_color'   => 'Menu Text Hover',
    'submenu_text_color'           => 'Sub Menu Text',
    'submenu_hover_text_color'     => 'Sub Menu Text Hover',
    'submenu_highlight_color'      => 'Border',
);
$LANG_VS = array (
    'main_menu_text_color'          => 'Menu Text',
    'main_menu_hover_text_color'    => 'Menu Text Hover',
);

$LANG_MENU_MENU_TYPES = array(
    1                   => 'Horizontal - Cascading',
    2                   => 'Horizontal - Simple',
    3                   => 'Vertical - Cascading',
    4                   => 'Vertical - Simple',
);

$LANG_MENU_TYPES = array(
    1                   => 'Sub Menu',
    2                   => 'Geeklog Action',
    3                   => 'Geeklog Menu',
    4                   => 'Plugin',
    5                   => 'Static Page',
    6                   => 'External URL',
    7                   => 'PHP Function',
    8                   => 'Label',
    9                   => 'Topic',
);


$LANG_MENU_TARGET = array(
    1                   => 'Parent Window',
    2                   => 'New Window with navigation',
    3                   => 'New Window without navigation',
);

$LANG_MENU_GLFUNCTION = array(
    0                   => 'Home',
    1                   => 'Contribute',
    2                   => 'Directory',
    3                   => 'Preferences',
    4                   => 'Search',
    5                   => 'Site Stats',
);

$LANG_MENU_GLTYPES = array(
    1                   => 'User Menu',
    2                   => 'Admin Menu',
    3                   => 'Topics Menu',
    4                   => 'Static Pages Menu',
    5                   => 'Plugin Menu',
    6                   => 'Header Menu',
);

$LANG_MENU_ADMIN = array(
    1                   => 'Menu Builder allows you to create and edit menus for your site. To add a new menu, click the Create New Menu link above. To edit a menu\'s items, click the icon under the Elements column. To change the menu colors, click the icon under the Options column.',
    2                   => 'To create a new menu, specify a Menu Name and Menu type below. You can also set the active status, and what group of users will be able to see the menu, with the Active and Visible To fields.',
    3                   => 'Click on the icon under the Edit column to edit a menu item\'s properties. Arrange the items by moving them up or down with the arrows under the Order column.',
    4                   => 'To create a new menu element, specify its details and permissions below.',
    5                   => 'To edit a menu element, modify its details or permissions below.',
);

$LANG_MENU_GLTYPES_HELP = array(
    1                   => 'Displays the standard Geeklog user menu.',
    2                   => 'Displays the Geeklog administration menu.',
    3                   => 'Displays the Geeklog topics menu.',
    4                   => 'Displays Static Pages navigation.',
    5                   => 'Displays plugin-provided menu items.',
    6                   => 'Displays the Geeklog header menu.',
);

$LANG_MENU_GLFUNCTION_HELP = array(
    0                   => 'Link to the site home page.',
    1                   => 'Link to the story submission page.',
    2                   => 'Link to the article directory.',
    3                   => 'Link to user preferences.',
    4                   => 'Link to site search.',
    5                   => 'Link to site statistics.',
);

$LANG_MENU_TYPES_HELP = array(
    1                   => 'Creates a container that may contain child items and can optionally have its own link.',
    2                   => 'Creates a link to a standard Geeklog action such as Home, Search or Contribute.',
    3                   => 'Inserts a dynamic Geeklog menu such as the User, Admin or Topics menu.',
    4                   => 'Creates a link provided by an installed plugin.',
    5                   => 'Creates a link to a published Static Page.',
    6                   => 'Creates a link to a custom or external URL.',
    7                   => 'Calls a PHP function. Use only for trusted, advanced integrations.',
    8                   => 'Displays text without a built-in destination.',
    9                   => 'Creates a link to a Geeklog topic.',
);
