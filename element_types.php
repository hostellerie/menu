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

/**
 * Return whether an element type is normally available for a menu type.
 *
 * Horizontal-simple and vertical-simple menus cannot contain submenu holder
 * (type 1) or Geeklog core menu (type 3) elements. Static-page elements are
 * unavailable when the Static Pages plugin is not active.
 *
 * @param int  $menuType
 * @param int  $elementType
 * @param bool $hasStaticPages
 * @return bool
 */
function MENU_elementTypeIsAllowed($menuType, $elementType, $hasStaticPages)
{
    $menuType = (int) $menuType;
    $elementType = (int) $elementType;

    if (!$hasStaticPages && $elementType === 5) {
        return false;
    }

    if (($menuType === 2 || $menuType === 4)
        && ($elementType === 1 || $elementType === 3)) {
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
 * @return array
 */
function MENU_getAllowedElementTypes($labels, $menuType, $hasStaticPages, $currentType = null)
{
    $types = array();
    $currentType = $currentType === null ? null : (int) $currentType;

    foreach ($labels as $typeId => $typeLabel) {
        $typeId = (int) $typeId;
        if (MENU_elementTypeIsAllowed($menuType, $typeId, $hasStaticPages)
            || ($currentType !== null && $typeId === $currentType)) {
            $types[$typeId] = $typeLabel;
        }
    }

    return $types;
}
