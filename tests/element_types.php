<?php

define('VERSION', '2.1.1');
require_once dirname(__DIR__) . '/element_types.php';

function menu_type_test_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$labels = array(
    1 => 'Submenu',
    2 => 'Geeklog Action',
    3 => 'Geeklog Core',
    4 => 'Plugin',
    5 => 'Static Page',
    6 => 'URL',
    7 => 'PHP Function',
    8 => 'Other',
    9 => 'Topic',
);

$createCascading = MENU_getAllowedElementTypes($labels, 1, true, null);
menu_type_test_assert(isset($createCascading[1]), 'type 1 missing from cascading create');
menu_type_test_assert(isset($createCascading[2]), 'type 2 Geeklog Action missing from create');
menu_type_test_assert(isset($createCascading[3]), 'type 3 Geeklog Core missing from create');

$createSimple = MENU_getAllowedElementTypes($labels, 2, true, null);
menu_type_test_assert(!isset($createSimple[1]), 'type 1 should be unavailable in simple menu create');
menu_type_test_assert(isset($createSimple[2]), 'type 2 must remain available in simple menu create');
menu_type_test_assert(!isset($createSimple[3]), 'type 3 should be unavailable in simple menu create');

$editType2 = MENU_getAllowedElementTypes($labels, 1, true, 2);
menu_type_test_assert(isset($editType2[2]), 'stored type 2 must remain representable while editing');

$editLegacyType3 = MENU_getAllowedElementTypes($labels, 2, true, 3);
menu_type_test_assert(isset($editLegacyType3[3]), 'stored legacy type 3 must remain representable while editing');

$withoutStaticPages = MENU_getAllowedElementTypes($labels, 1, false, null);
menu_type_test_assert(!isset($withoutStaticPages[5]), 'static page type should require Static Pages plugin');

$editLegacyStatic = MENU_getAllowedElementTypes($labels, 1, false, 5);
menu_type_test_assert(isset($editLegacyStatic[5]), 'stored static-page type must remain representable when plugin is unavailable');

echo 'Element type tests passed' . PHP_EOL;
