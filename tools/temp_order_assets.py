from pathlib import Path

views = Path('admin_element_views.php')
s = views.read_text()
old = "    $_SCRIPTS->setJavaScriptLibrary('jquery');\n\n    $retval = '';"
new = "    $_SCRIPTS->setJavaScriptLibrary('jquery');\n    $_SCRIPTS->setJavaScriptFile('menu_tablednd', '/admin/plugins/menu/js/tablednd_0_6.js');\n    $_SCRIPTS->setJavaScriptFile('menu_order_handle', '/admin/plugins/menu/js/menu-order-handle.js');\n\n    $retval = '';"
if old not in s:
    raise SystemExit('MENU_displayTree jQuery block not found')
s = s.replace(old, new, 1)
views.write_text(s)

template = Path('templates/default/menutree.thtml')
t = template.read_text()
for line in [
    '<script type="text/javascript" src="{site_admin_url}/plugins/menu/js/tablednd_0_6.js"></script>\n',
    '<script type="text/javascript" src="{site_admin_url}/plugins/menu/js/menu-order-handle.js"></script>\n',
]:
    if line not in t:
        raise SystemExit('direct ordering script tag not found: ' + line.strip())
    t = t.replace(line, '', 1)
template.write_text(t)
