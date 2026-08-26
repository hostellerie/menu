<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | cache_runtime.php                                                         |
// |                                                                           |
// | Runtime cache invalidation helpers.                                       |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Invalidate every disposable cache owned by Menu after a data mutation.
 *
 * Menu output cache entries and generated legacy CSS entries share the same
 * private cache directory. Clearing that directory is simpler and less error
 * prone than trying to derive every security/language/theme-specific key.
 * Persistent custom CSS lives in the separate css/ directory and is untouched.
 *
 * @param bool $refreshMenu Rebuild the in-request Menu structure when possible
 * @return void
 */
function MENU_invalidateRuntimeCache($refreshMenu = true)
{
    global $_TABLES;

    if (function_exists('MENU_CACHE_cleanup_plugin')) {
        MENU_CACHE_cleanup_plugin('');
    }

    if (isset($_TABLES['vars'])) {
        DB_save($_TABLES['vars'], 'name,value', "'cacheid'," . rand());
    }

    if ($refreshMenu && function_exists('MENU_initMENU')) {
        MENU_initMENU(true);
    }
}
