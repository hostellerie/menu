<?php

$root = dirname(__DIR__);
$index = file_get_contents($root . '/admin/index.php');
$module = file_get_contents($root . '/admin_element_views.php');
$template = file_get_contents($root . '/templates/default/menutree.thtml');

$functions = array(
    'MENU_displayTree',
    'MENU_createElement',
    'MENU_editElement',
    'MENU_menuConfig',
);

foreach ($functions as $name) {
    $needle = 'function ' . $name;
    if (strpos($module, $needle) === false) {
        fwrite(STDERR, "Missing admin element view helper: {$name}\n");
        exit(1);
    }
    if (strpos($index, $needle) !== false) {
        fwrite(STDERR, "Admin element view helper still duplicated in index.php: {$name}\n");
        exit(1);
    }
}

if (strpos($index, 'admin_element_views.php') === false) {
    fwrite(STDERR, "admin/index.php does not load admin_element_views.php\n");
    exit(1);
}

if (strpos($module, 'MENU_adminTokenInput()') === false) {
    fwrite(STDERR, "Admin element views lost security token fields\n");
    exit(1);
}

if (strpos($module, "'menu_alignment' => \$defaultAlignment") === false
    || strpos($module, "'use_images' => 0") === false
    || strpos($module, "isset(\$Menus[\$mid]['config'])") === false) {
    fwrite(STDERR, "Admin menu configuration must tolerate missing legacy config rows\n");
    exit(1);
}

if (strpos($module, "isset(\$LANG_VS[\$name]) ? \$LANG_VS[\$name]") === false
    || strpos($module, "isset(\$LANG_VC[\$name]) ? \$LANG_VC[\$name]") === false
    || strpos($module, "isset(\$LANG_MENU01[\$name]) ? \$LANG_MENU01[\$name] : \$name") === false) {
    fwrite(STDERR, "Per-type menu labels must fall back safely when translations are incomplete\n");
    exit(1);
}

$treeStart = strpos($module, 'function MENU_displayTree');
$treeEnd = strpos($module, 'function MENU_createElement', $treeStart);
$treeBody = substr($module, $treeStart, $treeEnd - $treeStart);
$requirements = array(
    "setJavaScriptLibrary('jquery')",
    "setJavaScriptFile('menu_tablednd', '/admin/plugins/menu/js/tablednd_0_6.js')",
    "setJavaScriptFile('menu_order_handle', '/admin/plugins/menu/js/menu-order-handle.js')",
);
foreach ($requirements as $needle) {
    if (strpos($treeBody, $needle) === false) {
        fwrite(STDERR, "Admin tree view lost required ordering asset registration: {$needle}\n");
        exit(1);
    }
}

if (strpos($template, 'tablednd_0_6.js') !== false
    || strpos($template, 'menu-order-handle.js') !== false) {
    fwrite(STDERR, "Admin tree template must not inject ordering assets directly\n");
    exit(1);
}

// Editing menus without Static Pages must not reference an uninitialized
// template variable, even when the optional plugin is inactive.
$editStart = strpos($module, 'function MENU_editElement');
$editEnd = strpos($module, 'function MENU_menuConfig', $editStart);
$editBody = substr($module, $editStart, $editEnd - $editStart);
$initPos = strpos($editBody, "\$sp_select = '';");
$usePos = strpos($editBody, "'sp_select'         => \$sp_select");
if ($editStart === false || $editEnd === false || $initPos === false
    || $usePos === false || $initPos >= $usePos) {
    fwrite(STDERR, "Edit element view must always initialize optional Static Pages select before template use\n");
    exit(1);
}
echo "Admin element view module contract tests passed\n";
