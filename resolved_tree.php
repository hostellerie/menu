<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                        |
// +---------------------------------------------------------------------------+
// | resolved_tree.php                                                         |
// |                                                                           |
// | Theme-facing resolved tree API.                                           |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

require_once __DIR__ . '/runtime_config.php';
require_once __DIR__ . '/resolved_admin.php';

function MENU_findMenuIdByName($name)
{
    global $Menus;

    $names = array();
    $lang = function_exists('COM_getLanguageId') ? COM_getLanguageId() : '';
    if ($lang !== '') {
        $names[] = $name . '_' . $lang;
    }
    $names[] = $name;

    foreach ($names as $candidate) {
        if (!is_array($Menus)) {
            break;
        }
        foreach ($Menus as $menu) {
            if (isset($menu['menu_name']) && strcasecmp(trim($menu['menu_name']), trim($candidate)) === 0) {
                return (int) $menu['menu_id'];
            }
        }
    }

    return 0;
}

function MENU_getResolvedTree($name = 'navigation')
{
    global $Menus;

    $menuId = MENU_findMenuIdByName($name);
    if ($menuId <= 0 || !isset($Menus[$menuId])) {
        return array();
    }

    $menu = $Menus[$menuId];
    if (empty($menu['active']) || (isset($menu['menu_perm']) && (int) $menu['menu_perm'] !== 3)) {
        return array();
    }
    if (!isset($menu['elements'][0])) {
        return array();
    }

    $tree = array();
    foreach ($menu['elements'][0]->getChildren() as $childId) {
        if (!isset($menu['elements'][$childId])) {
            continue;
        }
        $node = MENU_resolveElementNode($menuId, $childId);
        if ($node !== null) {
            $tree[] = $node;
        }
    }

    return $tree;
}

function MENU_resolveElementNode($menuId, $elementId)
{
    global $Menus, $_CONF;

    if (!isset($Menus[$menuId]['elements'][$elementId])) {
        return null;
    }

    $element = $Menus[$menuId]['elements'][$elementId];
    if ((int) $element->active !== 1 || (int) $element->access <= 0) {
        return null;
    }
    if ((int) $element->group_id === 998 && (SEC_inGroup('Root') || SEC_inGroup('menu Admin'))) {
        return null;
    }
    if ((int) $element->group_id !== 998 && (int) $element->group_id !== 0 && !SEC_inGroup($element->group_id)) {
        return null;
    }

    $type = (int) $element->type;
    $subtype = $element->subtype;
    $url = (string) $element->url;
    $allowed = true;
    $dynamicChildren = array();
    $resolved = true;

    switch ($type) {
        case 1:
            $url = MENU_resolveMacros($url);
            break;

        case 2:
            list($url, $allowed) = MENU_resolveGeeklogAction($subtype);
            break;

        case 3:
            $url = '#';
            list($dynamicChildren, $resolved) = MENU_resolveGeeklogCoreChildren((int) $subtype);
            break;

        case 4:
            $pluginMenus = MENU_PLG_getMenuItems();
            if (isset($pluginMenus[$subtype])) {
                $url = $pluginMenus[$subtype];
            } else {
                return null;
            }
            break;

        case 5:
            if (!MENU_resolvedStaticPageAvailable($subtype)) {
                return null;
            }
            $url = COM_buildURL($_CONF['site_url'] . '/staticpages/index.php?page=' . rawurlencode((string) $subtype));
            break;

        case 6:
            $url = MENU_resolveMacros($url !== '' ? $url : (string) $subtype);
            break;

        case 7:
            if (!MENU_runtimeConfigEnabled('allow_php_elements', false)) {
                MENU_debugLog('Resolved PHP menu element ' . (int) $element->id . ' skipped by configuration.');
                return null;
            }
            // Arbitrary PHP callbacks historically return HTML. Preserve the
            // item in the data API, but do not pretend that HTML is a resolved
            // child tree. Themes can detect resolved=false and choose a legacy
            // fallback if they need to support such an item.
            $url = '#';
            $resolved = false;
            break;

        case 8:
            $url = '';
            break;

        case 9:
            if (!MENU_resolvedTopicAvailable($subtype)) {
                return null;
            }
            $url = $_CONF['site_url'] . '/index.php?topic=' . rawurlencode((string) $subtype);
            break;
    }

    if (!$allowed) {
        return null;
    }

    $children = $dynamicChildren;
    if (!empty($element->children)) {
        foreach ($element->getChildren() as $childId) {
            $child = MENU_resolveElementNode($menuId, $childId);
            if ($child !== null) {
                $children[] = $child;
            }
        }
    }

    return array(
        'id' => (int) $element->id,
        'parent_id' => (int) $element->pid,
        'label' => strip_tags((string) $element->label),
        'type' => $type,
        'subtype' => $subtype,
        'url' => $url,
        'target' => (string) $element->target,
        'rel' => MENU_resolvedLinkRel($url, (string) $element->target),
        'aria_label' => MENU_runtimeConfigEnabled('accessibility_markup', true)
            ? strip_tags((string) $element->label) : '',
        'active' => true,
        'selected' => MENU_resolvedNodeSelected($url),
        'resolved' => $resolved,
        'children' => $children,
    );
}

