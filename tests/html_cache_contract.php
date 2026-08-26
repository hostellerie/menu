<?php

function menu_html_cache_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

$functions = file_get_contents(dirname(__DIR__) . '/functions.inc');
$start = strpos($functions, 'function MENU_getMenu(');
$end = strpos($functions, 'function phpblock_getMenu', $start);
menu_html_cache_assert($start !== false && $end !== false, 'MENU_getMenu must exist');
$block = substr($functions, $start, $end - $start);

menu_html_cache_assert(
    strpos($block, 'MENU_CACHE_check_instance') === false,
    'Context-sensitive HTML menu rendering must not read the legacy incomplete HTML cache'
);
menu_html_cache_assert(
    strpos($functions, 'MENU_CACHE_security_hash') === false,
    'Unused legacy HTML-cache security hash must remain removed'
);
menu_html_cache_assert(
    strpos($functions, "'menu_css_' . \$menu['menu_id']") !== false,
    'Theme-specific legacy CSS caching must remain available'
);

echo "Menu HTML cache safety contract passed\n";
