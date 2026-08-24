<?php

// Element editor UI regression checks. Compatible with PHP 5.6+.

function menu_editor_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$root = dirname(__DIR__);
$js = file_get_contents($root . '/admin/js/element-editor.js');
$create = file_get_contents($root . '/templates/default/createelement.thtml');
$edit = file_get_contents($root . '/templates/default/editelement.thtml');

menu_editor_assert($js !== false, 'element editor JavaScript missing');
menu_editor_assert($create !== false, 'create element template missing');
menu_editor_assert($edit !== false, 'edit element template missing');

$expected = array(
    "case '1'" => "$('#urldiv').show();",
    "case '2'" => "$('#glfunc').show();",
    "case '3'" => "$('#glcorediv').show();",
    "case '4'" => "$('#plugin').show();",
    "case '5'" => "$('#staticpage').show();",
    "case '6'" => "$('#urldiv, #targetdiv').show();",
    "case '7'" => "$('#phpdiv').show();",
    "case '8'" => 'break;',
    "case '9'" => "$('#topic').show();",
);

foreach ($expected as $case => $action) {
    $casePos = strpos($js, $case);
    menu_editor_assert($casePos !== false, 'missing editor mapping for ' . $case);
    $nextCase = strpos($js, 'case ', $casePos + strlen($case));
    $segment = $nextCase === false ? substr($js, $casePos) : substr($js, $casePos, $nextCase - $casePos);
    menu_editor_assert(strpos($segment, $action) !== false, 'wrong visible fields for ' . $case);
}

foreach (array($create, $edit) as $template) {
    menu_editor_assert(strpos($template, '/plugins/menu/js/element-editor.js') !== false, 'shared editor script not loaded');
    menu_editor_assert(strpos($template, 'MENUElementEditor.init()') !== false, 'editor not initialized on page load');
    menu_editor_assert(strpos($template, 'MENUElementEditor.syncFields()') !== false, 'editor not synchronized after AJAX type refresh');
}

menu_editor_assert(strpos($create, 'for="pluginname"') !== false, 'create plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="pluginname"') !== false, 'edit plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="glfunction"') !== false, 'Geeklog Action label must target glfunction selector');
menu_editor_assert(strpos($edit, "save.attr('disabled', 'disabled')") !== false, 'edit save must be blocked before authoritative types load');
menu_editor_assert(strpos($edit, "save.removeAttr('disabled')") !== false, 'edit save must be enabled after authoritative types load');
menu_editor_assert(strpos($edit, '.fail(function ()') !== false, 'edit type loading failure must be handled');
menu_editor_assert(strpos($edit, 'Reload this page before saving.') !== false, 'edit type loading failure must explain recovery');

echo "Element editor UI contract tests passed" . PHP_EOL;
