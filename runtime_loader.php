<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | runtime_loader.php                                                        |
// |                                                                           |
// | Batched runtime menu/config/element loading.                              |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

// functions.inc loads this runtime bootstrap on every active Menu request.
// Publish the theme-facing structured API here so themes can simply feature-
// detect MENU_getResolvedTree() without knowing Menu's internal file layout.
require_once __DIR__ . '/resolved_tree.php';

// Shared color helpers are kept in one dedicated module. Loading them here
// makes MENU_hexrgb() available to all plugin paths while admin/index.php can
// safely require the same file again via require_once.
require_once __DIR__ . '/color_utils.php';

/**
 * Return whether the current visitor can access Geeklog's core statistics page.
 *
 * Geeklog core stats.php does not require a stats.view feature. Anonymous
 * access is controlled by the global login requirement and statsloginrequired.
 * Registered users can access the page when the site itself is available.
 *
 * @return bool
 */
function MENU_coreStatsActionAllowed()
{
    global $_CONF;

    if (!COM_isAnonUser()) {
        return true;
    }

    return empty($_CONF['loginrequired']) && empty($_CONF['statsloginrequired']);
}

/**
 * Load the complete Menu runtime structure using a fixed number of queries.
 *
 * @param bool  $mbadmin Whether the current user has menu.admin
 * @param bool  $root    Whether the current user belongs to Root
 * @param array $groups  Current user's Geeklog group IDs
 * @return array
 */
function MENU_loadRuntimeMenus($mbadmin, $root, $groups)
{
    global $_CONF, $_TABLES;

    $menus = array();
    $groups = is_array($groups) ? $groups : array();

    // Query 1: menu metadata.
    $result = DB_query("SELECT * FROM {$_TABLES['menu']}", 1);
    while ($menu = DB_fetchArray($result)) {
        $menuId = (int) $menu['id'];
        if ($menuId <= 0) {
            continue;
        }

        $menus[$menuId] = array(
            'menu_name' => $menu['menu_name'],
            'menu_id' => $menuId,
            'active' => (int) $menu['menu_active'],
            'menu_type' => (int) $menu['menu_type'],
            'group_id' => (int) $menu['group_id'],
            'menu_perm' => 0,
            'config' => array(),
            'elements' => array(),
        );

        if ($mbadmin || $root) {
            $menus[$menuId]['menu_perm'] = 3;
        } elseif ((int) $menu['group_id'] === 998) {
            $menus[$menuId]['menu_perm'] = COM_isAnonUser() ? 3 : 0;
        } elseif (in_array((int) $menu['group_id'], $groups)) {
            $menus[$menuId]['menu_perm'] = 3;
        }

        $element = new mbElement();
        $element->id = 0;
        $element->menu_id = $menuId;
        $element->label = 'Top Level Menu';
        $element->type = -1;
        $element->pid = 0;
        $element->order = 0;
        $element->url = '';
        $element->owner_id = $mbadmin;
        $element->group_id = $root;
        if ($mbadmin) {
            $element->access = 3;
        }
        $menus[$menuId]['elements'][0] = $element;
    }

    if (empty($menus)) {
        return $menus;
    }

    // Query 2: all per-menu configuration rows.
    $cfgResult = DB_query(
        "SELECT menu_id,conf_name,conf_value FROM {$_TABLES['menu_config']} ORDER BY menu_id"
    );
    while ($cfgRow = DB_fetchArray($cfgResult)) {
        $menuId = (int) $cfgRow['menu_id'];
        if (isset($menus[$menuId])) {
            $menus[$menuId]['config'][$cfgRow['conf_name']] = $cfgRow['conf_value'];
        }
    }

    // Query 3: all elements, already grouped and ordered for hierarchy rebuild.
    $elementResult = DB_query(
        "SELECT * FROM {$_TABLES['menu_elements']} ORDER BY menu_id, element_order ASC, id ASC",
        1
    );
    while ($row = DB_fetchArray($elementResult)) {
        $menuId = (int) $row['menu_id'];
        if (!isset($menus[$menuId])) {
            continue;
        }

        // Older persisted rows and lightweight test fixtures may not expose
        // every optional column. Normalize missing type information to zero so
        // runtime loading stays warning-free until an explicit site upgrade has
        // completed, which is important for shared-files multisite deployments.
        $elementType = isset($row['element_type']) ? (int) $row['element_type'] : 0;
        $elementSubtype = isset($row['element_subtype']) ? (int) $row['element_subtype'] : 0;

        // Geeklog core action subtype 5 is the Site Statistics page. The
        // historical renderer checks a non-core stats.view feature and can hide
        // an otherwise public Stats link. Normalize this action to the resolved
        // core URL after applying the same access rule as stats.php itself.
        if ($elementType === 2 && $elementSubtype === 5) {
            if (!MENU_coreStatsActionAllowed()) {
                continue;
            }
            $row['element_type'] = 6;
            $row['element_url'] = $_CONF['site_url'] . '/stats.php';
        }

        $element = new mbElement();
        $element->constructor($row, $mbadmin, $root, $groups);
        if ($element->access > 0) {
            $menus[$menuId]['elements'][$element->id] = $element;
        }
    }

    foreach ($menus as $menuId => $menu) {
        foreach ($menus[$menuId]['elements'] as $id => $element) {
            if ($id === 0) {
                continue;
            }
            $parentId = (int) $element->pid;
            if (isset($menus[$menuId]['elements'][$parentId])) {
                $menus[$menuId]['elements'][$parentId]->setChild($id);
            }
        }
    }

    return $menus;
}
