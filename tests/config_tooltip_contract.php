<?php

$root = dirname(__DIR__);
$runtime = file_get_contents($root . '/runtime_config.php');

$required = array(
    'function plugin_getconfigtooltip_menu($id)',
    "'enable_cache' =>",
    "'accessibility_markup' =>",
    "'external_link_protection' =>",
    "'allow_php_elements' =>",
    "'legacy_rendering' =>",
    "'load_legacy_css' =>",
    "'load_legacy_js' =>",
    "'debug' =>",
    "french_france_utf-8",
    "return isset($tooltips[$id]) ? $tooltips[$id] : '';",
);

foreach ($required as $needle) {
    if (strpos($runtime, $needle) === false) {
        fwrite(STDERR, "Configuration tooltip contract missing: {$needle}\n");
        exit(1);
    }
}

if (strpos($runtime, "'samplesetting1' =>") !== false
    || strpos($runtime, "'samplesetting2' =>") !== false) {
    fwrite(STDERR, "Obsolete sample settings must not receive tooltips\n");
    exit(1);
}

echo "Configuration tooltip contract tests passed\n";
