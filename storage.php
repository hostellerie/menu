<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | storage.php                                                               |
// |                                                                           |
// | Site-specific storage helpers and legacy data migration.                  |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Return the private, site-specific Menu data directory.
 *
 * Examples:
 *   /path/data/          -> /path/data-menu/
 *   /path/data/ecologie/ -> /path/data/ecologie-menu/
 *
 * @return string
 */
function MENU_dataDir()
{
    global $_CONF;

    $base = isset($_CONF['path_data']) ? rtrim($_CONF['path_data'], "/\\") : '';
    if ($base === '') {
        return '';
    }

    return dirname($base) . DIRECTORY_SEPARATOR
        . basename($base) . '-menu' . DIRECTORY_SEPARATOR;
}

/**
 * Return the legacy Menu data directory used up to 1.2.8.1.
 *
 * @return string
 */
function MENU_legacyDataDir()
{
    global $_CONF;

    $base = isset($_CONF['path_data']) ? rtrim($_CONF['path_data'], "/\\") : '';
    if ($base === '') {
        return '';
    }

    return $base . DIRECTORY_SEPARATOR . 'menu_data' . DIRECTORY_SEPARATOR;
}

/**
 * Ensure a directory exists.
 *
 * @param string $path
 * @return bool
 */
function MENU_ensureDirectory($path)
{
    if ($path === '') {
        return false;
    }

    if (is_dir($path)) {
        return true;
    }

    return @mkdir($path, 0755, true) || is_dir($path);
}

/**
 * Ensure the site-specific Menu storage layout exists.
 *
 * cache/ is disposable. css/ is persistent and must survive Geeklog cache
 * cleanup operations.
 *
 * @return bool
 */
function MENU_ensureDataDirs()
{
    $base = MENU_dataDir();
    if ($base === '') {
        return false;
    }

    return MENU_ensureDirectory($base)
        && MENU_ensureDirectory($base . 'cache' . DIRECTORY_SEPARATOR)
        && MENU_ensureDirectory($base . 'css' . DIRECTORY_SEPARATOR);
}

/**
 * Copy a directory tree without overwriting an existing destination file.
 * Source files and directories are never deleted.
 *
 * @param string $source
 * @param string $destination
 * @return bool
 */
function MENU_copyTreeNonDestructive($source, $destination)
{
    if (!is_dir($source)) {
        return true;
    }

    if (!MENU_ensureDirectory($destination)) {
        return false;
    }

    $handle = @opendir($source);
    if ($handle === false) {
        return false;
    }

    $ok = true;

    while (false !== ($entry = readdir($handle))) {
        if ($entry === '.' || $entry === '..' || $entry === '.svn') {
            continue;
        }

        $src = rtrim($source, "/\\") . DIRECTORY_SEPARATOR . $entry;
        $dst = rtrim($destination, "/\\") . DIRECTORY_SEPARATOR . $entry;

        // Do not follow symlinks during migration.
        if (is_link($src)) {
            continue;
        }

        if (is_dir($src)) {
            if (!MENU_copyTreeNonDestructive($src, $dst)) {
                $ok = false;
            }
        } elseif (is_file($src)) {
            // Existing destination files always win.
            if (!file_exists($dst) && !@copy($src, $dst)) {
                $ok = false;
            }
        }
    }

    closedir($handle);

    return $ok;
}

/**
 * Migrate legacy path_data/menu_data/ content to the new site-specific
 * {path_data}-menu/ location.
 *
 * The migration is intentionally non-destructive and idempotent:
 * - legacy files are never deleted;
 * - existing destination files are never overwritten;
 * - it can safely be executed more than once.
 *
 * @return bool
 */
function MENU_migrateLegacyData()
{
    $source = MENU_legacyDataDir();
    $destination = MENU_dataDir();

    if ($destination === '' || !MENU_ensureDataDirs()) {
        return false;
    }

    if ($source === '' || !is_dir($source)) {
        return true;
    }

    return MENU_copyTreeNonDestructive($source, $destination);
}
