<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | admin_element_validation.php                                              |
// |                                                                           |
// | Server-side validation for Menu administration mutations.                 |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

require_once __DIR__ . '/element_types.php';

/**
 * Validate move/delete/deletemenu request references before the legacy
 * controller performs any mutation.
 *
 * @param string $mode
 * @param array  $post
 * @return string
 */
function MENU_adminMutationReferenceError($mode, $post)
{
    global $_TABLES;

    if ($mode !== 'move' && $mode !== 'delete' && $mode !== 'deletemenu'
        && $mode !== 'activate' && $mode !== 'menuactivate') {
        return '';
    }
    if (!is_array($post)) {
        return 'Invalid Menu administration request.';
    }

    if ($mode === 'deletemenu' || $mode === 'menuactivate') {
        $menuId = isset($post['id']) ? (int) $post['id'] : 0;
        if ($menuId <= 0 || !isset($_TABLES['menu'])) {
            return 'Invalid menu.';
        }

        $menuName = DB_getItem($_TABLES['menu'], 'menu_name', 'id=' . $menuId);
        if ($menuName === '' || $menuName === null || $menuName === false) {
            return 'The selected menu does not exist.';
        }
        if ($mode === 'deletemenu'
            && in_array((string) $menuName, array('navigation', 'footer', 'block'), true)) {
            return 'This built-in menu cannot be deleted.';
        }
        if ($mode === 'menuactivate') {
            $active = isset($post['active']) ? (int) $post['active'] : -1;
            if ($active !== 0 && $active !== 1) {
                return 'Invalid menu activation state.';
            }
        }

        return '';
    }

    $menuId = ($mode === 'move' || $mode === 'activate')
        ? (isset($post['menu']) ? (int) $post['menu'] : 0)
        : (isset($post['menuid']) ? (int) $post['menuid'] : 0);
    $mid = isset($post['mid']) ? (int) $post['mid'] : 0;

    if ($menuId <= 0 || $mid <= 0 || !isset($_TABLES['menu_elements'])) {
        return 'Invalid menu element.';
    }

    $elementId = DB_getItem(
        $_TABLES['menu_elements'],
        'id',
        'id=' . $mid . ' AND menu_id=' . $menuId
    );
    if ($elementId === '' || $elementId === null || $elementId === false) {
        return 'The menu element does not belong to the selected menu.';
    }

    if ($mode === 'activate') {
        $active = isset($post['active']) ? (int) $post['active'] : -1;
        if ($active !== 0 && $active !== 1) {
            return 'Invalid menu element activation state.';
        }
    }

    if ($mode === 'move') {
        $where = isset($post['where']) ? strtolower(trim((string) $post['where'])) : '';
        if ($where !== 'up' && $where !== 'down') {
            return 'Invalid menu movement direction.';
        }
    }

    return '';
}

/**
 * Return an empty string when an element create/edit mutation is structurally
 * valid, otherwise return a short administrator-facing error message.
 *
 * Existing unavailable plugin/static-page/topic destinations are allowed when
 * an administrator edits an element without changing that stored destination.
 * This preserves configuration non-destructively.
 *
 * @param string $mode
 * @param array  $post
 * @return string
 */
