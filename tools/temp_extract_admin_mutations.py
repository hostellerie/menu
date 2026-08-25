from pathlib import Path

index_path = Path('admin/index.php')
module_path = Path('admin_menu_mutations.php')
test_path = Path('tests/admin_mutations_contract.php')

index = index_path.read_text()
module = module_path.read_text()
test = test_path.read_text()

# 1. Move MENU_moveElement from index to mutation module.
start = index.find('/*\n * Moves a menu element up or down\n */\nfunction MENU_moveElement')
if start < 0:
    raise SystemExit('MENU_moveElement block not found')
end = index.find('\n\n\n', index.find('\n}', start) + 2)
if end < 0:
    raise SystemExit('MENU_moveElement end not found')
move_block = index[start:end].rstrip() + '\n\n'
index = index[:start] + index[end:].lstrip('\n')

insert_anchor = '/*\n * Saves a new menu element\n */'
if insert_anchor not in module:
    raise SystemExit('module insertion anchor not found')
module = module.replace(insert_anchor, move_block + insert_anchor, 1)

# 2. Add wrapper mutations after recursive delete helper.
delete_anchor = "    DB_query($sql);\n}\n\n/*\n * Saves the menu configuration\n */"
if delete_anchor not in module:
    raise SystemExit('delete helper anchor not found')
wrappers = r'''    DB_query($sql);
}

/**
 * Delete one element and all of its descendants, then normalize the root
 * branch order and invalidate runtime caches once.
 *
 * @param int $id
 * @param int $menuId
 */
function MENU_deleteElementTree($id, $menuId)
{
    global $Menus;

    $id = (int) $id;
    $menuId = (int) $menuId;
    if ($id <= 0 || $menuId <= 0) {
        return;
    }

    MENU_deleteChildElements($id, $menuId);
    if (isset($Menus[$menuId]['elements'][0])) {
        $Menus[$menuId]['elements'][0]->reorderMenu();
    }
    MENU_invalidateRuntimeCache(true);
}

/**
 * Toggle whether legacy rendering/configuration is enabled for one menu.
 *
 * @param int $menuId
 * @param int $active
 */
function MENU_setMenuConfigEnabled($menuId, $active)
{
    global $_TABLES;

    $menuId = (int) $menuId;
    $active = ((int) $active === 1) ? 1 : 0;
    if ($menuId <= 0) {
        return;
    }

    DB_query("UPDATE {$_TABLES['menu_config']} SET enabled=" . $active
        . " WHERE menu_id=" . $menuId);
    MENU_invalidateRuntimeCache(true);
}

/**
 * Persist a drag/drop order previously validated by admin_security.php.
 *
 * @param int    $menuId
 * @param string $ordersString
 */
function MENU_saveElementOrder($menuId, $ordersString)
{
    global $_TABLES, $Menus;

    $menuId = (int) $menuId;
    if ($menuId <= 0) {
        return;
    }

    $orders = explode('&', (string) $ordersString);
    $elementIds = array();
    foreach ($orders as $item) {
        $parts = explode('=', $item, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $rowId = rawurldecode($parts[1]);
        if (!preg_match('/^mid_([1-9][0-9]*)$/', $rowId, $matches)) {
            continue;
        }
        $mid = (int) $matches[1];
        if (isset($Menus[$menuId]['elements'][$mid])) {
            $elementIds[] = $mid;
        }
    }

    foreach ($elementIds as $key => $mid) {
        $newOrder = ((int) $key + 1) * 10;
        DB_query("UPDATE {$_TABLES['menu_elements']} SET element_order=" . $newOrder
            . " WHERE menu_id=" . $menuId . " AND id=" . (int) $mid);
    }

    MENU_invalidateRuntimeCache(false);
}

/*
 * Saves the menu configuration
 */'''
module = module.replace(delete_anchor, wrappers, 1)

# 3. Simplify controller cases.
old_delete = """        case 'delete' :
            // delete the element
            $id      = (int) Geeklog\\Input::fPost('mid');
            $menu_id = (int) Geeklog\\Input::fPost('menuid');
            MENU_deleteChildElements($id, $menu_id);
            $Menus[$menu_id]['elements'][0]->reorderMenu();
            MENU_invalidateRuntimeCache(true);
            echo COM_refresh($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;menu=' . $menu_id);
            exit;
            break;
"""
new_delete = """        case 'delete' :
            $id      = (int) Geeklog\\Input::fPost('mid');
            $menu_id = (int) Geeklog\\Input::fPost('menuid');
            MENU_deleteElementTree($id, $menu_id);
            echo COM_refresh($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;menu=' . $menu_id);
            exit;
"""
if old_delete not in index:
    raise SystemExit('delete controller block not found')
index = index.replace(old_delete, new_delete, 1)

old_disable = """        case 'disablemenu' :
            $action = (int) Geeklog\\Input::fPost('menuactive');
            $mid    = (int) Geeklog\\Input::fPost('menutodisable');
            $sql = \"UPDATE {$_TABLES['menu_config']} SET enabled = \" . $action . \" WHERE menu_id=\" . $mid . \";\";
            DB_query($sql);
            COM_redirect($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;mid=' . $mid);
            break;
"""
new_disable = """        case 'disablemenu' :
            $action = (int) Geeklog\\Input::fPost('menuactive');
            $mid    = (int) Geeklog\\Input::fPost('menutodisable');
            MENU_setMenuConfigEnabled($mid, $action);
            COM_redirect($_CONF['site_admin_url'] . '/plugins/menu/index.php?mode=menu&amp;mid=' . $mid);
            break;
"""
if old_disable not in index:
    raise SystemExit('disablemenu block not found')
index = index.replace(old_disable, new_disable, 1)

start = index.find("} else if ( isset($_POST['orders']) && isset($_POST['menu_id']) ) {")
if start < 0:
    raise SystemExit('orders branch start not found')
end = index.find("\n} else {\n    // display the tree", start)
if end < 0:
    raise SystemExit('orders branch end not found')
new_orders = """} else if ( isset($_POST['orders']) && isset($_POST['menu_id']) ) {
    $menu_id = (int) Geeklog\\Input::fPost('menu_id');
    MENU_saveElementOrder($menu_id, Geeklog\\Input::post('orders', ''));
    exit;
"""
index = index[:start] + new_orders + index[end:]

# 4. Extend permanent module contract.
old_functions = """    'MENU_deleteChildElements',
    'MENU_saveMenuConfig',
"""
new_functions = """    'MENU_moveElement',
    'MENU_deleteChildElements',
    'MENU_deleteElementTree',
    'MENU_setMenuConfigEnabled',
    'MENU_saveElementOrder',
    'MENU_saveMenuConfig',
"""
if old_functions not in test:
    raise SystemExit('mutation contract function list anchor not found')
test = test.replace(old_functions, new_functions, 1)

extra = r'''
$forbiddenIndexSql = array(
    "UPDATE {$_TABLES['menu_elements']} SET element_order=",
    "UPDATE {$_TABLES['menu_config']} SET enabled",
    'MENU_deleteChildElements($id, $menu_id)',
);
foreach ($forbiddenIndexSql as $needle) {
    if (strpos($index, $needle) !== false) {
        fwrite(STDERR, "State-changing SQL still remains in admin/index.php: {$needle}\n");
        exit(1);
    }
}

'''
marker = "if (strpos($module, 'MENU_invalidateRuntimeCache(') === false) {"
if marker not in test:
    raise SystemExit('contract insertion marker not found')
test = test.replace(marker, extra + marker, 1)

index_path.write_text(index)
module_path.write_text(module)
test_path.write_text(test)
