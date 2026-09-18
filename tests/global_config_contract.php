<?php

$root = dirname(__DIR__);
$config = file_get_contents($root . '/config.php');
$defaults = file_get_contents($root . '/install_defaults.php');
$functions = file_get_contents($root . '/functions.inc');

if ($config === false || $defaults === false || $functions === false) {
    fwrite(STDERR, "Unable to read Menu configuration sources\n");
    exit(1);
}

$settings = array(
    'enable_cache',
    'accessibility_markup',
    'external_link_protection',
    'allow_php_elements',
    'legacy_rendering',
    'load_legacy_css',
    'load_legacy_js',
    'debug',
);

if (strpos($functions, 'global $_CONF, $_DB_table_prefix, $_TABLES, $_MENU_CONF;') === false) {
    fwrite(STDERR, "functions.inc must keep \$_MENU_CONF in global scope\n");
    exit(1);
}

if (strpos($functions, "if (!isset(\$_MENU_CONF))") !== false) {
    fwrite(STDERR, "functions.inc must not skip persisted config hydration based on isset(\$_MENU_CONF)\n");
    exit(1);
}
if (strpos($functions, "\$_MENU_CONF = array_merge(\$_MENU_DEFAULT, \$menuStoredConfig);") === false) {
    fwrite(STDERR, "functions.inc must merge Menu defaults with persisted Geeklog configuration\n");
    exit(1);
}

foreach ($settings as $setting) {
    if (strpos($config, "'" . $setting . "'") === false) {
        fwrite(STDERR, 'Missing default for global setting: ' . $setting . "\n");
        exit(1);
    }
    if (strpos($defaults, "'" . $setting . "'") === false) {
        fwrite(STDERR, 'Missing installer registration for global setting: ' . $setting . "\n");
        exit(1);
    }
}

$sourceFiles = array();
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $path);

    if (strpos($relative, 'tests' . DIRECTORY_SEPARATOR) === 0
        || strpos($relative, '.git' . DIRECTORY_SEPARATOR) === 0
        || $relative === 'config.php'
        || $relative === 'install_defaults.php'
        || substr($relative, -3) === '.md') {
        continue;
    }

    $extension = pathinfo($path, PATHINFO_EXTENSION);
    if ($extension !== 'php' && $extension !== 'inc') {
        continue;
    }

    $contents = file_get_contents($path);
    if ($contents !== false) {
        $sourceFiles[$relative] = $contents;
    }
}

foreach ($settings as $setting) {
    $usedBy = array();
    foreach ($sourceFiles as $relative => $contents) {
        if (strpos($contents, "'" . $setting . "'") !== false
            || strpos($contents, '"' . $setting . '"') !== false) {
            $usedBy[] = $relative;
        }
    }

    if (empty($usedBy)) {
        fwrite(STDERR, 'Global setting is declared but has no runtime consumer: ' . $setting . "\n");
        exit(1);
    }
}

echo "Global configuration contract OK\n";
