<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin                                                               |
// +---------------------------------------------------------------------------+
// | css_security.php                                                          |
// |                                                                           |
// | Validation helpers for legacy Menu CSS values.                            |
// +---------------------------------------------------------------------------+

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Return a safe six-digit hexadecimal CSS color.
 *
 * @param mixed  $value
 * @param string $fallback
 * @return string
 */
function MENU_cssColor($value, $fallback = '#000000')
{
    $value = trim((string) $value);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
        return strtoupper($value);
    }

    return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $fallback)
        ? strtoupper((string) $fallback)
        : '#000000';
}

/**
 * Return a safe configured legacy image basename only when the file exists.
 *
 * @param mixed $filename
 * @return string
 */
function MENU_cssImageFilename($filename)
{
    $filename = basename(trim((string) $filename));
    if ($filename === '' || !preg_match('/^[A-Za-z0-9._-]+\.(?:png|gif|jpe?g)$/i', $filename)) {
        return '';
    }

    $directory = MENU_imageDir();
    if ($directory === '') {
        return '';
    }

    $path = $directory . $filename;
    if (!is_file($path) || is_link($path)) {
        return '';
    }

    return $filename;
}

/**
 * Return a safe CSS background image fragment for one legacy image.
 *
 * @param mixed  $filename
 * @param string $repeat
 * @return string
 */
function MENU_cssImageBackground($filename, $repeat = '')
{
    $filename = MENU_cssImageFilename($filename);
    $baseUrl = MENU_imageUrl();
    if ($filename === '' || $baseUrl === '') {
        return '';
    }

    $suffix = $repeat === 'repeat-x' ? ' repeat-x' : '';

    return 'url("' . $baseUrl . rawurlencode($filename) . '")' . $suffix;
}
