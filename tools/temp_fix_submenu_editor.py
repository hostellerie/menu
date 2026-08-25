from pathlib import Path

p = Path('admin/index.php')
s = p.read_text()

old_create_types = '''    $type_select = '<select id="menutype" name="menutype" onChange="toggleFields();">' . LB;
    while ( $types = current($LANG_MENU_TYPES) ) {
        if ( $spCount == 0 && key($LANG_MENU_TYPES) == 5 ) {
            // skip it
        } else {
            if ( ($Menus[$menu_id]['menu_type'] == 2 || $Menus[$menu_id]['menu_type'] == 4 ) && (key($LANG_MENU_TYPES) == 1 || key($LANG_MENU_TYPES) == 3)){
                // skip it
            } else {
                $type_select .= '<option value="' . key($LANG_MENU_TYPES) . '"';
                $type_select .= '>' . $types . '</option>' . LB;
            }
        }
        next($LANG_MENU_TYPES);
    }
    $type_select .= '</select>' . LB;
'''
new_create_types = '''    $type_select = '<select id="menutype" name="menutype" onChange="toggleFields();">' . LB;
    $allowedTypes = MENU_getAllowedElementTypes(
        $LANG_MENU_TYPES,
        $Menus[$menu_id]['menu_type'],
        $spCount > 0,
        null,
        $topicCount > 0
    );
    foreach ($allowedTypes as $typeId => $typeLabel) {
        $type_select .= '<option value="' . (int) $typeId . '">'
            . MENU_escapeHTML($typeLabel) . '</option>' . LB;
    }
    $type_select .= '</select>' . LB;
'''
if old_create_types not in s:
    raise SystemExit('create type block not found')
s = s.replace(old_create_types, new_create_types, 1)

old_create_parent = '''    if ( $Menus[$menu_id]['menu_type'] == 2 || $Menus[$menu_id]['menu_type'] == 4 ) {
        $parent_select = '<input type="hidden" name="pid" id="pid" value="0"'.XHTML.'>'.$LANG_MENU01['top_level'];
    } else {
        $parent_select = '<select name="pid" id="pid">' . LB;
        $parent_select .= '<option value="0">' . $LANG_MENU01['top_level'] . '</option>' . LB;
        $result = DB_query("SELECT id,element_label FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND element_type=1");
        while ($row = DB_fetchArray($result)) {
            $parent_select .= '<option value="' . $row['id'] . '">' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
        }
        $parent_select .= '</select>' . LB;
    }
'''
new_create_parent = '''    $parent_select = '<select name="pid" id="pid">' . LB;
    $parent_select .= '<option value="0">' . $LANG_MENU01['top_level'] . '</option>' . LB;
    $result = DB_query("SELECT id,element_label FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND element_type=1 ORDER BY element_order ASC, id ASC");
    while ($row = DB_fetchArray($result)) {
        $parent_select .= '<option value="' . (int) $row['id'] . '">' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
    }
    $parent_select .= '</select>' . LB;
'''
if old_create_parent not in s:
    raise SystemExit('create parent block not found')
s = s.replace(old_create_parent, new_create_parent, 1)

old_edit_types = '''    $type_select = '<select id="menutype" name="menutype" onChange="toggleFields();">' . LB;
    while ( $types = current($LANG_MENU_TYPES) ) {
        if ( key($LANG_MENU_TYPES) < 4 ){
            // skip it
        } else {
            $type_select .= '<option value="' . key($LANG_MENU_TYPES) . '"';
            $type_select .= ($Menus[$menu_id]['elements'][$mid]->type==key($LANG_MENU_TYPES) ? ' selected="selected"' : '') . '>' . $types . '</option>' . LB;
        }
        next($LANG_MENU_TYPES);
    }
    $type_select .= '</select>' . LB;
'''
new_edit_types = '''    $type_select = '<select id="menutype" name="menutype" onChange="toggleFields();">' . LB;
    $allowedTypes = MENU_getAllowedElementTypes(
        $LANG_MENU_TYPES,
        $Menus[$menu_id]['menu_type'],
        in_array('staticpages', $_PLUGINS),
        $Menus[$menu_id]['elements'][$mid]->type,
        true
    );
    foreach ($allowedTypes as $typeId => $typeLabel) {
        $type_select .= '<option value="' . (int) $typeId . '"';
        $type_select .= ($Menus[$menu_id]['elements'][$mid]->type == $typeId ? ' selected="selected"' : '')
            . '>' . MENU_escapeHTML($typeLabel) . '</option>' . LB;
    }
    $type_select .= '</select>' . LB;
'''
if old_edit_types not in s:
    raise SystemExit('edit type block not found')
s = s.replace(old_edit_types, new_edit_types, 1)

old_edit_parent = '''    if ( $Menus[$menu_id]['menu_type'] == 2 || $Menus[$menu_id]['menu_type'] == 4 ) {
        $parent_select = '<input type="hidden" name="pid" id="pid" value="0"'.XHTML.'>'.$LANG_MENU01['top_level'];
    } else {
        $parent_select = '<select id="pid" name="pid">' . LB;
        $parent_select .= '<option value="0">' . $LANG_MENU01['top_level'] . '</option>' . LB;
        $result = DB_query("SELECT id,element_label FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND element_type=1");
        while ($row = DB_fetchArray($result)) {
            $parent_select .= '<option value="' . $row['id'] . '" ' . ($Menus[$menu_id]['elements'][$mid]->pid==$row['id'] ? 'selected="selected"' : '') . '>' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
        }
        $parent_select .= '</select>' . LB;
    }
'''
new_edit_parent = '''    $parent_select = '<select id="pid" name="pid">' . LB;
    $parent_select .= '<option value="0">' . $LANG_MENU01['top_level'] . '</option>' . LB;
    $result = DB_query("SELECT id,element_label FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND element_type=1 ORDER BY element_order ASC, id ASC");
    while ($row = DB_fetchArray($result)) {
        if ((int) $row['id'] === (int) $mid) {
            continue;
        }
        $parent_select .= '<option value="' . (int) $row['id'] . '" '
            . ($Menus[$menu_id]['elements'][$mid]->pid == $row['id'] ? 'selected="selected"' : '')
            . '>' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
    }
    $parent_select .= '</select>' . LB;
'''
if old_edit_parent not in s:
    raise SystemExit('edit parent block not found')
s = s.replace(old_edit_parent, new_edit_parent, 1)

p.write_text(s)
