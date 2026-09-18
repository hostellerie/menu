<?php

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Return one RGB channel from a CSS hex color.
 *
 * Accepts legacy Menu values such as #RRGGBB, RRGGBB, #RGB and RGB.
 * Empty, "none" or malformed values safely resolve to 0 so the admin
 * configuration page remains renderable with historical data.
 *
 * @param mixed  $color   Color value
 * @param string $channel r, g or b
 * @return int
 */
function MENU_hexrgb($color, $channel)
{
    if (!is_string($color) && !is_numeric($color)) {
        return 0;
    }

    $hex = trim((string) $color);
    if ($hex === '' || strcasecmp($hex, 'none') === 0) {
        return 0;
    }

    if ($hex[0] === '#') {
        $hex = substr($hex, 1);
    }

    if (strlen($hex) === 3 && ctype_xdigit($hex)) {
        $hex = $hex[0] . $hex[0]
             . $hex[1] . $hex[1]
             . $hex[2] . $hex[2];
    }

    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return 0;
    }

    switch (strtolower((string) $channel)) {
        case 'r':
            $offset = 0;
            break;
        case 'g':
            $offset = 2;
            break;
        case 'b':
            $offset = 4;
            break;
        default:
            return 0;
    }

    return hexdec(substr($hex, $offset, 2));
}
