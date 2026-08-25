<?php

$root = dirname(__DIR__);
$obsolete = array(
    'admin/js/tablednd.js',
    'admin/js/tablednd_0_5.js',
    'admin/js/tablednd_0_6.js',
);

foreach ($obsolete as $relative) {
    if (file_exists($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
        fwrite(STDERR, "Obsolete admin asset still exists: {$relative}\n");
        exit(1);
    }
}

if (!is_file($root . '/admin/js/menu-order-handle.js')) {
    fwrite(STDERR, "Native Menu ordering script is missing\n");
    exit(1);
}

$references = array(
    'tablednd.js',
    'tablednd_0_5.js',
    'tablednd_0_6.js',
);
$extensions = array('php', 'inc', 'thtml', 'js', 'css');
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
    if (!in_array($extension, $extensions, true)) {
        continue;
    }
    if (strpos($file->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) !== false) {
        continue;
    }

    $content = strtolower(file_get_contents($file->getPathname()));
    foreach ($references as $reference) {
        if (strpos($content, $reference) !== false) {
            fwrite(STDERR, "Legacy tableDnD asset reference remains in " . $file->getPathname() . "\n");
            exit(1);
        }
    }
}

echo "Legacy admin asset cleanup tests passed\n";
