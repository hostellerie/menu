<?php

function menu_order_test_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$root = dirname(__DIR__);
$template = file_get_contents($root . '/templates/default/createelement.thtml');
$endpoint = file_get_contents($root . '/admin/getorder.php');
$views = file_get_contents($root . '/admin_element_views.php');
$editor = file_get_contents($root . '/admin/js/element-editor.js');

menu_order_test_assert(
    strpos($views, '$lastOrderIndex = count($orderRows) - 1;') !== false
        && strpos($views, '$orderIndex === $lastOrderIndex') !== false
        && strpos($views, ' selected="selected"') !== false,
    'Create form must select the last Display After option server-side'
);
menu_order_test_assert(
    strpos($template, "orderSelect.prop('selectedIndex'") === false,
    'Create form must not rely on JavaScript to choose the default order'
);
menu_order_test_assert(
    strpos($editor, 'function refreshOrder()') !== false
        && strpos($editor, "'getorder.php?optionid=' + encodeURIComponent(parent)") !== false
        && strpos($editor, "$('#pid').off('change.menuElementEditor')") !== false,
    'Parent selection must refresh Display After options through the shared editor asset'
);
menu_order_test_assert(
    strpos($endpoint, 'AND pid=\' . $parentId') !== false,
    'Order endpoint must scope siblings to the selected parent'
);
menu_order_test_assert(
    strpos($endpoint, "ORDER BY element_order ASC, id ASC") !== false,
    'Order endpoint must return siblings in deterministic order'
);
menu_order_test_assert(
    strpos($endpoint, '$index === $lastIndex') !== false
        && strpos($endpoint, ' selected="selected"') !== false,
    'Order endpoint must select the last sibling by default'
);
menu_order_test_assert(
    strpos($endpoint, "AND element_type=1") !== false,
    'Order endpoint must only accept submenu parents from the current menu'
);

echo 'Order option contract tests passed' . PHP_EOL;
