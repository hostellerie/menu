from pathlib import Path

p = Path('admin_element_views.php')
s = p.read_text()

start = s.find('    $dragTokenName = MENU_adminTokenName();')
if start < 0:
    raise SystemExit('legacy drag bootstrap start not found')
end_marker = '    $_SCRIPTS->setJavaScript($js, true);\n'
end = s.find(end_marker, start)
if end < 0:
    raise SystemExit('legacy drag bootstrap end not found')
end += len(end_marker)

s = s[:start] + s[end:]

for needle in ('.tableDnD(', 'jQuery.tableDnD', '$dragTokenName', '$dragPostUrl'):
    if needle in s:
        raise SystemExit('legacy drag bootstrap remains: ' + needle)

if "setJavaScriptFile('menu_order_handle', '/admin/plugins/menu/js/menu-order-handle.js')" not in s:
    raise SystemExit('native menu ordering asset include missing')

p.write_text(s)