function MENU_adminElementMutationError($mode, $post)
{
    global $_TABLES, $_PLUGINS;

    if ($mode !== 'save' && $mode !== 'saveedit') {
        return '';
    }
    if (!is_array($post)) {
        return 'Invalid Menu element request.';
    }

    $menuId = $mode === 'saveedit'
        ? (isset($post['menu']) ? (int) $post['menu'] : 0)
        : (isset($post['menuid']) ? (int) $post['menuid'] : 0);
    if ($menuId <= 0 || !isset($_TABLES['menu'])) {
        return 'Invalid menu.';
    }

    $menuTypeValue = DB_getItem($_TABLES['menu'], 'menu_type', 'id=' . $menuId);
    if ($menuTypeValue === '' || $menuTypeValue === null || $menuTypeValue === false) {
        return 'The selected menu does not exist.';
    }
    $menuType = (int) $menuTypeValue;

    $currentType = null;
    $currentSubtype = '';
    $mid = 0;
    if ($mode === 'saveedit') {
        $mid = isset($post['id']) ? (int) $post['id'] : 0;
        if ($mid <= 0 || !isset($_TABLES['menu_elements'])) {
            return 'Invalid menu element.';
        }

        $result = DB_query(
            'SELECT element_type, element_subtype FROM ' . $_TABLES['menu_elements']
            . ' WHERE id=' . $mid . ' AND menu_id=' . $menuId
        );
        if (DB_numRows($result) === 0) {
            return 'The menu element does not belong to the selected menu.';
        }
        $row = DB_fetchArray($result);
        $currentType = (int) $row['element_type'];
        $currentSubtype = (string) $row['element_subtype'];
    }

    $pid = isset($post['pid']) ? (int) $post['pid'] : 0;
    if ($pid < 0 || ($mid > 0 && $pid === $mid)) {
        return 'Invalid parent menu element.';
    }
    if ($pid > 0) {
        $parentType = DB_getItem(
            $_TABLES['menu_elements'],
            'element_type',
            'id=' . $pid . ' AND menu_id=' . $menuId
        );
        if ($parentType === '' || $parentType === null || $parentType === false || (int) $parentType !== 1) {
            return 'The selected parent is not a submenu in this menu.';
        }
    }

    $afterId = isset($post['menuorder']) ? (int) $post['menuorder'] : 0;
    if ($afterId < 0 || ($mid > 0 && $afterId === $mid)) {
        return 'Invalid Display After element.';
    }
    if ($afterId > 0) {
        $afterPid = DB_getItem(
            $_TABLES['menu_elements'],
            'pid',
            'id=' . $afterId . ' AND menu_id=' . $menuId
        );
        if ($afterPid === '' || $afterPid === null || $afterPid === false || (int) $afterPid !== $pid) {
            return 'Display After must reference an element with the same parent.';
        }
    }

    $elementType = isset($post['menutype']) ? (int) $post['menutype'] : 0;
    if ($elementType < 1 || $elementType > 9) {
        return 'Invalid menu element type.';
    }

    $hasStaticPages = isset($_PLUGINS) && is_array($_PLUGINS)
        && in_array('staticpages', $_PLUGINS, true);
    if ($currentType === null || $elementType !== $currentType) {
        if (!MENU_elementTypeIsAllowed($menuType, $elementType, $hasStaticPages)) {
            return 'The selected element type is not available for this menu.';
        }
    }

    $target = isset($post['urltarget']) ? (string) $post['urltarget'] : '';
    if ($target !== '' && $target !== '_blank') {
        return 'Invalid URL target.';
    }

    if ($elementType === 2) {
        $action = isset($post['glfunction']) ? (int) $post['glfunction'] : -1;
        if ($action < 0 || $action > 5) {
            return 'Invalid Geeklog Action.';
        }
    } elseif ($elementType === 3) {
        $coreType = isset($post['gltype']) ? (int) $post['gltype'] : 0;
        if ($coreType < 1 || $coreType > 6) {
            return 'Invalid Geeklog Menu type.';
        }
    } elseif ($elementType === 4) {
        $plugin = isset($post['pluginname']) ? (string) $post['pluginname'] : '';
        if ($plugin === '') {
            return 'A plugin destination is required.';
        }
        if (!($currentType === 4 && $plugin === $currentSubtype)) {
            $pluginMenus = MENU_PLG_getMenuItems();
            if (!isset($pluginMenus[$plugin])) {
                return 'The selected plugin destination is unavailable.';
            }
        }
    } elseif ($elementType === 5) {
        $pageId = isset($post['spname']) ? (string) $post['spname'] : '';
        if ($pageId === '') {
            return 'A Static Page destination is required.';
        }
        if (!($currentType === 5 && $pageId === $currentSubtype)) {
            if (!$hasStaticPages || !isset($_TABLES['staticpage'])) {
                return 'The Static Pages plugin is unavailable.';
            }
            $escaped = function_exists('DB_escapeString') ? DB_escapeString($pageId) : addslashes($pageId);
            $found = DB_getItem($_TABLES['staticpage'], 'sp_id', "sp_id='" . $escaped . "'");
            if ($found === '' || $found === null || $found === false) {
                return 'The selected Static Page does not exist.';
            }
        }
    } elseif ($elementType === 6) {
        $url = isset($post['menuurl']) ? trim((string) $post['menuurl']) : '';
        if ($url === '') {
            return 'An External URL is required.';
        }
    } elseif ($elementType === 9) {
        $topicId = isset($post['topicname']) ? (string) $post['topicname'] : '';
        if ($topicId === '') {
            return 'A Topic destination is required.';
        }
        if (!($currentType === 9 && $topicId === $currentSubtype)) {
            if (!isset($_TABLES['topics'])) {
                return 'Topics are unavailable.';
            }
            $escaped = function_exists('DB_escapeString') ? DB_escapeString($topicId) : addslashes($topicId);
            $found = DB_getItem($_TABLES['topics'], 'tid', "tid='" . $escaped . "'");
            if ($found === '' || $found === null || $found === false) {
                return 'The selected Topic does not exist.';
            }
        }
    }

    return '';
}
