<?php

$functionsPath = __DIR__ . '/../functions.inc';
$functions = file_get_contents($functionsPath);
$old = <<<'PHP'
$langfile = $plugin_path . 'language/' . $_CONF['language'] . '.php';

if (file_exists($langfile)) {
    require_once $langfile;
} else {
    require_once $plugin_path . 'language/english.php';
}
PHP;
$new = <<<'PHP'
$langfile = $plugin_path . 'language/' . $_CONF['language'] . '.php';
$englishLangFile = $plugin_path . 'language/english.php';

// English is the canonical language. Load it first and keep it as the
// per-key fallback when a translation file is incomplete.
require_once $englishLangFile;

$menuEnglishLanguage = array(
    'LANG_MENU_1' => $LANG_MENU_1,
    'LANG_MENU00' => $LANG_MENU00,
    'LANG_MENU01' => $LANG_MENU01,
    'LANG_HC' => $LANG_HC,
    'LANG_HS' => $LANG_HS,
    'LANG_VC' => $LANG_VC,
    'LANG_VS' => $LANG_VS,
    'LANG_MENU_MENU_TYPES' => $LANG_MENU_MENU_TYPES,
    'LANG_MENU_TYPES' => $LANG_MENU_TYPES,
    'LANG_MENU_TARGET' => $LANG_MENU_TARGET,
    'LANG_MENU_GLFUNCTION' => $LANG_MENU_GLFUNCTION,
    'LANG_MENU_GLTYPES' => $LANG_MENU_GLTYPES,
    'LANG_MENU_ADMIN' => $LANG_MENU_ADMIN,
    'LANG_MENU_GLTYPES_HELP' => $LANG_MENU_GLTYPES_HELP,
    'LANG_MENU_GLFUNCTION_HELP' => $LANG_MENU_GLFUNCTION_HELP,
    'LANG_MENU_TYPES_HELP' => $LANG_MENU_TYPES_HELP,
);

$menuEnglishConfigLanguage = array(
    'LANG_configsections' => isset($LANG_configsections['menu']) ? $LANG_configsections['menu'] : array(),
    'LANG_confignames' => isset($LANG_confignames['menu']) ? $LANG_confignames['menu'] : array(),
    'LANG_configsubgroups' => isset($LANG_configsubgroups['menu']) ? $LANG_configsubgroups['menu'] : array(),
    'LANG_tab' => isset($LANG_tab['menu']) ? $LANG_tab['menu'] : array(),
    'LANG_fs' => isset($LANG_fs['menu']) ? $LANG_fs['menu'] : array(),
    'LANG_configselects' => isset($LANG_configselects['menu']) ? $LANG_configselects['menu'] : array(),
);

if ($langfile !== $englishLangFile && file_exists($langfile)) {
    require $langfile;

    foreach ($menuEnglishLanguage as $languageVariable => $englishValues) {
        $translatedValues = isset(${$languageVariable}) && is_array(${$languageVariable})
            ? ${$languageVariable}
            : array();
        ${$languageVariable} = array_replace($englishValues, $translatedValues);
    }

    foreach ($menuEnglishConfigLanguage as $languageVariable => $englishValues) {
        $translatedValues = isset(${$languageVariable}['menu']) && is_array(${$languageVariable}['menu'])
            ? ${$languageVariable}['menu']
            : array();
        ${$languageVariable}['menu'] = array_replace($englishValues, $translatedValues);
    }
}

unset($menuEnglishLanguage, $menuEnglishConfigLanguage);
PHP;

if (strpos($functions, $old) === false) {
    fwrite(STDERR, "Language loader block not found\n");
    exit(1);
}
file_put_contents($functionsPath, str_replace($old, $new, $functions));

function loadMenuLanguageFile($file)
{
    $LANG_configsections = array();
    $LANG_confignames = array();
    $LANG_configsubgroups = array();
    $LANG_tab = array();
    $LANG_fs = array();
    $LANG_configselects = array();
    include $file;
    return get_defined_vars();
}

$english = loadMenuLanguageFile(__DIR__ . '/../language/english.php');
$persian = loadMenuLanguageFile(__DIR__ . '/../language/persian_utf-8.php');
$arrayNames = array(
    'LANG_MENU_1', 'LANG_MENU00', 'LANG_MENU01', 'LANG_HC', 'LANG_HS',
    'LANG_VC', 'LANG_VS', 'LANG_MENU_MENU_TYPES', 'LANG_MENU_TYPES',
    'LANG_MENU_TARGET', 'LANG_MENU_GLFUNCTION', 'LANG_MENU_GLTYPES',
    'LANG_MENU_ADMIN', 'LANG_MENU_GLTYPES_HELP', 'LANG_MENU_GLFUNCTION_HELP',
    'LANG_MENU_TYPES_HELP',
);

$append = "\n// English values for keys not yet translated to Persian.\n";
foreach ($arrayNames as $name) {
    $base = isset($english[$name]) && is_array($english[$name]) ? $english[$name] : array();
    $translated = isset($persian[$name]) && is_array($persian[$name]) ? $persian[$name] : array();
    foreach (array_diff_key($base, $translated) as $key => $value) {
        $append .= '$' . $name . '[' . var_export($key, true) . '] = ' . var_export($value, true) . ";\n";
    }
}

$configNames = array(
    'LANG_configsections', 'LANG_confignames', 'LANG_configsubgroups',
    'LANG_tab', 'LANG_fs', 'LANG_configselects',
);
foreach ($configNames as $name) {
    $base = isset($english[$name]['menu']) && is_array($english[$name]['menu']) ? $english[$name]['menu'] : array();
    $translated = isset($persian[$name]['menu']) && is_array($persian[$name]['menu']) ? $persian[$name]['menu'] : array();
    foreach (array_diff_key($base, $translated) as $key => $value) {
        $append .= '$' . $name . "['menu'][" . var_export($key, true) . '] = ' . var_export($value, true) . ";\n";
    }
}

file_put_contents(__DIR__ . '/../language/persian_utf-8.php', $append, FILE_APPEND);
