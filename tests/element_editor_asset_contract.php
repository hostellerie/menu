<?php
$view = file_get_contents(dirname(__DIR__) . '/admin_element_views.php');
$js = file_get_contents(dirname(__DIR__) . '/admin/js/element-editor.js');

if (substr_count($view, "setJavaScriptFile('menu_element_editor', '/admin/plugins/menu/js/element-editor.js')") !== 2) {
    fwrite(STDERR, "Element editor asset is not loaded by both create/edit views\n");
    exit(1);
}
if (strpos($view, 'function toggleFields()') !== false
    || strpos($view, 'onChange="toggleFields();"') !== false
    || strpos($view, "jQuery('#pid').change") !== false) {
    fwrite(STDERR, "Legacy inline element editor JavaScript remains\n");
    exit(1);
}
$required = array('syncFields', 'refreshOrder', "$('div#menu').show()", 'encodeURIComponent');
foreach ($required as $needle) {
    if (strpos($js, $needle) === false) {
        fwrite(STDERR, "Element editor asset missing behaviour: {$needle}\n");
        exit(1);
    }
}

echo "Element editor asset contract tests passed\n";
