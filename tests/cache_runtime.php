<?php

define('VERSION', '2.1.1');

$_TABLES = array('vars' => 'gl_vars');
$calls = array();

function MENU_CACHE_cleanup_plugin($plugin)
{
    global $calls;
    $calls[] = array('cleanup', $plugin);
}

function DB_save($table, $fields, $values)
{
    global $calls;
    $calls[] = array('db_save', $table, $fields, $values);
}

function MENU_initMENU($force = false)
{
    global $calls;
    $calls[] = array('init', $force);
}

require_once dirname(__DIR__) . '/cache_runtime.php';

MENU_invalidateRuntimeCache(true);

if (count($calls) !== 3
    || $calls[0] !== array('cleanup', '')
    || $calls[1][0] !== 'db_save'
    || $calls[1][1] !== 'gl_vars'
    || $calls[2] !== array('init', true)) {
    fwrite(STDERR, "Full Menu cache invalidation sequence is incorrect.\n");
    exit(1);
}

$calls = array();
MENU_invalidateRuntimeCache(false);
if (count($calls) !== 2 || $calls[0] !== array('cleanup', '') || $calls[1][0] !== 'db_save') {
    fwrite(STDERR, "Cache invalidation without in-request rebuild is incorrect.\n");
    exit(1);
}

echo "Menu runtime cache invalidation tests passed\n";
