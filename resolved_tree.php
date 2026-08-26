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
    $found = DB_getItem(
        $_TABLES['topics'],
        'tid',
        "tid='" . $escaped . "'"
    );

    return $found !== '' && $found !== null && $found !== false;
}

function MENU_resolveMacros($url)
{
    global $_CONF;

    $url = str_replace('%version%', VERSION, (string) $url);
    $url = str_replace('%site_url%', $_CONF['site_url'], $url);
    $url = str_replace('%site_admin_url%', $_CONF['site_admin_url'], $url);
    return $url;
}

function MENU_resolveGeeklogAction($subtype)
{
    global $_CONF;

    $topic = isset($_REQUEST['topic']) ? COM_applyFilter($_REQUEST['topic']) : '';
    $anon = COM_isAnonUser();
    $allowed = true;

    switch ((int) $subtype) {
        case 0:
            $url = $_CONF['site_url'] . '/';
            break;
        case 1:
            $url = $_CONF['site_url'] . '/submit.php?type=story';
            if ($topic !== '') {
                $url .= '&amp;topic=' . rawurlencode($topic);
            }
            if ($anon && (!empty($_CONF['loginrequired']) || !empty($_CONF['submitloginrequired']))) {
                $allowed = false;
            }
            break;
        case 2:
            $url = $_CONF['site_url'] . '/directory.php';
            if ($topic !== '') {
                $url = COM_buildUrl($url . '?topic=' . rawurlencode($topic));
            }
            if ($anon && (!empty($_CONF['loginrequired']) || !empty($_CONF['directoryloginrequired']))) {
                $allowed = false;
            }
            break;
        case 3:
            $url = $_CONF['site_url'] . '/usersettings.php?mode=edit';
            if ($anon && (!empty($_CONF['loginrequired']) || !empty($_CONF['profileloginrequired']))) {
                $allowed = false;
            }
            break;
        case 4:
            $url = $_CONF['site_url'] . '/search.php';
            if ($anon && (!empty($_CONF['loginrequired']) || !empty($_CONF['searchloginrequired']))) {
                $allowed = false;
            }
            break;
        case 5:
            $url = $_CONF['site_url'] . '/stats.php';
            if (!SEC_hasRights('stats.view')) {
                $allowed = false;
            }
            break;
        default:
            $url = $_CONF['site_url'] . '/';
            break;
    }

    return array($url, $allowed);
}

function MENU_resolveGeeklogCoreChildren($subtype)
{
    global $_CONF, $_TABLES, $_USER, $_PLUGINS, $_SP_CONF;

    $children = array();
    $resolved = true;

    switch ((int) $subtype) {
        case 1: // user menu
            if (!empty($_USER['uid']) && (int) $_USER['uid'] > 1) {
                if (function_exists('PLG_getUserOptions')) {
                    $options = PLG_getUserOptions();
                    foreach ($options as $option) {
                        if (is_object($option) && isset($option->adminurl, $option->adminlabel)) {
                            $children[] = MENU_resolvedSyntheticNode($option->adminlabel, $option->adminurl);
                        }
                    }
                }
                $children[] = MENU_resolvedSyntheticNode('Preferences', $_CONF['site_url'] . '/usersettings.php?mode=edit');
                $children[] = MENU_resolvedSyntheticNode('Logout', $_CONF['site_url'] . '/users.php?mode=logout');
            } else {
                $children[] = MENU_resolvedSyntheticNode('Login', $_CONF['site_url'] . '/users.php?mode=login');
            }
            break;

        case 2: // admin menu
            $children = MENU_resolveGeeklogAdminChildren();
            break;

        case 3: // topics
            $langsql = COM_getLangSQL('tid', 'AND');
            $sql = "SELECT tid,topic FROM {$_TABLES['topics']} WHERE hidden=0" . $langsql . COM_getPermSQL('AND');
            $sql .= (!empty($_CONF['sortmethod']) && $_CONF['sortmethod'] === 'alpha') ? ' ORDER BY topic ASC' : ' ORDER BY sortnum';
            $result = DB_query($sql);
            while ($row = DB_fetchArray($result)) {
                $children[] = MENU_resolvedSyntheticNode(
                    stripslashes($row['topic']),
                    $_CONF['site_url'] . '/index.php?topic=' . rawurlencode($row['tid']),
                    9,
                    $row['tid']
                );
            }
            break;

        case 4: // static pages menu
            if (!in_array('staticpages', $_PLUGINS)) {
                break;
            }
            $order = '';
            if (!empty($_SP_CONF['sort_menu_by'])) {
                if ($_SP_CONF['sort_menu_by'] === 'date') {
                    $order = ' ORDER BY sp_date DESC';
                } elseif ($_SP_CONF['sort_menu_by'] === 'label') {
                    $order = ' ORDER BY sp_label';
                } elseif ($_SP_CONF['sort_menu_by'] === 'title') {
                    $order = ' ORDER BY sp_title';
                } else {
                    $order = ' ORDER BY sp_id';
                }
            }
            $result = DB_query('SELECT sp_id, sp_label FROM ' . $_TABLES['staticpage']
                . ' WHERE sp_onmenu = 1 AND draft_flag = 0' . COM_getPermSql('AND') . $order);
            while ($row = DB_fetchArray($result)) {
                $children[] = MENU_resolvedSyntheticNode(
                    $row['sp_label'],
                    COM_buildURL($_CONF['site_url'] . '/staticpages/index.php?page=' . rawurlencode($row['sp_id'])),
                    5,
                    $row['sp_id']
                );
            }
            break;

        case 5: // plugin menu
            $pluginMenu = PLG_getMenuItems();
            foreach ($pluginMenu as $label => $url) {
                $children[] = MENU_resolvedSyntheticNode($label, $url, 4, $label);
            }
            break;

        default:
            break;
    }

    return array($children, $resolved);
}

function MENU_resolvedSyntheticNode($label, $url, $type = 0, $subtype = '')
{
    return array(
        'id' => 0,
        'parent_id' => 0,
        'label' => strip_tags((string) $label),
        'type' => (int) $type,
        'subtype' => $subtype,
        'url' => (string) $url,
        'target' => '',
        'rel' => '',
        'aria_label' => MENU_runtimeConfigEnabled('accessibility_markup', true)
            ? strip_tags((string) $label) : '',
        'active' => true,
        'selected' => MENU_resolvedNodeSelected($url),
        'resolved' => true,
        'children' => array(),
    );
}

function MENU_resolvedNodeSelected($url)
{
    if ($url === '' || $url === '#') {
        return false;
    }
    if (!function_exists('COM_getCurrentURL')) {
        return false;
    }

    $current = html_entity_decode(COM_getCurrentURL(), ENT_QUOTES, 'UTF-8');
    $candidate = html_entity_decode((string) $url, ENT_QUOTES, 'UTF-8');
    return rtrim($current, '/') === rtrim($candidate, '/');
}
