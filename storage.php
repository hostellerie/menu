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

require_once __DIR__ . '/compat.php';
require_once __DIR__ . '/resolved_tree.php';

/**
 * Return the preferred private, site-specific Menu data directory.
 *
 * Examples:
 *   /path/data/          -> /path/data-menu/
 *   /path/data/ecologie/ -> /path/data/ecologie-menu/
 *
 * @return string
 */
function MENU_preferredDataDir()
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
 * Return the active Menu data directory.
 *
 * The preferred location is the sibling {path_data}-menu directory. Some
 * older/single-site installations allow PHP to write inside path_data but not
 * in its parent directory. In that case we temporarily fall back to the legacy
 * path_data/menu_data directory rather than aborting plugin installation.
 *
 * @return string
 */
function MENU_dataDir()
{
    $preferred = MENU_preferredDataDir();
    if ($preferred === '') {
        return '';
    }

    if (is_dir($preferred)) {
        return $preferred;
    }

    $parent = dirname(rtrim($preferred, "/\\"));
    if (is_dir($parent) && is_writable($parent)) {
        return $preferred;
    }

    return MENU_legacyDataDir();
}

/**
 * Tell whether the preferred multisite-safe storage is currently active.
 *
 * @return bool
 */
function MENU_usesPreferredDataDir()
{
    $preferred = MENU_preferredDataDir();
    $active = MENU_dataDir();

    if ($preferred === '' || $active === '') {
        return false;
    }

    return rtrim($preferred, "/\\") === rtrim($active, "/\\");
}

/**
 * Return the filesystem directory for public Menu images.
 *
 * @return string
 */
function MENU_imageDir()
{
    global $_CONF;

    $base = isset($_CONF['path_images']) ? rtrim($_CONF['path_images'], "/\\") : '';
    if ($base === '') {
        return '';
    }

    return $base . DIRECTORY_SEPARATOR . 'menu' . DIRECTORY_SEPARATOR;
}

/**
 * Return the public URL for Menu images.
 *
 * path_images is normally inside path_html. Deriving the URL from the relative
 * filesystem path preserves multisite layouts such as images/ecologie/menu/.
 * A conservative /images/menu/ fallback is kept for unusual legacy layouts.
 *
 * @return string
 */
function MENU_imageUrl()
{
    global $_CONF;

    $siteUrl = isset($_CONF['site_url']) ? rtrim($_CONF['site_url'], '/') : '';
    $pathImages = isset($_CONF['path_images']) ? rtrim($_CONF['path_images'], "/\\") : '';
    $pathHtml = isset($_CONF['path_html']) ? rtrim($_CONF['path_html'], "/\\") : '';

    if ($siteUrl === '') {
        return '';
    }

    if ($pathImages !== '' && $pathHtml !== '') {
        $imagesNormalized = str_replace('\\', '/', $pathImages);
        $htmlNormalized = rtrim(str_replace('\\', '/', $pathHtml), '/');

        if (strpos($imagesNormalized, $htmlNormalized . '/') === 0) {
            $relative = ltrim(substr($imagesNormalized, strlen($htmlNormalized)), '/');
            return $siteUrl . '/' . $relative . '/menu/';
        }
    }

    return $siteUrl . '/images/menu/';
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
 * Ensure the active Menu storage layout exists.
 *
 * cache/ is disposable. css/ is persistent and must survive Geeklog cache
 * cleanup operations when preferred storage is available.
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
 * Ensure the public Menu image directory exists.
 *
 * @return bool
 */
function MENU_ensureImageDir()
{
    return MENU_ensureDirectory(MENU_imageDir());
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

        if (is_link($src)) {
            continue;
        }

        if (is_dir($src)) {
            if (!MENU_copyTreeNonDestructive($src, $dst)) {
                $ok = false;
            }
        } elseif (is_file($src)) {
            if (!file_exists($dst) && !@copy($src, $dst)) {
                $ok = false;
            }
        }
    }

    closedir($handle);

    return $ok;
}

/**
 * Migrate legacy path_data/menu_data/ content to the preferred
 * {path_data}-menu/ location when that location is writable.
 *
 * The migration is intentionally non-destructive and idempotent:
 * - legacy files are never deleted;
 * - existing destination files are never overwritten;
 * - it can safely be executed more than once.
 *
 * If the parent of {path_data}-menu is not writable, the plugin keeps using
 * legacy storage and returns success. This avoids breaking installation on
 * older Geeklog layouts while preserving the preferred migration whenever the
 * filesystem permits it.
 *
 * @return bool
 */
function MENU_migrateLegacyData()
{
    $source = MENU_legacyDataDir();
    $preferred = MENU_preferredDataDir();

    if ($preferred === '') {
        return false;
    }

    if (!MENU_usesPreferredDataDir()) {
        return MENU_ensureDataDirs();
    }

    if (!MENU_ensureDirectory($preferred)
        || !MENU_ensureDirectory($preferred . 'cache' . DIRECTORY_SEPARATOR)
        || !MENU_ensureDirectory($preferred . 'css' . DIRECTORY_SEPARATOR)) {
        return false;
    }

    if ($source === '' || !is_dir($source)) {
        return true;
    }

    if (rtrim($source, "/\\") === rtrim($preferred, "/\\")) {
        return true;
    }

    return MENU_copyTreeNonDestructive($source, $preferred);
}
