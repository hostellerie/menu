from pathlib import Path

p = Path('admin/index.php')
s = p.read_text()

# New menu save: central cache invalidation.
s = s.replace("""    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
    $randID = rand();
    DB_save($_TABLES['vars'],'name,value',\"'cacheid',$randID\");
    MENU_initMENU(true);
}""", """    MENU_invalidateRuntimeCache(true);
}""", 1)

# Move element.
s = s.replace("""    $Menus[$menu_id]['elements'][$pid]->reorderMenu();
    MENU_CACHE_remove_instance('menu');

    return;
}""", """    $Menus[$menu_id]['elements'][$pid]->reorderMenu();
    MENU_invalidateRuntimeCache(true);

    return;
}""", 1)

# Edited element: avoid two full init passes; reorder then invalidate once.
s = s.replace("""    DB_query($sql);
    MENU_initMENU(true);
    $Menus[$menu_id]['elements'][$pid]->reorderMenu();
    MENU_initMENU(true);
}""", """    DB_query($sql);
    $Menus[$menu_id]['elements'][$pid]->reorderMenu();
    MENU_invalidateRuntimeCache(true);
}""", 1)

# Active status element/menu.
s = s.replace("""    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
    MENU_CACHE_remove_instance('js');
}""", """    MENU_invalidateRuntimeCache(true);
}""", 1)
s = s.replace("""    MENU_CACHE_remove_instance('menu');
}

function MENU_deleteMenu($menu_id) {""", """    MENU_invalidateRuntimeCache(true);
}

function MENU_deleteMenu($menu_id) {""", 1)

# Remove dead whole-menu delete wrapper, keep recursive subtree deletion.
start = s.find('function MENU_deleteMenu($menu_id) {')
end_marker = '/**\n* Recursivly deletes all elements and child elements'
end = s.find(end_marker, start)
if start < 0 or end < 0:
    raise SystemExit('MENU_deleteMenu block not found')
s = s[:start] + s[end:]

# Recursive subtree delete must not flush cache on every recursion level.
s = s.replace("""    $sql = \"DELETE FROM \" . $_TABLES['menu_elements'] . \" WHERE id=\" . $id;
    DB_query( $sql );

    MENU_CACHE_remove_instance('menu');
}""", """    $sql = \"DELETE FROM \" . $_TABLES['menu_elements'] . \" WHERE id=\" . $id . \" AND menu_id=\" . (int) $menu_id;
    DB_query($sql);
}""", 1)

# Config save.
s = s.replace("""    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
    $randID = rand();
    DB_save($_TABLES['vars'],'name,value',\"'cacheid',$randID\");
    return;
}""", """    MENU_invalidateRuntimeCache(true);
    return;
}""", 1)

# Remove duplicate controller invalidation / init calls.
s = s.replace("""        case 'saveedit' :
            MENU_saveEditMenuElement();
            MENU_CACHE_remove_instance('menu');
            COM_redirect""", """        case 'saveedit' :
            MENU_saveEditMenuElement();
            COM_redirect""", 1)
s = s.replace("""        case 'activate' :
            MENU_changeActiveStatusElement();
            MENU_initMENU();
            $content = MENU_displayTree""", """        case 'activate' :
            MENU_changeActiveStatusElement();
            $content = MENU_displayTree""", 1)
s = s.replace("""        case 'menuactivate' :
            MENU_changeActiveStatusMenu();
            MENU_initMENU();
            $content = MENU_displayMenuList""", """        case 'menuactivate' :
            MENU_changeActiveStatusMenu();
            $content = MENU_displayMenuList""", 1)

# Element subtree deletion invalidates exactly once after DB/order changes.
s = s.replace("""            MENU_deleteChildElements( $id, $menu_id );
            $Menus[$menu_id]['elements'][0]->reorderMenu();
            echo COM_refresh""", """            MENU_deleteChildElements($id, $menu_id);
            $Menus[$menu_id]['elements'][0]->reorderMenu();
            MENU_invalidateRuntimeCache(true);
            echo COM_refresh""", 1)

# Whole-menu deletion controller path is dead; admin_security handles it.
start = s.find("        case 'deletemenu' :")
if start >= 0:
    end = s.find("        case 'config' :", start)
    if end < 0:
        raise SystemExit('deletemenu switch end not found')
    s = s[:start] + s[end:]

# Config helper already refreshes runtime state.
s = s.replace("""            MENU_saveMenuConfig($menu_id);
            MENU_initMENU();
            $content = MENU_menuConfig""", """            MENU_saveMenuConfig($menu_id);
            $content = MENU_menuConfig""", 1)

# Defaults: invalidate once after all writes.
s = s.replace("""} else if ( isset($_POST['defaults']) ) {
    MENU_CACHE_remove_instance('menu');
    MENU_CACHE_remove_instance('css');
    $menu_id = (int) Geeklog\\Input::fPost('menu_id');""", """} else if ( isset($_POST['defaults']) ) {
    $menu_id = (int) Geeklog\\Input::fPost('menu_id');""", 1)
s = s.replace("""    }
    MENU_initMenu();
    $content = MENU_displayMenuList( );
} else if ( isset($_POST['cancel'])""", """    }
    MENU_invalidateRuntimeCache(true);
    $content = MENU_displayMenuList( );
} else if ( isset($_POST['cancel'])""", 1)

# Drag ordering: invalidate once, without forcing an unnecessary HTML refresh.
s = s.replace("""    MENU_CACHE_remove_instance('menu');
    
    exit;
}""", """    MENU_invalidateRuntimeCache(false);

    exit;
}""", 1)

p.write_text(s)
