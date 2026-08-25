from pathlib import Path

index_path = Path('admin/index.php')
module_path = Path('admin_menu_mutations.php')
test_path = Path('tests/admin_mutations_contract.php')

index = index_path.read_text()
module = module_path.read_text()
test = test_path.read_text()

helper = r'''
/**
 * Return legacy presentation defaults for a Menu presentation type.
 *
 * @param int $menuType
 * @return array
 */
function MENU_defaultConfigValues($menuType)
{
    switch ((int) $menuType) {
        case 1:
            return array(
                'main_menu_bg_color' => '#151515',
                'main_menu_hover_bg_color' => '#3667c0',
                'main_menu_text_color' => '#CCCCCC',
                'main_menu_hover_text_color' => '#FFFFFF',
                'submenu_text_color' => '#FFFFFF',
                'submenu_hover_text_color' => '#679EF1',
                'submenu_background_color' => '#151515',
                'submenu_hover_bg_color' => '#333333',
                'submenu_highlight_color' => '#333333',
                'submenu_shadow_color' => '#000000',
                'use_images' => '0',
                'menu_bg_filename' => '',
                'menu_hover_filename' => '',
                'menu_parent_filename' => '',
                'menu_alignment' => '1',
            );

        case 2:
            return array(
                'main_menu_text_color' => '#3677C0',
                'main_menu_hover_text_color' => '#679EF1',
                'submenu_highlight_color' => '#999999',
                'menu_alignment' => '1',
            );

        case 3:
        case 4:
            return array(
                'main_menu_bg_color' => '#DDDDDD',
                'main_menu_hover_bg_color' => '#BBBBBB',
                'main_menu_text_color' => '#0000FF',
                'main_menu_hover_text_color' => '#FFFFFF',
                'submenu_text_color' => '#0000FF',
                'submenu_hover_text_color' => '#FFFFFF',
                'submenu_highlight_color' => '#999999',
                'menu_parent_filename' => '',
                'menu_alignment' => '1',
            );
    }

    return array();
}

/**
 * Restore legacy presentation defaults for one menu.
 *
 * @param int $menuId
 */
function MENU_restoreMenuDefaults($menuId)
{
    global $_TABLES, $Menus;

    $menuId = (int) $menuId;
    if ($menuId <= 0 || !isset($Menus[$menuId])) {
        return;
    }

    $values = MENU_defaultConfigValues((int) $Menus[$menuId]['menu_type']);
    foreach ($values as $name => $value) {
        $nameSql = MENU_dbEscape($name);
        $valueSql = MENU_dbEscape($value);
        DB_save(
            $_TABLES['menu_config'],
            'menu_id,conf_name,conf_value',
            "$menuId,'$nameSql','$valueSql'"
        );
    }

    MENU_invalidateRuntimeCache(true);
}

'''
anchor = "/*\n * Saves the menu configuration\n */"
if 'function MENU_restoreMenuDefaults(' not in module:
    if anchor not in module:
        raise SystemExit('mutation insertion anchor not found')
    module = module.replace(anchor, helper + anchor, 1)

start = index.find("} else if ( isset($_POST['defaults']) ) {")
if start < 0:
    raise SystemExit('defaults branch start not found')
end = index.find("} else if ( isset($_POST['cancel'])", start)
if end < 0:
    raise SystemExit('defaults branch end not found')
replacement = """} else if ( isset($_POST['defaults']) ) {
    $menu_id = (int) Geeklog\\Input::fPost('menu_id');
    MENU_restoreMenuDefaults($menu_id);
    $content = MENU_displayMenuList();
"""
index = index[:start] + replacement + index[end:]

# Extend permanent contract.
needle = "    'MENU_saveElementOrder',\n    'MENU_saveMenuConfig',\n"
replace = "    'MENU_saveElementOrder',\n    'MENU_defaultConfigValues',\n    'MENU_restoreMenuDefaults',\n    'MENU_saveMenuConfig',\n"
if needle not in test:
    raise SystemExit('contract function list anchor not found')
test = test.replace(needle, replace, 1)

extra = r'''
if (strpos($index, 'DB_save(') !== false || strpos($index, 'DB_query(') !== false) {
    fwrite(STDERR, "admin/index.php still contains direct database writes\n");
    exit(1);
}
if (strpos($index, 'MENU_restoreMenuDefaults($menu_id)') === false) {
    fwrite(STDERR, "Defaults branch is not routed through mutation module\n");
    exit(1);
}
if (strpos($module, 'function MENU_defaultConfigValues(') === false
    || strpos($module, 'function MENU_restoreMenuDefaults(') === false) {
    fwrite(STDERR, "Centralized defaults helpers are missing\n");
    exit(1);
}

'''
marker = "if (strpos($module, 'MENU_invalidateRuntimeCache(') === false) {"
if extra not in test:
    if marker not in test:
        raise SystemExit('contract marker not found')
    test = test.replace(marker, extra + marker, 1)

index_path.write_text(index)
module_path.write_text(module)
test_path.write_text(test)
