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
$create = file_get_contents($root . '/templates/default/createelement.thtml');
$edit = file_get_contents($root . '/templates/default/editelement.thtml');

menu_editor_assert($create !== false, 'create element template missing');
menu_editor_assert($edit !== false, 'edit element template missing');

$expectedCreate = array(
    "case '1'" => "jQuery('#urldiv').show();",
    "case '2'" => "jQuery('#glfunc').show();",
    "case '3'" => "jQuery('#glcorediv').show();",
    "case '4'" => "jQuery('#plugin').show();",
    "case '5'" => "jQuery('#staticpage-wrapper').show();",
    "case '6'" => "jQuery('#urldiv,#targetdiv').show();",
    "case '7'" => "jQuery('#phpdiv').show();",
    "case '9'" => "jQuery('#topic-wrapper').show();",
);

$expectedEdit = array(
    "case '1'" => "jQuery('#urldiv').show();",
    "case '2'" => "jQuery('#glfunc').show();",
    "case '3'" => "jQuery('#glcorediv').show();",
    "case '4'" => "jQuery('#plugin').show();",
    "case '5'" => "jQuery('#staticpage').show();",
    "case '6'" => "jQuery('#urldiv,#targetdiv').show();",
    "case '7'" => "jQuery('#phpdiv').show();",
    "case '9'" => "jQuery('#topic').show();",
);

foreach ($expectedCreate as $case => $action) {
    $casePos = strpos($create, $case);
    menu_editor_assert($casePos !== false, 'missing create mapping for ' . $case);
    $nextCase = strpos($create, 'case ', $casePos + strlen($case));
    $segment = $nextCase === false ? substr($create, $casePos) : substr($create, $casePos, $nextCase - $casePos);
    menu_editor_assert(strpos($segment, $action) !== false, 'wrong create visible fields for ' . $case);
}

foreach ($expectedEdit as $case => $action) {
    $casePos = strpos($edit, $case);
    menu_editor_assert($casePos !== false, 'missing edit mapping for ' . $case);
    $nextCase = strpos($edit, 'case ', $casePos + strlen($case));
    $segment = $nextCase === false ? substr($edit, $casePos) : substr($edit, $casePos, $nextCase - $casePos);
    menu_editor_assert(strpos($segment, $action) !== false, 'wrong edit visible fields for ' . $case);
}

foreach (array($create, $edit) as $template) {
    menu_editor_assert(strpos($template, 'function syncElementFields()') !== false, 'inline element field synchronizer missing');
    menu_editor_assert(strpos($template, "String(jQuery('#menutype').val() || '')") !== false, 'field synchronizer must read actual selected type');
    menu_editor_assert(strpos($template, 'style="display:none;"') !== false, 'conditional fields must be hidden by default');
    menu_editor_assert(strpos($template, 'syncElementFields();') !== false, 'initial field synchronization missing');
    menu_editor_assert(strpos($template, 'getJSON(') !== false, 'authoritative type refresh missing');
}

menu_editor_assert(strpos($create, 'for="pluginname"') !== false, 'create plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="pluginname"') !== false, 'edit plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="glfunction"') !== false, 'Geeklog Action label must target glfunction selector');
menu_editor_assert(strpos($edit, 'disabled="disabled"') !== false, 'edit save must be blocked before authoritative types load');
menu_editor_assert(strpos($edit, "saveButton.removeAttr('disabled')") !== false, 'edit save must be enabled after authoritative types load');
menu_editor_assert(strpos($create, '/plugins/menu/js/element-editor.js') === false, 'create must not depend on external editor JavaScript');
menu_editor_assert(strpos($edit, '/plugins/menu/js/element-editor.js') === false, 'edit must not depend on external editor JavaScript');

echo "Element editor UI contract tests passed" . PHP_EOL;
