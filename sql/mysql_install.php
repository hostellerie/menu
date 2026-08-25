<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | mysql_install.php                                                         |
// |                                                                           |
// | Installation SQL                                                          |
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
// | This program is licensed under the terms of the GNU General Public License|
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                      |
// | See the GNU General Public License for more details.                      |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+


$_SQL[] = "CREATE TABLE {$_TABLES['menu']} (
  `id` int(11) NOT NULL auto_increment,
  `menu_name` varchar(64) NOT NULL,
  `menu_type` tinyint(4) NOT NULL,
  `menu_active` tinyint(3) NOT NULL,
  `group_id` mediumint(9) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `menu_name` (`menu_name`)
) ENGINE=MyISAM;";

$_SQL[] = "CREATE TABLE {$_TABLES['menu_config']} (
  `id` int(11) NOT NULL auto_increment,
  `menu_id` int(11) NOT NULL,
  `conf_name` varchar(64) NOT NULL,
  `conf_value` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `Config` (`menu_id`,`conf_name`),
  KEY `menu_id` (`menu_id`)
) ENGINE=MyISAM;";

$_SQL[] = "CREATE TABLE {$_TABLES['menu_elements']} (
    `id` int(11) NOT NULL auto_increment,
    `pid` int(11) NOT NULL,
    `menu_id` int(11) NOT NULL default '0',
    `element_label` varchar(255) NOT NULL,
    `element_type` int(11) NOT NULL,
    `element_subtype` varchar(255) NOT NULL,
    `element_order` int(11) NOT NULL,
    `element_active` tinyint(4) NOT NULL,
    `element_url` varchar(255) NOT NULL,
    `element_target` varchar(255) NOT NULL,
    `group_id` mediumint(9) NOT NULL,
    PRIMARY KEY( `id` ),
    INDEX ( `pid` ),
    KEY `menu_parent_order` (`menu_id`, `pid`, `element_order`)
) ENGINE=MyISAM;";


$_SQL[] = "INSERT INTO {$_TABLES['menu']} (`id`, `menu_name`, `menu_type`, `menu_active`, `group_id`) VALUES
(1, 'navigation', 1, 1, 2),
(2, 'footer', 2, 1, 2),
(3, 'block', 3, 1, 2);
";

$_SQL[] = "INSERT INTO {$_TABLES['menu_config']} (`id`, `menu_id`, `conf_name`, `conf_value`) VALUES
(1, 1, 'main_menu_bg_color', '#151515'),
(2, 1, 'main_menu_hover_bg_color', '#3667c0'),
(3, 1, 'main_menu_text_color', '#CCCCCC'),
(4, 1, 'main_menu_hover_text_color', '#FFFFFF'),
(5, 1, 'submenu_text_color', '#FFFFFF'),
(6, 1, 'submenu_hover_text_color', '#679EF1'),
(7, 1, 'submenu_background_color', '#151515'),
(8, 1, 'submenu_hover_bg_color', '#333333'),
(9, 1, 'submenu_highlight_color', '#333333'),
(10, 1, 'submenu_shadow_color', '#000000'),
(11, 1, 'use_images', '0'),
(12, 1, 'menu_bg_filename', ''),
(13, 1, 'menu_hover_filename', ''),
(14, 1, 'menu_parent_filename', ''),
(15, 1, 'menu_alignment', '1'),
(16, 2, 'main_menu_bg_color', '#000000'),
(17, 2, 'main_menu_hover_bg_color', '#000000'),
(18, 2, 'main_menu_text_color', '#3677c0'),
(19, 2, 'main_menu_hover_text_color', '#679ef1'),
(20, 2, 'submenu_text_color', '#000000'),
(21, 2, 'submenu_hover_text_color', '#000000'),
(22, 2, 'submenu_background_color', '#000000'),
(23, 2, 'submenu_hover_bg_color', '#000000'),
(24, 2, 'submenu_highlight_color', '#999999'),
(25, 2, 'submenu_shadow_color', '#000000'),
(26, 2, 'menu_alignment', '0'),
(27, 2, 'use_images', '0'),
(28, 3, 'main_menu_bg_color', '#DDDDDD'),
(29, 3, 'main_menu_hover_bg_color', '#BBBBBB'),
(30, 3, 'main_menu_text_color', '#0000ff'),
(31, 3, 'main_menu_hover_text_color', '#FFFFFF'),
(32, 3, 'submenu_text_color', '#0000FF'),
(33, 3, 'submenu_hover_text_color', '#FFFFFF'),
(34, 3, 'submenu_background_color', '#DDDDDD'),
(35, 3, 'submenu_hover_bg_color', '#BBBBBB'),
(36, 3, 'submenu_highlight_color', '#999999'),
(37, 3, 'submenu_shadow_color', '#999999'),
(38, 3, 'menu_alignment', '1'),
(39, 3, 'use_images', '0'),
(40, 3, 'menu_bg_filename', ''),
(41, 3, 'menu_hover_filename', ''),
(42, 3, 'menu_parent_filename', '');
";

$_SQL[] = "INSERT INTO {$_TABLES['menu_elements']} (`id`, `pid`, `menu_id`, `element_label`, `element_type`, `element_subtype`, `element_order`, `element_active`, `element_url`, `element_target`, `group_id`) VALUES
(1, 0, 1, 'Home', 2, '0', 10, 1, '', '', 2),
(2, 0, 1, 'Contribute', 2, '1', 20, 1, '', '', 13),
(3, 0, 1, 'Search', 2, '4', 30, 1, '', '', 2),
(4, 0, 1, 'Directory', 2, '2', 40, 1, '', '', 2),
(5, 0, 1, 'Topics', 3, '3', 50, 1, '', '', 2),
(6, 0, 1, 'Extras', 3, '5', 60, 1, '', '', 2),
(7, 0, 1, 'Site Stats', 2, '5', 90, 1, '', '', 2),
(8, 0, 1, 'My Account', 3, '1', 100, 1, '', '', 13),
(9, 0, 1, 'Admins Only', 3, '2', 110, 1, '', '', 1),
(10, 0, 2, 'Home', 2, '0', 10, 1, '', '', 2),
(11, 0, 2, 'Contribute', 2, '1', 30, 1, '', '', 13),
(12, 0, 2, 'Search', 2, '4', 20, 1, '', '', 2),
(13, 0, 2, 'Site Stats', 2, '5', 40, 1, '', '', 2),
(14, 0, 2, 'Contact Us', 6, '%site_url%/profiles.php?uid=2', 50, 1, '%site_url%/profiles.php?uid=2', '', 2),
(15, 0, 2, 'Top', 6, '#top', 60, 1, '#top', '', 2),
(16, 0, 3, 'Home', 2, '0', 10, 1, '', '', 2),
(17, 0, 3, 'Downloads', 4, 'filemgmt', 20, 1, '', '', 2),
(18, 0, 3, 'Forums', 4, 'forum', 30, 1, '', '', 2),
(19, 0, 3, 'Topic Menu', 3, '3', 40, 1, '', '', 2),
(20, 0, 3, 'User Menu', 3, '1', 50, 1, '', '', 13),
(21, 0, 3, 'Admin Options', 3, '2', 60, 1, '', '', 1),
(22, 0, 3, 'Logout', 6, '%site_url%/users.php?mode=logout', 70, 1, '%site_url%/users.php?mode=logout', '', 13);
";
