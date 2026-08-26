<?php

$root = dirname(__DIR__);
$obsolete = array(
    'admin/js/tablednd.js',
    'admin/js/tablednd_0_5.js',
);

foreach ($obsolete as $relative) {
    if (file_exists($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
        fwrite(STDERR, "Obsolete admin asset still exists: {$relative}\n");
        exit(1);
    }
}

if (!is_file($root . '/admin/js/tablednd_0_6.js')) {
    fwrite(STDERR, "Required TableDnD 0.6 ordering asset is missing\n");
    exit(1);
}
if (!is_file($root . '/admin/js/menu-order-handle.js')) {
    fwrite(STDERR, "Menu ordering adapter is missing\n");
    exit(1);
}

$references = array(
    'tablednd.js',
    'tablednd_0_5.js',
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
            fwrite(STDERR, "Obsolete TableDnD asset reference remains in " . $file->getPathname() . "\n");
            exit(1);
        }
    }
}

$views = file_get_contents($root . '/admin_element_views.php');
$template = file_get_contents($root . '/templates/default/menutree.thtml');
$treeStart = strpos($views, 'function MENU_displayTree');
$treeEnd = strpos($views, 'function MENU_createElement', $treeStart);
$treeBody = substr($views, $treeStart, $treeEnd - $treeStart);

$jqueryPos = strpos($treeBody, "setJavaScriptLibrary('jquery')");
$tableDnDPos = strpos($treeBody, "setJavaScriptFile('menu_tablednd', '/admin/plugins/menu/js/tablednd_0_6.js')");
$adapterPos = strpos($treeBody, "setJavaScriptFile('menu_order_handle', '/admin/plugins/menu/js/menu-order-handle.js')");
if ($jqueryPos === false || $tableDnDPos === false || $adapterPos === false
    || !($jqueryPos < $tableDnDPos && $tableDnDPos < $adapterPos)) {
    fwrite(STDERR, "Ordering assets must be registered after jQuery and in dependency order\n");
    exit(1);
}

if (strpos($template, 'tablednd_0_6.js') !== false
    || strpos($template, 'menu-order-handle.js') !== false) {
    fwrite(STDERR, "Ordering assets must not be injected directly by the tree template\n");
    exit(1);
}

echo "Legacy admin asset cleanup tests passed\n";
