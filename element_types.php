<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | element_types.php                                                         |
// |                                                                           |
// | Shared rules for element types offered by create/edit forms.              |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

require_once __DIR__ . '/runtime_config.php';

/**
 * Return element types in an administrator-oriented order.
 *
 * The stored numeric ids are intentionally unchanged. Only their presentation
 * order is normalized so common destinations are grouped ahead of structural
 * and advanced entries.
 *
 * @return array
 */
function MENU_elementTypeAdminOrder()
{
    return array(
        2, // Geeklog Action
        3, // Geeklog Core
        4, // Plugin
        5, // Static Page
        9, // Topic
        6, // External URL
        1, // Submenu/container
        8, // Label/other
        7, // PHP Function (advanced)
    );
}

/**
 * Return the preferred type for a newly created element.
 *
 * Geeklog Action is a safe, useful default and is supported by all menu
 * presentation types. It also avoids silently creating structural submenus.
 *
 * @param array $types Allowed types keyed by stored type id
 * @return int|null
 */
function MENU_defaultElementType($types)
{
    if (isset($types[2])) {
        return 2;
    }

    foreach ($types as $typeId => $label) {
        return (int) $typeId;
    }

    return null;
}

/**
 * Return whether an element type is normally available for a menu type.
 *
 * Submenu/container elements are structural data and are valid for every menu
 * presentation type. Modern themes may render that hierarchy themselves, so
 * the legacy simple/cascading presentation choice must not remove hierarchy
 * from the underlying menu model.
 *
 * Geeklog core menu elements (type 3) remain unavailable for horizontal-simple
 * and vertical-simple legacy presentations. Static-page elements are
 * unavailable when the Static Pages plugin is not active. Topic elements are
 * unavailable for new items when no topics currently exist.
 *
 * @param int  $menuType
 * @param int  $elementType
 * @param bool $hasStaticPages
 * @param bool $hasTopics
 * @return bool
 */
function MENU_elementTypeIsAllowed($menuType, $elementType, $hasStaticPages, $hasTopics = true)
{
    $menuType = (int) $menuType;
    $elementType = (int) $elementType;

    if ($elementType === 7 && !MENU_runtimeConfigEnabled('allow_php_elements', false)) {
        return false;
    }

    if (!$hasStaticPages && $elementType === 5) {
        return false;
    }

    if (!$hasTopics && $elementType === 9) {
        return false;
    }

    if (($menuType === 2 || $menuType === 4) && $elementType === 3) {
        return false;
    }

    return true;
}

/**
 * Build the type list used by both create and edit forms.
 *
 * During editing, the current stored type is always retained even when it is
 * no longer normally allowed by the menu's current configuration. This makes
 * legacy elements representable and prevents silent type conversion.
 *
 * @param array    $labels
 * @param int      $menuType
 * @param bool     $hasStaticPages
 * @param int|null $currentType
 * @param bool     $hasTopics
 * @return array
 */
function MENU_getAllowedElementTypes($labels, $menuType, $hasStaticPages, $currentType = null, $hasTopics = true)
{
    $types = array();
    $currentType = $currentType === null ? null : (int) $currentType;
    $order = MENU_elementTypeAdminOrder();

    foreach ($order as $typeId) {
        if (!array_key_exists($typeId, $labels)) {
            continue;
        }

        if (MENU_elementTypeIsAllowed($menuType, $typeId, $hasStaticPages, $hasTopics)
            || ($currentType !== null && $typeId === $currentType)) {
            $types[$typeId] = $labels[$typeId];
        }
    }

    // Preserve any plugin-defined/future types not yet known by this version.
    foreach ($labels as $typeId => $typeLabel) {
        $typeId = (int) $typeId;
        if (isset($types[$typeId])) {
            continue;
        }
        if (MENU_elementTypeIsAllowed($menuType, $typeId, $hasStaticPages, $hasTopics)
            || ($currentType !== null && $typeId === $currentType)) {
            $types[$typeId] = $typeLabel;
        }
    }

    return $types;
}
