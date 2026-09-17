<?php

$root = dirname(__DIR__);
$script = file_get_contents($root . '/admin/js/menu-tree-actions-1.4.0.js');
$template = file_get_contents($root . '/templates/default/menutree.thtml');
$module = file_get_contents($root . '/admin_element_views.php');
$library = file_get_contents($root . '/admin/js/tablednd_0_6.js');
$endpoint = file_get_contents($root . '/admin/tree-action.php');

$forbidden = array(
    'tablednd.js',
    'tablednd_0_5.js',
    "addEventListener('dragstart'",
    "document.addEventListener('mousemove'",
    'document.elementFromPoint',
);

foreach ($forbidden as $needle) {
    if (stripos($script, $needle) !== false
        || stripos($template, $needle) !== false) {
        fwrite(STDERR, "Obsolete ordering path remains: {$needle}\n");
        exit(1);
    }
}

$requiredScript = array(
    "typeof $.fn.tableDnD !== 'function'",
    '$table.tableDnD({',
    "dragHandle: 'menu-drag-handle'",
    "onDrop: function ()",
    "tree_action: 'order'",
    "orders: orders",
    "type: 'POST'",
    'data[tokenName] = tokenValue',
    'refreshToken(response)',
    "tree_action: 'activate'",
);
foreach ($requiredScript as $needle) {
    if (strpos($script, $needle) === false) {
        fwrite(STDERR, "TableDnD ordering behavior missing: {$needle}\n");
        exit(1);
    }
}

$requiredEndpoint = array(
    "MENU_adminCheckToken()",
    "MENU_saveElementOrder",
    "MENU_changeActiveStatusElement",
    "MENU_adminCreateToken()",
    "'tokenValue' => $newToken",
);
foreach ($requiredEndpoint as $needle) {
    if (strpos($endpoint, $needle) === false) {
        fwrite(STDERR, "Tree action endpoint behavior missing: {$needle}\n");
        exit(1);
    }
}

if (strpos($library, 'jQuery.tableDnD') === false
    || strpos($library, 'jQuery.fn.extend') === false
    || strpos($library, 'tableDnD : jQuery.tableDnD.build') === false) {
    fwrite(STDERR, "TableDnD 0.6 library contract is incomplete\n");
    exit(1);
}

$treeStart = strpos($module, 'function MENU_displayTree');
$treeEnd = strpos($module, 'function MENU_createElement', $treeStart);
$treeBody = substr($module, $treeStart, $treeEnd - $treeStart);
$jqueryPos = strpos($treeBody, "setJavaScriptLibrary('jquery')");
$libraryPos = strpos($treeBody, "setJavaScriptFile('menu_tablednd', '/admin/plugins/menu/js/tablednd_0_6.js')");
if ($jqueryPos === false || $libraryPos === false || $jqueryPos >= $libraryPos) {
    fwrite(STDERR, "Ordering assets must be registered after jQuery and in dependency order\n");
    exit(1);
}

if (strpos($template, 'id="menu-order-token"') === false
    || strpos($template, 'data-menuid="{menuid}"') === false
    || strpos($template, 'data-tree-action-url=') === false
    || strpos($template, 'menu-tree-actions-1.4.0.js') === false) {
    fwrite(STDERR, "Ordering template metadata is incomplete\n");
    exit(1);
}

echo "TableDnD menu ordering contract tests passed\n";
