<?php

$root = dirname(__DIR__);
$runtime = file_get_contents($root . '/runtime_config.php');
$documentation = file_get_contents($root . '/public_html/config.html');

$runtimeRequired = array(
    'function plugin_getdocumentationurl_menu($file)',
    "'/menu/config.html'",
    'function plugin_getconfigtooltip_menu($id)',
    'return null;',
);

foreach ($runtimeRequired as $needle) {
    if (strpos($runtime, $needle) === false) {
        fwrite(STDERR, "Configuration tooltip contract missing: {$needle}\n");
        exit(1);
    }
}

$options = array(
    'enable_cache',
    'accessibility_markup',
    'external_link_protection',
    'allow_php_elements',
    'legacy_rendering',
    'load_legacy_css',
    'load_legacy_js',
    'debug',
);

foreach ($options as $option) {
    if (strpos($documentation, 'name="desc_' . $option . '"') === false) {
        fwrite(STDERR, "Configuration documentation missing anchor: desc_{$option}\n");
        exit(1);
    }
}

if (strpos($documentation, 'samplesetting1') !== false
    || strpos($documentation, 'samplesetting2') !== false) {
    fwrite(STDERR, "Obsolete sample settings must not appear in configuration documentation\n");
    exit(1);
}

echo "Configuration tooltip contract tests passed\n";
