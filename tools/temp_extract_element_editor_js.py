from pathlib import Path
import re

view_path = Path('admin_element_views.php')
js_path = Path('admin/js/element-editor.js')
test_path = Path('tests/element_editor_asset_contract.php')

view = view_path.read_text()

for function_name in ('MENU_createElement', 'MENU_editElement'):
    start = view.find('function ' + function_name)
    if start < 0:
        raise SystemExit(function_name + ' not found')
    next_func = view.find('\nfunction MENU_', start + 1)
    if next_func < 0:
        next_func = len(view)
    block = view[start:next_func]

    library = "    $_SCRIPTS->setJavaScriptLibrary('jquery');\n"
    asset = library + "    $_SCRIPTS->setJavaScriptFile('menu_element_editor', '/admin/plugins/menu/js/element-editor.js');\n"
    if library not in block:
        raise SystemExit(function_name + ' jQuery registration not found')
    block = block.replace(library, asset, 1)

    block, count = re.subn(
        r'\n    \$js = "<script type=\\"text/javascript\\">.*?\n    \$_SCRIPTS->setJavaScript\(\$js\);\n',
        '\n',
        block,
        count=1,
        flags=re.S,
    )
    if count != 1:
        raise SystemExit(function_name + ' inline editor JavaScript block not found')

    block = block.replace(' onChange="toggleFields();"', '')
    view = view[:start] + block + view[next_func:]

view_path.write_text(view)

js_path.write_text(r'''/* Menu element editor behaviour. Compatible with Geeklog 2.1.1+. */
(function ($, window) {
    'use strict';

    var fieldIds = [
        '#urldiv',
        '#targetdiv',
        '#glcorediv',
        '#plugin',
        '#staticpage',
        '#glfunc',
        '#phpdiv',
        '#topic'
    ];

    function hideAll() {
        $.each(fieldIds, function (index, selector) {
            $(selector).hide();
        });
    }

    function syncFields() {
        var type = String($('#menutype').val() || '');

        hideAll();

        switch (type) {
            case '1':
                $('#urldiv').show();
                break;
            case '2':
                $('#glfunc').show();
                break;
            case '3':
                $('#glcorediv').show();
                break;
            case '4':
                $('#plugin').show();
                break;
            case '5':
                $('#staticpage').show();
                break;
            case '6':
                $('#urldiv, #targetdiv').show();
                break;
            case '7':
                $('#phpdiv').show();
                break;
            case '8':
                break;
            case '9':
                $('#topic').show();
                break;
        }
    }

    function menuId() {
        var value = $('#menunid').val();
        if (typeof value === 'undefined') {
            value = $('input[name="menu"]').val();
        }
        return String(value || '');
    }

    function refreshOrder() {
        var parent = String($('#pid').val() || '0');
        var menu = menuId();
        if (!menu) {
            return;
        }
        $('#displayafter').load(
            'getorder.php?optionid=' + encodeURIComponent(parent)
            + '&menuid=' + encodeURIComponent(menu)
        );
    }

    function init() {
        var select = $('#menutype');
        if (!select.length) {
            return;
        }

        $('div#menu').show();
        select.off('change.menuElementEditor')
            .on('change.menuElementEditor', syncFields);
        $('#pid').off('change.menuElementEditor')
            .on('change.menuElementEditor', refreshOrder);
        syncFields();
    }

    window.MENUElementEditor = {
        init: init,
        syncFields: syncFields,
        refreshOrder: refreshOrder
    };

    $(init);
}(jQuery, window));
''')

test_path.write_text(r'''<?php
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
''')
