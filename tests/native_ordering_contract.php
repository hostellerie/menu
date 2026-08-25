<?php

$root = dirname(__DIR__);
$script = file_get_contents($root . '/admin/js/menu-order-handle.js');
$template = file_get_contents($root . '/templates/default/menutree.thtml');
$library = file_get_contents($root . '/admin/js/tablednd_0_6.js');

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
    "$table.tableDnD({",
    "dragHandle: 'menu-drag-handle'",
    "onDrop: function ()",
    "type: 'POST'",
    "orders: orders",
    "mode: 'move'",
    "where: direction",
);
foreach ($requiredScript as $needle) {
    if (strpos($script, $needle) === false) {
        fwrite(STDERR, "TableDnD ordering behavior missing: {$needle}\n");
        exit(1);
    }
}

if (strpos($library, 'jQuery.tableDnD') === false
    || strpos($library, '$.fn.tableDnD') === false) {
    fwrite(STDERR, "TableDnD 0.6 library contract is incomplete\n");
    exit(1);
}

$libraryPos = strpos($template, '{site_admin_url}/plugins/menu/js/tablednd_0_6.js');
$adapterPos = strpos($template, '{site_admin_url}/plugins/menu/js/menu-order-handle.js');
if ($libraryPos === false || $adapterPos === false || $libraryPos >= $adapterPos) {
    fwrite(STDERR, "TableDnD must load before the Menu ordering adapter\n");
    exit(1);
}

if (strpos($template, 'id="menu-order-token"') === false
    || strpos($template, 'data-menuid="{menuid}"') === false
    || strpos($template, 'data-post-url=') === false) {
    fwrite(STDERR, "Ordering template metadata is incomplete\n");
    exit(1);
}

echo "TableDnD menu ordering contract tests passed\n";
