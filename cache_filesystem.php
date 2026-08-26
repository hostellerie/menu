<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | cache_filesystem.php                                                      |
// |                                                                           |
// | Filesystem-safe cache cleanup helpers.                                    |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Remove matching files below one cache root without following symlinks.
 *
 * @param string      $path   Directory currently being scanned
 * @param string      $needle Optional filename fragment
 * @param int         $since  Only delete files whose ctime is <= this value
 * @param string|null $root   Canonical cache root used for recursion checks
 * @return bool
 */
function MENU_cache_clean_directories($path, $needle = '', $since = 0, $root = null)
{
    $path = rtrim((string) $path, "/\\");
    if ($path === '') {
        return false;
    }

    if ($root === null) {
        $root = realpath($path);
        if ($root === false || !is_dir($root)) {
            return false;
        }
    } else {
        $root = rtrim((string) $root, "/\\");
    }

    $current = realpath($path);
    if ($current === false || !is_dir($current)) {
        return false;
    }

    if ($current !== $root
        && strpos($current, $root . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }

    $handle = @opendir($current);
    if ($handle === false) {
        return false;
    }

    $retval = true;
    while (false !== ($entry = readdir($handle))) {
        if ($entry === '.' || $entry === '..' || $entry === '.svn') {
            continue;
        }

        $entryPath = $current . DIRECTORY_SEPARATOR . $entry;

        // Never follow a symlink. Leaving it in place is safer than resolving
        // a target that may live outside the plugin-owned cache directory.
        if (is_link($entryPath)) {
            $retval = false;
            continue;
        }

        if (is_dir($entryPath)) {
            $retval = MENU_cache_clean_directories($entryPath, $needle, $since, $root) && $retval;
            $retval = @rmdir($entryPath) && $retval;
            continue;
        }

        if (!is_file($entryPath)) {
            $retval = false;
            continue;
        }

        if ($needle !== '' && strpos($entry, $needle) === false) {
            $retval = false;
            continue;
        }

        if ($since && @filectime($entryPath) > $since) {
            $retval = false;
            continue;
        }

        $retval = @unlink($entryPath) && $retval;
    }

    @closedir($handle);
    return $retval;
}
