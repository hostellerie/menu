<?php

// Database upgrade contract. Compatible with PHP 5.6+.
define('VERSION', '2.1.1');
$_SERVER['PHP_SELF'] = '/tests/database_upgrade.php';

$_TABLES = array(
    'menu_elements' => 'gl_menu_elements',
);

$menuDbIndexExists = false;
$menuDbAlterCount = 0;

function DB_query($sql)
{
    global $menuDbIndexExists, $menuDbAlterCount;

    if (strpos($sql, 'SHOW INDEX FROM gl_menu_elements') === 0) {
        return array('rows' => $menuDbIndexExists ? array(array('Key_name' => 'menu_parent_order')) : array());
    }

    if (strpos($sql, 'ALTER TABLE gl_menu_elements ADD INDEX menu_parent_order') === 0) {
        $menuDbAlterCount++;
        $menuDbIndexExists = true;
        return array('rows' => array());
    }

    return array('rows' => array());
}

function DB_numRows($result)
{
    return count($result['rows']);
}

require dirname(__DIR__) . '/install_updates.php';

function menu_db_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

menu_db_assert(menu_update_Database_1_3_0() === true, 'first database upgrade must succeed');
menu_db_assert($menuDbAlterCount === 1, 'missing composite index must be created once');
menu_db_assert(menu_update_Database_1_3_0() === true, 'second database upgrade must succeed');
menu_db_assert($menuDbAlterCount === 1, 'database upgrade must be idempotent');

$_TABLES['menu_elements'] = '';
menu_db_assert(menu_update_Database_1_3_0() === false, 'missing menu_elements table must fail safely');

echo "Database upgrade tests passed" . PHP_EOL;
