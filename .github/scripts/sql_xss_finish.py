from pathlib import Path

# Final renderer and SQL hygiene pass after the main hardening patch.
p = Path('classes/classMenuElement.php')
s = p.read_text()

# Dynamic Geeklog/plugin/static-page/topic menu entries must never inject raw
# labels or URLs into legacy HTML output.
s = s.replace(
    "'<li><a href=\"' . $url . '\">' . $label . '</a></li>' . LB",
    "'<li><a href=\"' . MENU_safeHref($url) . '\">' . MENU_escapeStoredText($label) . '</a></li>' . LB"
)

# Topic IDs used to build the legacy exclusion SQL originate from the user
# index. Normalize them to positive integers before interpolation.
old = '''                            if( !empty( $tids )) {
                                $sql .= " AND (tid NOT IN ('" . str_replace( ' ', "','", $tids )
                                     . "'))" . COM_getPermSQL( 'AND' );
                            } else {
'''
new = '''                            if( !empty( $tids )) {
                                $safeTids = array();
                                foreach (preg_split('/\\s+/', trim((string) $tids)) as $tid) {
                                    $tid = (int) $tid;
                                    if ($tid > 0) {
                                        $safeTids[] = $tid;
                                    }
                                }
                                if (!empty($safeTids)) {
                                    $sql .= " AND (tid NOT IN (" . implode(',', $safeTids)
                                         . "))" . COM_getPermSQL( 'AND' );
                                } else {
                                    $sql .= COM_getPermSQL( 'AND' );
                                }
                            } else {
'''
if old not in s:
    raise SystemExit('legacy topic exclusion block not found')
s = s.replace(old, new, 1)

# IDs read back from the database are still explicitly cast before SQL use.
s = s.replace(
    'DB_query("UPDATE {$_TABLES[\'menu_elements\']} SET `element_order`=" . $M[\'element_order\'] . " WHERE menu_id=".$menu_id." AND id=" . $M[\'id\'] );',
    'DB_query("UPDATE {$_TABLES[\'menu_elements\']} SET `element_order`=" . (int) $M[\'element_order\'] . " WHERE menu_id=" . $menu_id . " AND id=" . (int) $M[\'id\']);'
)
p.write_text(s)

p = Path('admin/index.php')
s = p.read_text()

# Scope the Display After lookup to the current menu and cast the resulting
# order. This closes the last cross-menu/raw numeric assumption in element edit.
old = "    $aorder   = DB_getItem($_TABLES['menu_elements'],'element_order','id=' . $aid);\n    $neworder = $aorder + 1;"
new = "    $aorder   = (int) DB_getItem($_TABLES['menu_elements'], 'element_order', 'id=' . $aid . ' AND menu_id=' . $menu_id);\n    $neworder = $aorder + 1;"
if old not in s:
    raise SystemExit('display-after lookup block not found')
s = s.replace(old, new, 1)

# Database-derived group names are rendered in select options.
s = s.replace(
    "$group_select .= '>' . key($usergroups) . '</option>' . LB;",
    "$group_select .= '>' . MENU_escapeHTML(key($usergroups)) . '</option>' . LB;"
)
p.write_text(s)

# Extend and repair the regression contract. Dollar signs in double-quoted PHP
# search strings are escaped so the contract searches source text literally.
p = Path('tests/sql_xss_contract.php')
s = p.read_text()
s = s.replace(
    'assertTrue(strpos($admin, "preg_match(\'/^mid_([1-9][0-9]*)$/\', $rowId") !== false, \'drag IDs validated\');',
    'assertTrue(strpos($admin, "preg_match(\'/^mid_([1-9][0-9]*)$/\', \\$rowId") !== false, \'drag IDs validated\');'
)
needle = "assertTrue(strpos($functions, '$menuIDSql = MENU_dbEscape($menuID);') !== false, 'autotag menu name escaped');\n"
extra = needle + r'''assertTrue(strpos($class, "MENU_safeHref(\$url)") !== false, 'dynamic legacy URLs use safe href helper');
assertTrue(strpos($class, "MENU_escapeStoredText(\$label)") !== false, 'dynamic legacy labels are escaped');
assertTrue(strpos($class, "str_replace( ' ', \"','\", \$tids )") === false, 'raw legacy topic ID interpolation removed');
assertTrue(strpos($class, "preg_split('/\\s+/', trim((string) \$tids))") !== false, 'legacy topic IDs normalized');
assertTrue(strpos($admin, "'id=' . \$aid . ' AND menu_id=' . \$menu_id") !== false, 'display-after lookup scoped to menu');
'''
if needle not in s:
    raise SystemExit('contract insertion marker not found')
s = s.replace(needle, extra, 1)
p.write_text(s)
