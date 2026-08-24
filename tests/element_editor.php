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
$types = file_get_contents($root . '/element_types.php');
$typeOptions = file_get_contents($root . '/admin/type_options.php');

menu_editor_assert($create !== false, 'create element template missing');
menu_editor_assert($edit !== false, 'edit element template missing');
menu_editor_assert($types !== false, 'element type helper missing');
menu_editor_assert($typeOptions !== false, 'type options endpoint missing');

$expectedPanels = array(
    "case '1'" => "jQuery('#urldiv').show();",
    "case '2'" => "jQuery('#glfunc').show();",
    "case '3'" => "jQuery('#glcorediv').show();",
    "case '4'" => "jQuery('#plugin').show();",
    "case '5'" => "jQuery('#staticpage').show();",
    "case '6'" => "jQuery('#urldiv,#targetdiv').show();",
    "case '7'" => "jQuery('#phpdiv').show();",
    "case '8'" => 'break;',
    "case '9'" => "jQuery('#topic').show();",
);

foreach (array($create, $edit) as $template) {
    menu_editor_assert(strpos($template, 'var panels =') !== false, 'central panel list missing');
    menu_editor_assert(strpos($template, 'syncElementFields') !== false, 'field synchronizer missing');
    menu_editor_assert(strpos($template, "off('change.menuElementEditor')") !== false, 'type change handler missing');
    menu_editor_assert(strpos($template, 'style="display:none;"') !== false, 'conditional fields must be hidden by default');

    foreach ($expectedPanels as $case => $action) {
        $casePos = strpos($template, $case);
        menu_editor_assert($casePos !== false, 'missing editor mapping for ' . $case);
        $nextCase = strpos($template, 'case ', $casePos + strlen($case));
        $segment = $nextCase === false ? substr($template, $casePos) : substr($template, $casePos, $nextCase - $casePos);
        menu_editor_assert(strpos($segment, $action) !== false, 'wrong visible fields for ' . $case);
    }
}

menu_editor_assert(strpos($create, "select.val(String(data.defaultType))") !== false, 'create form must apply stable default type');
menu_editor_assert(strpos($edit, "select.val(String(data.currentType))") !== false, 'edit form must preserve stored type');
menu_editor_assert(strpos($typeOptions, "'defaultType' => MENU_defaultElementType") !== false, 'endpoint must expose default type');
menu_editor_assert(strpos($types, '2, // Geeklog Action') !== false, 'admin order must start with Geeklog Action');
menu_editor_assert(strpos($types, '9, // Topic') !== false, 'Topic must be explicitly ordered');
menu_editor_assert(strpos($types, '7, // PHP Function') !== false, 'PHP Function must remain available as advanced type');

menu_editor_assert(strpos($create, 'for="pluginname"') !== false, 'create plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="pluginname"') !== false, 'edit plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="glfunction"') !== false, 'Geeklog Action label must target glfunction selector');
menu_editor_assert(strpos($edit, "saveButton.attr('disabled', 'disabled')") !== false, 'edit save must remain blocked after type-loading failure');

echo "Element editor UI contract tests passed" . PHP_EOL;
