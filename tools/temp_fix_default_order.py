from pathlib import Path

p = Path('admin/index.php')
s = p.read_text()
old = '''    $order_select = '<select id="menuorder" name="menuorder">' . LB;
    $order_select .= '<option value="0">' . $LANG_MENU01['first_position'] . '</option>' . LB;

    $result = DB_query("SELECT id,element_label,element_order FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND pid=0 ORDER BY element_order ASC");
    while ($row = DB_fetchArray($result)) {
        $order_select .= '<option value="' . $row['id'] . '">' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
    }
    $order_select .= '</select>' . LB;
'''
new = '''    $order_select = '<select id="menuorder" name="menuorder">' . LB;
    $order_select .= '<option value="0">' . $LANG_MENU01['first_position'] . '</option>' . LB;

    $orderRows = array();
    $result = DB_query("SELECT id,element_label,element_order FROM {$_TABLES['menu_elements']} WHERE menu_id='" . $menu_id . "' AND pid=0 ORDER BY element_order ASC, id ASC");
    while ($row = DB_fetchArray($result)) {
        $orderRows[] = $row;
    }
    $lastOrderIndex = count($orderRows) - 1;
    foreach ($orderRows as $orderIndex => $row) {
        $order_select .= '<option value="' . (int) $row['id'] . '"';
        if ($orderIndex === $lastOrderIndex) {
            $order_select .= ' selected="selected"';
        }
        $order_select .= '>' . MENU_escapeStoredText($row['element_label']) . '</option>' . LB;
    }
    $order_select .= '</select>' . LB;
'''
if old not in s:
    raise SystemExit('initial order block not found')
p.write_text(s.replace(old, new, 1))
