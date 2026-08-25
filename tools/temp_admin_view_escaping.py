from pathlib import Path

p = Path('admin_element_views.php')
s = p.read_text()

# MENU_displayTree: normalize one safe stored menu name and reuse it in HTML contexts.
anchor = "    $retval = '';\n\n\n    $menu_arr = array("
replacement = "    $retval = '';\n    $safeMenuName = MENU_escapeStoredText($Menus[$menu_id]['menu_name']);\n\n    $menu_arr = array("
if anchor not in s:
    raise SystemExit('displayTree safe-name anchor not found')
s = s.replace(anchor, replacement, 1)

s = s.replace("$retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$Menus[$menu_id]['menu_name'],'', COM_getBlockTemplate('_admin_block', 'header'));",
              "$retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$safeMenuName,'', COM_getBlockTemplate('_admin_block', 'header'));", 1)
s = s.replace("'birdseed'          => '<a href=\"'.$_CONF['site_admin_url'].'/plugins/menu/index.php\">'.$LANG_MENU01['menu_list'].'</a> :: '.$Menus[$menu_id]['menu_name'].' :: '.$LANG_MENU01['elements'],",
              "'birdseed'          => '<a href=\"' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/index.php\">' . MENU_escapeHTML($LANG_MENU01['menu_list']) . '</a> :: ' . $safeMenuName . ' :: ' . MENU_escapeHTML($LANG_MENU01['elements']),", 1)
s = s.replace("'menuname'          => $Menus[$menu_id]['menu_name'],",
              "'menuname'          => $safeMenuName,", 1)

# Create element: safe name for menu labels/title.
create_start = s.find('function MENU_createElement')
edit_start = s.find('function MENU_editElement', create_start)
if create_start < 0 or edit_start < 0:
    raise SystemExit('create/edit boundaries missing')
create = s[create_start:edit_start]
create = create.replace("    $retval = '';\n", "    $retval = '';\n    $safeMenuName = MENU_escapeStoredText($Menus[$menu_id]['menu_name']);\n", 1)
create = create.replace("'text' => 'Back to ' . $Menus[$menu_id]['menu_name']),", "'text' => 'Back to ' . $safeMenuName),", 1)
create = create.replace("$retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['create_element'] .' >> ' . $Menus[$menu_id]['menu_name'],'', COM_getBlockTemplate('_admin_block', 'header'));",
                        "$retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['create_element'] .' >> ' . $safeMenuName,'', COM_getBlockTemplate('_admin_block', 'header'));", 1)
s = s[:create_start] + create + s[edit_start:]

# Edit element: same normalization + remove duplicate self check.
edit_start = s.find('function MENU_editElement')
config_start = s.find('function MENU_menuConfig', edit_start)
edit = s[edit_start:config_start]
edit = edit.replace("    $retval = '';\n", "    $retval = '';\n    $safeMenuName = MENU_escapeStoredText($Menus[$menu_id]['menu_name']);\n", 1)
edit = edit.replace("'text' => 'Back to ' . $Menus[$menu_id]['menu_name']),", "'text' => 'Back to ' . $safeMenuName),", 1)
edit = edit.replace("$retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['edit_element'] .' for ' . $Menus[$menu_id]['menu_name'],'', COM_getBlockTemplate('_admin_block', 'header'));",
                    "$retval  .= COM_startBlock($LANG_MENU01['menu_builder'].' :: '.$LANG_MENU01['edit_element'] .' for ' . $safeMenuName,'', COM_getBlockTemplate('_admin_block', 'header'));", 1)
edit = edit.replace("        if ((int) $row['id'] === (int) $mid) {\n            continue;\n        }\n", "", 1)
edit = edit.replace("'birdseed'          => '<a href=\"'.$_CONF['site_admin_url'].'/plugins/menu/index.php\">Menu List</a> :: <a href=\"'.$_CONF['site_admin_url'].'/plugins/menu/index.php?mode=menu&amp;menu='.$menu_id.'\">'.$Menus[$menu_id]['menu_name'].'</a> :: Edit Element',",
                    "'birdseed'          => '<a href=\"' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/index.php\">Menu List</a> :: <a href=\"' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/index.php?mode=menu&amp;menu=' . (int) $menu_id . '\">' . $safeMenuName . '</a> :: Edit Element',", 1)
s = s[:edit_start] + edit + s[config_start:]

p.write_text(s)

Path('tests/admin_view_escaping_contract.php').write_text(r'''<?php
$view = file_get_contents(dirname(__DIR__) . '/admin_element_views.php');

if (substr_count($view, 'MENU_escapeStoredText($Menus[$menu_id][\'menu_name\'])') < 3) {
    fwrite(STDERR, "Stored menu names are not normalized in all admin element views\n");
    exit(1);
}
if (strpos($view, "if ((int) $row['id'] === (int) $mid) {\n            continue;\n        }\n        if ((int) $row['id'] === (int) $mid)") !== false) {
    fwrite(STDERR, "Duplicate self-parent filter remains\n");
    exit(1);
}
if (strpos($view, ".$Menus[$menu_id]['menu_name'].' :: '.$LANG_MENU01['elements']") !== false) {
    fwrite(STDERR, "Raw stored menu name remains in tree breadcrumb\n");
    exit(1);
}

echo "Admin view escaping contract tests passed\n";
''')