/**
 * Return whether a stored Static Page destination is currently usable.
 *
 * The menu item itself is never deleted. If Static Pages is disabled, the
 * referenced page is gone, or the page is a draft, the resolved tree omits the
 * item until the destination becomes available again.
 *
 * @param string $pageId
 * @return bool
 */
function MENU_resolvedStaticPageAvailable($pageId)
{
    global $_PLUGINS, $_TABLES;

    if (!isset($_PLUGINS) || !is_array($_PLUGINS) || !in_array('staticpages', $_PLUGINS, true)) {
        return false;
    }
    if (!isset($_TABLES['staticpage'])) {
        return false;
    }

    // Unit-test environments may intentionally omit Geeklog's DB helpers.
    if (!function_exists('DB_getItem')) {
        return true;
    }

    $escaped = function_exists('DB_escapeString')
        ? DB_escapeString((string) $pageId)
        : addslashes((string) $pageId);
    $found = DB_getItem(
        $_TABLES['staticpage'],
        'sp_id',
        "sp_id='" . $escaped . "' AND draft_flag=0"
    );

    return $found !== '' && $found !== null && $found !== false;
}

/**
 * Return whether a stored Topic destination still exists.
 *
 * @param string $topicId
 * @return bool
 */
function MENU_resolvedTopicAvailable($topicId)
{
    global $_TABLES;

    if (!isset($_TABLES['topics'])) {
        return false;
    }

    if (!function_exists('DB_getItem')) {
        return true;
    }

    $escaped = function_exists('DB_escapeString')
        ? DB_escapeString((string) $topicId)
        : addslashes((string) $topicId);
    $found = DB_getItem($_TABLES['topics'], 'tid', "tid='" . $escaped . "'");

    return $found !== '' && $found !== null && $found !== false;
}

function MENU_resolveMacros($url)
{
    global $_CONF;

    $url = str_replace('%site_url%', $_CONF['site_url'], $url);
    $url = str_replace('%site_admin_url%', $_CONF['site_admin_url'], $url);
    $url = str_replace('%version%', VERSION, $url);

    return $url;
}

function MENU_resolveGeeklogAction($subtype)
{
    global $_CONF;

    switch ((int) $subtype) {
        case 1:
            return array($_CONF['site_url'] . '/users.php?mode=login', true);
        case 2:
            return array($_CONF['site_url'] . '/users.php?mode=logout', true);
        case 3:
            return array($_CONF['site_url'] . '/usersettings.php?mode=edit', true);
        case 4:
            return array($_CONF['site_admin_url'] . '/index.php', true);
        default:
            return array('', false);
    }
}

function MENU_resolvedNodeSelected($url)
{
    if ($url === '' || $url === '#') {
        return false;
    }

    if (!function_exists('COM_getCurrentURL')) {
        return false;
    }

    $current = COM_getCurrentURL();

    return $current !== '' && strpos($current, html_entity_decode($url, ENT_QUOTES, 'UTF-8')) === 0;
}
