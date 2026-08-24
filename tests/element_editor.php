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
$runtime = file_get_contents($root . '/element_editor_runtime.php');
$storage = file_get_contents($root . '/storage.php');
$english = file_get_contents($root . '/language/english.php');

menu_editor_assert($create !== false, 'create element template missing');
menu_editor_assert($edit !== false, 'edit element template missing');
menu_editor_assert($types !== false, 'element type helper missing');
menu_editor_assert($typeOptions !== false, 'type options endpoint missing');
menu_editor_assert($runtime !== false, 'element editor runtime missing');
menu_editor_assert($storage !== false, 'storage bootstrap missing');
menu_editor_assert($english !== false, 'English language file missing');

menu_editor_assert(strpos($runtime, "var panels = '#urldiv,#targetdiv,#glfunc,#glcorediv,#plugin,#staticpage,#topic,#phpdiv'") !== false, 'runtime panel list missing');
menu_editor_assert(strpos($runtime, "case '2': $('#glfunc').show();") !== false, 'runtime must show Action selector for Geeklog Action');
menu_editor_assert(strpos($runtime, "case '9': $('#topic').show();") !== false, 'runtime must show Topic selector for Topic');
menu_editor_assert(strpos($runtime, "mode === 'new' && select.find('option[value=\"2\"]')") !== false, 'runtime must immediately prefer Geeklog Action on create');
menu_editor_assert(strpos($runtime, "select.val('2')") !== false, 'runtime must select Geeklog Action immediately');
menu_editor_assert(strpos($runtime, 'type_options.php') !== false, 'runtime must load authoritative type options');
menu_editor_assert(strpos($runtime, "mode === 'edit' ? data.currentType : data.defaultType") !== false, 'runtime must distinguish create and edit type selection');
menu_editor_assert(strpos($storage, "require_once __DIR__ . '/element_editor_runtime.php';") !== false, 'runtime must be loaded by Menu bootstrap');

menu_editor_assert(strpos($create, "var adminOrder = ['2', '3', '4', '5', '9', '6', '1', '8', '7']") !== false, 'create fallback must use administrator-oriented order');
menu_editor_assert(strpos($create, "select.find('option[value=\"2\"]')") !== false, 'create fallback must prefer Geeklog Action');
menu_editor_assert(strpos($create, "select.val('2')") !== false, 'create fallback must select Geeklog Action');
menu_editor_assert(strpos($edit, "select.val(String(data.currentType))") !== false, 'edit fallback must preserve stored type');
menu_editor_assert(strpos($typeOptions, "'defaultType' => MENU_defaultElementType") !== false, 'endpoint must expose default type');
menu_editor_assert(strpos($types, '2, // Geeklog Action') !== false, 'admin order must start with Geeklog Action');
menu_editor_assert(strpos($types, '9, // Topic') !== false, 'Topic must be explicitly ordered');
menu_editor_assert(strpos($types, '7, // PHP Function') !== false, 'PHP Function must remain available as advanced type');

menu_editor_assert(strpos($create, 'for="pluginname"') !== false, 'create plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="pluginname"') !== false, 'edit plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="glfunction"') !== false, 'Geeklog Action label must target glfunction selector');
menu_editor_assert(strpos($english, "'geeklog_function'  => 'Action'") !== false, 'Geeklog Action selector must use the concise Action label');
menu_editor_assert(strpos($edit, "saveButton.attr('disabled', 'disabled')") !== false, 'edit save must remain blocked after type-loading failure');

echo "Element editor UI contract tests passed" . PHP_EOL;
