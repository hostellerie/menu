from pathlib import Path

view_path = Path('admin_element_views.php')
view = view_path.read_text()

# Create view: use the already-normalized stored menu name everywhere.
old = "        'birdseed'          => '<a href=\"'.$_CONF['site_admin_url'].'/plugins/menu/index.php\">'.$LANG_MENU01['menu_list'].'</a> :: <a href=\"'.$_CONF['site_admin_url'].'/plugins/menu/index.php?mode=menu&amp;menu='.$menu_id.'\">'.$Menus[$menu_id]['menu_name'].'</a> :: '.$LANG_MENU01['create_element'],\n        'menuname'          => isset($menu_name) ? $menu_name : '',"
new = "        'birdseed'          => '<a href=\"' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/index.php\">' . MENU_escapeHTML($LANG_MENU01['menu_list']) . '</a> :: <a href=\"' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/index.php?mode=menu&amp;menu=' . (int) $menu_id . '\">' . $safeMenuName . '</a> :: ' . MENU_escapeHTML($LANG_MENU01['create_element']),\n        'menuname'          => $safeMenuName,"
if old not in view:
    raise SystemExit('create breadcrumb/menuname anchor not found')
view = view.replace(old, new, 1)

# Plugin menu identifiers can come from plugin-provided data; escape both attribute and label contexts.
old = "        $plugin_select .= '<option value=\"' . key($plugin_menus) . '\">' . key($plugin_menus) . '</option>' . LB;"
new = "        $pluginKey = (string) key($plugin_menus);\n        $plugin_select .= '<option value=\"' . MENU_escapeHTML($pluginKey) . '\">' . MENU_escapeHTML($pluginKey) . '</option>' . LB;"
if old not in view:
    raise SystemExit('create plugin option anchor not found')
view = view.replace(old, new, 1)

# Edit view: escape values that land inside quoted HTML value attributes.
replacements = {
    "        'menulabel'         => $Menus[$menu_id]['elements'][$mid]->label,": "        'menulabel'         => MENU_escapeHTML($Menus[$menu_id]['elements'][$mid]->label),",
    "        'menuurl'           => $Menus[$menu_id]['elements'][$mid]->url,": "        'menuurl'           => MENU_escapeHTML($Menus[$menu_id]['elements'][$mid]->url),",
    "        'phpfunction'       => $Menus[$menu_id]['elements'][$mid]->subtype,": "        'phpfunction'       => MENU_escapeHTML($Menus[$menu_id]['elements'][$mid]->subtype),",
}
for old, new in replacements.items():
    if old not in view:
        raise SystemExit('edit attribute anchor not found: ' + old)
    view = view.replace(old, new, 1)

# Edit plugin option values/labels.
old = "        $plugin_select .= '<option value=\"' . key($plugin_menus) . '\"';"
new = "        $pluginKey = (string) key($plugin_menus);\n        $plugin_select .= '<option value=\"' . MENU_escapeHTML($pluginKey) . '\"';"
if old not in view:
    raise SystemExit('edit plugin option value anchor not found')
view = view.replace(old, new, 1)
old = "        $plugin_select .= '>' . key($plugin_menus) . '</option>' . LB;"
new = "        $plugin_select .= '>' . MENU_escapeHTML($pluginKey) . '</option>' . LB;"
if old not in view:
    raise SystemExit('edit plugin option label anchor not found')
view = view.replace(old, new, 1)

view_path.write_text(view)

# Extend the existing permanent escaping contract.
test_path = Path('tests/admin_view_escaping_contract.php')
test = test_path.read_text()
insert = r'''if (strpos($view, "'menuname'          => isset($menu_name)") !== false) {
    fwrite(STDERR, "Undefined legacy menu_name fallback remains in create view\n");
    exit(1);
}
$escapedAttributeNeedles = array(
    "'menulabel'         => MENU_escapeHTML(",
    "'menuurl'           => MENU_escapeHTML(",
    "'phpfunction'       => MENU_escapeHTML("
);
foreach ($escapedAttributeNeedles as $needle) {
    if (strpos($view, $needle) === false) {
        fwrite(STDERR, "Stored element attribute value is not escaped: {$needle}\n");
        exit(1);
    }
}
if (substr_count($view, 'MENU_escapeHTML($pluginKey)') < 3) {
    fwrite(STDERR, "Plugin-provided option identifiers are not escaped\n");
    exit(1);
}
'''
marker = 'echo "Admin view escaping contract tests passed\\n";'
if marker not in test:
    raise SystemExit('test marker not found')
test = test.replace(marker, insert + '\n' + marker, 1)
test_path.write_text(test)
