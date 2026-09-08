<?php

/**
 * Language contract for the Menu plugin.
 *
 * english.php is the canonical/default Geeklog language file. Every literal
 * language key referenced by PHP code must exist in that file. This test is
 * intentionally strict: missing keys must be fixed in english.php, not hidden
 * behind fallbacks in application code.
 */

$root = dirname(__DIR__);

// english.php appends to these Geeklog configuration arrays.
$LANG32 = array();
$LANG_configsections = array();
$LANG_confignames = array();
$LANG_configsubgroups = array();
$LANG_tab = array();
$LANG_fs = array();
$LANG_configselects = array();

require $root . '/language/english.php';

$languageArrays = array(
    'LANG_MENU_1' => isset($LANG_MENU_1) ? $LANG_MENU_1 : array(),
    'LANG_MENU00' => isset($LANG_MENU00) ? $LANG_MENU00 : array(),
    'LANG_MENU01' => isset($LANG_MENU01) ? $LANG_MENU01 : array(),
    'LANG_HC' => isset($LANG_HC) ? $LANG_HC : array(),
    'LANG_HS' => isset($LANG_HS) ? $LANG_HS : array(),
    'LANG_VC' => isset($LANG_VC) ? $LANG_VC : array(),
    'LANG_VS' => isset($LANG_VS) ? $LANG_VS : array(),
    'LANG_MENU_MENU_TYPES' => isset($LANG_MENU_MENU_TYPES) ? $LANG_MENU_MENU_TYPES : array(),
    'LANG_MENU_TYPES' => isset($LANG_MENU_TYPES) ? $LANG_MENU_TYPES : array(),
    'LANG_MENU_TARGET' => isset($LANG_MENU_TARGET) ? $LANG_MENU_TARGET : array(),
    'LANG_MENU_GLFUNCTION' => isset($LANG_MENU_GLFUNCTION) ? $LANG_MENU_GLFUNCTION : array(),
    'LANG_MENU_GLTYPES' => isset($LANG_MENU_GLTYPES) ? $LANG_MENU_GLTYPES : array(),
    'LANG_MENU_ADMIN' => isset($LANG_MENU_ADMIN) ? $LANG_MENU_ADMIN : array(),
    'LANG_MENU_GLTYPES_HELP' => isset($LANG_MENU_GLTYPES_HELP) ? $LANG_MENU_GLTYPES_HELP : array(),
    'LANG_MENU_GLFUNCTION_HELP' => isset($LANG_MENU_GLFUNCTION_HELP) ? $LANG_MENU_GLFUNCTION_HELP : array(),
    'LANG_MENU_TYPES_HELP' => isset($LANG_MENU_TYPES_HELP) ? $LANG_MENU_TYPES_HELP : array(),
);

$missing = array();
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $path);

    if (strpos($relative, 'language' . DIRECTORY_SEPARATOR) === 0
        || strpos($relative, 'tests' . DIRECTORY_SEPARATOR) === 0
        || strpos($relative, '.git' . DIRECTORY_SEPARATOR) === 0
        || strpos($relative, 'dist' . DIRECTORY_SEPARATOR) === 0) {
        continue;
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($extension !== 'php' && $extension !== 'inc') {
        continue;
    }

    $source = file_get_contents($path);
    if ($source === false) {
        fwrite(STDERR, "Unable to read {$relative}\n");
        exit(1);
    }

    foreach ($languageArrays as $arrayName => $defined) {
        $pattern = '/\\$' . preg_quote($arrayName, '/')
            . '\\s*\\[\\s*([\'\"])([^\'\"]+)\\1\\s*\\]/';

        if (preg_match_all($pattern, $source, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = $match[2];
                if (!array_key_exists($key, $defined)) {
                    $missing[] = $relative . ': $' . $arrayName . '[' . var_export($key, true) . ']';
                }
            }
        }

        $numericPattern = '/\\$' . preg_quote($arrayName, '/')
            . '\\s*\\[\\s*([0-9]+)\\s*\\]/';

        if (preg_match_all($numericPattern, $source, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = (int) $match[1];
                if (!array_key_exists($key, $defined)) {
                    $missing[] = $relative . ': $' . $arrayName . '[' . $key . ']';
                }
            }
        }
    }
}

if ($missing) {
    fwrite(STDERR, "Missing keys in language/english.php:\n - " . implode("\n - ", array_unique($missing)) . "\n");
    exit(1);
}

echo "English language contract: OK\n";
