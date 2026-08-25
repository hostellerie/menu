from pathlib import Path

p = Path('admin/index.php')
s = p.read_text()
old = "$_SCRIPTS->setJavaScriptFile('menu', '/admin/plugins/menu/js/tablednd_0_6.js');"
new = "$_SCRIPTS->setJavaScriptFile('menu_order_handle', '/admin/plugins/menu/js/menu-order-handle.js');"
if old not in s:
    raise SystemExit('legacy tableDnD include not found')
s = s.replace(old, new, 1)
if 'tablednd_0_6.js' in s or 'tablednd_0_5.js' in s or '/tablednd.js' in s:
    raise SystemExit('legacy tableDnD asset reference remains')
p.write_text(s)
