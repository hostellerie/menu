<?php

// +---------------------------------------------------------------------------+
// | Menu Plugin 1.3.0                                                         |
// +---------------------------------------------------------------------------+
// | admin/configuration.php                                                   |
// |                                                                           |
// | POST bridge to Geeklog's configuration manager.                           |
// +---------------------------------------------------------------------------+

require_once '../../../lib-common.php';
require_once '../../auth.inc.php';

if (!SEC_hasRights('menu.admin')) {
    COM_accessLog('User ' . $_USER['username'] . ' tried to access the Menu configuration.');
    COM_output(COM_refresh($_CONF['site_url']));
    exit;
}

$action = htmlspecialchars(
    rtrim($_CONF['site_admin_url'], '/') . '/configuration.php',
    ENT_QUOTES,
    COM_getEncodingt()
);

$display = '<form id="menu-configuration-redirect" method="post" action="' . $action . '">' . LB;
$display .= '<input type="hidden" name="conf_group" value="menu"' . XHTML . '>' . LB;
$display .= '<input type="hidden" name="subgroup" value=""' . XHTML . '>' . LB;
$display .= '<noscript><p><input type="submit" value="Configuration"' . XHTML . '></p></noscript>' . LB;
$display .= '</form>' . LB;
$display .= '<script type="text/javascript">document.getElementById("menu-configuration-redirect").submit();</script>' . LB;

COM_output(COM_createHTMLDocument($display));
