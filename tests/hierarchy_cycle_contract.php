<?php

$root = dirname(__DIR__);
$validation = file_get_contents($root . '/admin_element_validation.php');
$views = file_get_contents($root . '/admin_element_views.php');

$requiredValidation = array(
    'function MENU_adminDescendantIds(',
    "'SELECT id,pid FROM '",
    'array_shift($queue)',
    'in_array($pid, MENU_adminDescendantIds($menuId, $mid), true)',
    'cannot use one of its descendants as parent',
);
foreach ($requiredValidation as $needle) {
    if (strpos($validation, $needle) === false) {
        fwrite(STDERR, "Missing hierarchy cycle guard: {$needle}\n");
        exit(1);
    }
}

$editStart = strpos($views, 'function MENU_editElement');
$editEnd = strpos($views, 'function MENU_menuConfig', $editStart);
$editBody = substr($views, $editStart, $editEnd - $editStart);
if (strpos($editBody, 'array_flip(MENU_adminDescendantIds($menu_id, $mid))') === false
    || strpos($editBody, 'isset($blockedParentIds[(int) $row[\'id\']])') === false) {
    fwrite(STDERR, "Edit parent selector does not filter descendants\n");
    exit(1);
}

echo "Hierarchy cycle contract tests passed\n";
