from pathlib import Path

path = Path('admin_element_views.php')
s = path.read_text()
needle = "function MENU_displayTree( $menu_id ) {\n    global $_CONF, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN, $LANG_ADMIN,\n           $_MENU_CONF, $Menus, $_SCRIPTS;\n\n    $retval = '';"
replacement = "function MENU_displayTree( $menu_id ) {\n    global $_CONF, $LANG_MENU00, $LANG_MENU01, $LANG_MENU_ADMIN, $LANG_ADMIN,\n           $_MENU_CONF, $Menus, $_SCRIPTS;\n\n    $_SCRIPTS->setJavaScriptLibrary('jquery');\n\n    $retval = '';"
if needle not in s:
    raise SystemExit('MENU_displayTree insertion point not found')
s = s.replace(needle, replacement, 1)
path.write_text(s)
