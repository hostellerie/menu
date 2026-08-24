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

menu_editor_assert(strpos($runtime, 'function MENU_elementEditorServerState()') !== false, 'stored editor state missing');
menu_editor_assert(strpos($runtime, 'SELECT element_type, element_subtype') !== false, 'editor must read stored type and subtype directly from database');
menu_editor_assert(strpos($runtime, "'currentType' => null") !== false, 'editor state must expose current type');
menu_editor_assert(strpos($runtime, "'currentSubtype' => ''") !== false, 'editor state must expose current subtype');
menu_editor_assert(strpos($runtime, "var panels = '#urldiv,#targetdiv,#glfunc,#glcorediv,#plugin,#staticpage,#topic,#phpdiv'") !== false, 'runtime panel list missing');
menu_editor_assert(strpos($runtime, "case '1': $('#urldiv').show();") !== false, 'runtime must show URL for Submenu');
menu_editor_assert(strpos($runtime, "case '2': $('#glfunc').show();") !== false, 'runtime must show Action selector for Geeklog Action');
menu_editor_assert(strpos($runtime, "case '3': $('#glcorediv').show();") !== false, 'runtime must show Geeklog Menu selector for Geeklog Core');
menu_editor_assert(strpos($runtime, "case '4': $('#plugin').show();") !== false, 'runtime must show Plugin selector for Plugin');
menu_editor_assert(strpos($runtime, "case '5': $('#staticpage').show();") !== false, 'runtime must show Static Page selector');
menu_editor_assert(strpos($runtime, "case '6': $('#urldiv,#targetdiv').show();") !== false, 'runtime must show URL and target for External URL');
menu_editor_assert(strpos($runtime, "case '7': $('#phpdiv').show();") !== false, 'runtime must show PHP Function field');
menu_editor_assert(strpos($runtime, "case '9': $('#topic').show();") !== false, 'runtime must show Topic selector for Topic');
menu_editor_assert(strpos($runtime, "resource = 'plugin'") !== false, 'editor must identify unavailable plugins');
menu_editor_assert(strpos($runtime, "resource = 'static page'") !== false, 'editor must identify unavailable Static Pages');
menu_editor_assert(strpos($runtime, "resource = 'topic'") !== false, 'editor must identify unavailable Topics');
menu_editor_assert(strpos($runtime, "'[Unavailable ' + kind + '] '") !== false, 'missing resources must remain identifiable in admin');
menu_editor_assert(strpos($runtime, 'data.currentSubtype, data.resource') !== false, 'editor must use authoritative resource status');
menu_editor_assert(strpos($runtime, 'type_options.php') !== false, 'runtime must load type labels only after Geeklog initialization');
menu_editor_assert(strpos($runtime, "data.types.length === 0") !== false, 'runtime must reject an empty authoritative type list');
menu_editor_assert(strpos($runtime, 'select.empty();') !== false, 'runtime must replace the type list after a valid response');
menu_editor_assert(strpos($runtime, "if (!applyTypeResponse(data) && state.mode === 'edit')") !== false, 'edit must fail closed when type restoration is invalid');
menu_editor_assert(strpos($storage, "require_once __DIR__ . '/element_editor_runtime.php';") !== false, 'runtime must be loaded by Menu bootstrap');

menu_editor_assert(strpos($create, "var adminOrder = ['2', '3', '4', '5', '9', '6', '1', '8', '7']") !== false, 'create fallback must use administrator-oriented order');
menu_editor_assert(strpos($create, "select.find('option[value=\"2\"]')") !== false, 'create fallback must prefer Geeklog Action');
menu_editor_assert(strpos($create, "select.val('2')") !== false, 'create fallback must select Geeklog Action');
menu_editor_assert(strpos($edit, 'type_options.php') === false, 'edit template must not duplicate authoritative AJAX logic');
menu_editor_assert(strpos($edit, 'disabled="disabled"') !== false, 'edit save must remain disabled until authoritative runtime initializes');

menu_editor_assert(strpos($typeOptions, "DB_getItem(\$_TABLES['menu'], 'menu_type'") !== false, 'type endpoint must read menu type directly from database');
menu_editor_assert(strpos($typeOptions, 'SELECT element_type, element_subtype') !== false, 'type endpoint must read stored type and subtype together');
menu_editor_assert(strpos($typeOptions, "'currentType' => \$currentType") !== false, 'type endpoint must expose stored current type');
menu_editor_assert(strpos($typeOptions, "'currentSubtype' => \$currentSubtype") !== false, 'type endpoint must expose stored current subtype');
menu_editor_assert(strpos($typeOptions, "'resource' => \$resource") !== false, 'type endpoint must expose destination availability');
menu_editor_assert(strpos($typeOptions, "\$currentType === 4") !== false, 'type endpoint must validate plugin destinations');
menu_editor_assert(strpos($typeOptions, "\$currentType === 5") !== false, 'type endpoint must validate Static Page destinations');
menu_editor_assert(strpos($typeOptions, "\$currentType === 9") !== false, 'type endpoint must validate Topic destinations');
menu_editor_assert(strpos($typeOptions, "'defaultType' => MENU_defaultElementType") !== false, 'type endpoint must expose create default type');

menu_editor_assert(strpos($types, '2, // Geeklog Action') !== false, 'admin order must start with Geeklog Action');
menu_editor_assert(strpos($types, '9, // Topic') !== false, 'Topic must be explicitly ordered');
menu_editor_assert(strpos($types, '7, // PHP Function') !== false, 'PHP Function must remain available as advanced type');
menu_editor_assert(strpos($types, '|| ($currentType !== null && $typeId === $currentType)') !== false, 'stored legacy/current type must always remain representable while editing');

menu_editor_assert(strpos($create, 'for="pluginname"') !== false, 'create plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="pluginname"') !== false, 'edit plugin label must target plugin selector');
menu_editor_assert(strpos($edit, 'for="glfunction"') !== false, 'Geeklog Action label must target glfunction selector');
menu_editor_assert(strpos($english, "'geeklog_function'  => 'Action'") !== false, 'Geeklog Action selector must use the concise Action label');

echo "Element editor UI contract tests passed" . PHP_EOL;
