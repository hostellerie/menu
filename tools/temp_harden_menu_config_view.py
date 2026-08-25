from pathlib import Path

p = Path('admin_element_views.php')
s = p.read_text()

old = "    $retval = '';\n    $menu_id = $mid;"
new = "    $retval = '';\n    $menu_id = $mid;\n    $safeMenuName = MENU_escapeStoredText($Menus[$menu_id]['menu_name']);"
if old not in s:
    raise SystemExit('menu config safe-name anchor not found')
s = s.replace(old, new, 1)

old = "$retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['menu_colors'] .' for ' . $Menus[$menu_id]['menu_name'],'', COM_getBlockTemplate('_admin_block', 'header'));"
new = "$retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['menu_colors'] .' for ' . $safeMenuName,'', COM_getBlockTemplate('_admin_block', 'header'));"
if old not in s:
    raise SystemExit('menu config title anchor not found')
s = s.replace(old, new, 1)

old = "        'birdseed'          => '<a href=\"'.$_CONF['site_admin_url'].'/plugins/menu/index.php\">Menu List</a> :: '.$Menus[$mid]['menu_name'].' :: Configuration',\n        'menu_id'           => $mid,\n        'menu_name'         => MENU_escapeHTML($Menus[$mid]['menu_name']),"
new = "        'birdseed'          => '<a href=\"' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/index.php\">Menu List</a> :: ' . $safeMenuName . ' :: Configuration',\n        'menu_id'           => (int) $mid,\n        'menu_name'         => $safeMenuName,"
if old not in s:
    raise SystemExit('menu config breadcrumb anchor not found')
s = s.replace(old, new, 1)
p.write_text(s)

tp = Path('tests/admin_view_escaping_contract.php')
t = tp.read_text()
t = t.replace("if (substr_count($view, '$safeMenuName = MENU_escapeStoredText(') < 3) {", "if (substr_count($view, '$safeMenuName = MENU_escapeStoredText(') < 4) {")
needle = "if (strpos($view, \"'menuname'          => \\$Menus[\\$menu_id]['menu_name']\") !== false) {"
if needle not in t:
    raise SystemExit('test insertion anchor not found')
insert = "if (strpos($view, \"'birdseed'          => '<a href=\\\"'.\\$_CONF['site_admin_url'].'/plugins/menu/index.php\\\">Menu List</a> :: '.\\$Menus[\\$mid]['menu_name']\") !== false) {\n    fwrite(STDERR, \"Raw stored menu name remains in configuration breadcrumb\\n\");\n    exit(1);\n}\n"
t = t.replace(needle, insert + needle, 1)
tp.write_text(t)
