<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// |                                                                           |
// | This file is used to hook into Geeklog's configuration UI                 |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2018 by the following authors:                         |
// |                                                                           |
// | Authors: Ben - ben AT geeklog DOT fr                                      |
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

if (stripos($_SERVER['PHP_SELF'], basename(__FILE__)) !== false) {
    die('This file can not be used on its own.');
}

/**
* Initialize Menu plugin configuration.
*
* Menu currently has no runtime settings stored in Geeklog's configuration
* system. We keep only the structural entries required by the configuration
* manager. The old Plugin Toolkit samples (samplesetting1 / samplesetting2)
* are deliberately not created on new installations.
*
* Existing installations may still contain those legacy rows. Their language
* definitions are retained so Geeklog 2.1.1 and 2.2.2 can render the global
* configuration page safely until an upgrade migration removes them.
*
* @return boolean TRUE on success
*/
function plugin_initconfig_menu()
{
    $me = 'menu';

    $c = config::get_instance();
    if (!$c->group_exists($me)) {
        $c->add('sg_main', null, 'subgroup', 0, 0, null, 0, true, $me, 0);
        $c->add('tab_main', null, 'tab', 0, 0, null, 0, true, $me, 0);
        $c->add('fs_main', null, 'fieldset', 0, 0, null, 0, true, $me, 0);
    }

    return true;
}
