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

$createCascading = MENU_getAllowedElementTypes($labels, 1, true, null, true);
menu_type_test_assert(isset($createCascading[1]), 'type 1 missing from cascading create');
menu_type_test_assert(isset($createCascading[2]), 'type 2 Geeklog Action missing from create');
menu_type_test_assert(isset($createCascading[3]), 'type 3 Geeklog Core missing from create');
menu_type_test_assert(isset($createCascading[9]), 'Topic must be available when topics exist');
menu_type_test_assert(!isset($createCascading[7]), 'PHP Function must be disabled by default');
menu_type_test_assert(array_keys($createCascading) === array(2, 3, 4, 5, 9, 6, 1, 8), 'cascading admin type order is inconsistent');
menu_type_test_assert(MENU_defaultElementType($createCascading) === 2, 'Geeklog Action must be the create default');

$createSimple = MENU_getAllowedElementTypes($labels, 2, true, null, true);
menu_type_test_assert(isset($createSimple[1]), 'type 1 Submenu must remain available in simple menu create');
menu_type_test_assert(isset($createSimple[2]), 'type 2 must remain available in simple menu create');
menu_type_test_assert(!isset($createSimple[3]), 'type 3 should be unavailable in simple menu create');
menu_type_test_assert(!isset($createSimple[7]), 'PHP Function must remain disabled by default');
menu_type_test_assert(array_keys($createSimple) === array(2, 4, 5, 9, 6, 1, 8), 'simple admin type order is inconsistent');
menu_type_test_assert(MENU_defaultElementType($createSimple) === 2, 'Geeklog Action must remain the simple-menu default');

$createVerticalSimple = MENU_getAllowedElementTypes($labels, 4, true, null, true);
menu_type_test_assert(isset($createVerticalSimple[1]), 'type 1 Submenu must be available in vertical-simple menu create');
menu_type_test_assert(!isset($createVerticalSimple[3]), 'type 3 should remain unavailable in vertical-simple menu create');

$editType2 = MENU_getAllowedElementTypes($labels, 1, true, 2, true);
menu_type_test_assert(isset($editType2[2]), 'stored type 2 must remain representable while editing');

$editLegacyType3 = MENU_getAllowedElementTypes($labels, 2, true, 3, true);
menu_type_test_assert(isset($editLegacyType3[3]), 'stored legacy type 3 must remain representable while editing');

$editLegacyPhp = MENU_getAllowedElementTypes($labels, 1, true, 7, true);
menu_type_test_assert(isset($editLegacyPhp[7]), 'stored PHP Function must remain representable while disabled globally');

$_MENU_CONF = array('allow_php_elements' => true);
$withPhpEnabled = MENU_getAllowedElementTypes($labels, 1, true, null, true);
menu_type_test_assert(isset($withPhpEnabled[7]), 'PHP Function must be offered when explicitly enabled');
menu_type_test_assert(array_keys($withPhpEnabled) === array(2, 3, 4, 5, 9, 6, 1, 8, 7), 'enabled PHP Function order is inconsistent');
unset($_MENU_CONF);

$withoutStaticPages = MENU_getAllowedElementTypes($labels, 1, false, null, true);
menu_type_test_assert(!isset($withoutStaticPages[5]), 'static page type should require Static Pages plugin');

$editLegacyStatic = MENU_getAllowedElementTypes($labels, 1, false, 5, true);
menu_type_test_assert(isset($editLegacyStatic[5]), 'stored static-page type must remain representable when plugin is unavailable');

$withoutTopics = MENU_getAllowedElementTypes($labels, 1, true, null, false);
menu_type_test_assert(!isset($withoutTopics[9]), 'Topic type should not be offered when no topics exist');

$editMissingTopic = MENU_getAllowedElementTypes($labels, 1, true, 9, false);
menu_type_test_assert(isset($editMissingTopic[9]), 'stored Topic type must remain representable when its topic is unavailable');

// Backward-compatible calls without the new hasTopics argument still assume
// topics may exist.
$legacyCall = MENU_getAllowedElementTypes($labels, 1, true, null);
menu_type_test_assert(isset($legacyCall[9]), 'legacy helper calls must retain Topic availability');

echo 'Element type tests passed' . PHP_EOL;
