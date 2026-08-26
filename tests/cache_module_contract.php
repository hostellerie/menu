<?php

$root = dirname(__DIR__);
$functions = file_get_contents($root . '/functions.inc');
$cache = file_get_contents($root . '/cache.php');

$required = array(
    'function MENU_CACHE_cleanup_plugin(',
    'function MENU_CACHE_remove_instance(',
    'function MENU_CACHE_create_instance(',
    'function MENU_CACHE_check_instance(',
    'function MENU_CACHE_get_instance_update(',
    'function MENU_CACHE_instance_filename(',
    'function MENU_compress(',
);

foreach ($required as $needle) {
    if (strpos($cache, $needle) === false) {
        fwrite(STDERR, "Missing cache helper: {$needle}\n");
        exit(1);
    }
    if (strpos($functions, $needle) !== false) {
        fwrite(STDERR, "Cache helper still duplicated in functions.inc: {$needle}\n");
        exit(1);
    }
}

if (strpos($functions, "require_once \$plugin_path . 'cache.php';") === false) {
    fwrite(STDERR, "functions.inc does not load cache.php\n");
    exit(1);
}

if (strpos($cache, 'LOCK_EX') === false) {
    fwrite(STDERR, "Cache writes are not serialized\n");
    exit(1);
}

if (strpos($cache, 'is_link($filename)') === false) {
    fwrite(STDERR, "Cache reads do not reject symbolic links\n");
    exit(1);
}

echo "Cache module contract tests passed\n";
