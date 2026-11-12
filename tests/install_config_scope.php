<?php
/**
 * Regression: Geeklog may require plugin functions.inc from within its
 * installer while config.php was already included in another function scope.
 * Running: php tests/install_config_scope.php
 */
error_reporting(E_ALL);
$_SERVER['PHP_SELF'] = '/admin/plugins.php';

function menu_load_defaults_from_nested_install()
{
    require_once dirname(__DIR__) . '/config.php';
}
menu_load_defaults_from_nested_install();

function menu_test_install_scope()
{
    global $_MENU_DEFAULT;
    require_once dirname(__DIR__) . '/config.php';
    if (!isset($_MENU_DEFAULT) || !is_array($_MENU_DEFAULT)) {
        require dirname(__DIR__) . '/config.php';
    }
    if (!isset($_MENU_DEFAULT['enable_cache'])) {
        fwrite(STDERR, "Install scope did not initialize Menu defaults\n");
        exit(1);
    }
}
menu_test_install_scope();

$source = file_get_contents(dirname(__DIR__) . '/functions.inc');
if ($source === false
    || strpos($source, 'global $_CONF, $_DB_table_prefix, $_TABLES, $_MENU_CONF, $_MENU_DEFAULT;') === false
    || strpos($source, "if (!isset(\$_MENU_DEFAULT) || !is_array(\$_MENU_DEFAULT))") === false
    || strpos($source, '$_MENU_CONF = array_merge($_MENU_DEFAULT, $menuStoredConfig);') === false) {
    fwrite(STDERR, "Menu installer configuration scope contract is missing\n");
    exit(1);
}
echo "Menu installer config scope: OK\n";
