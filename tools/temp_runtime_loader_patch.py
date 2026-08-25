from pathlib import Path

p = Path('functions.inc')
s = p.read_text()

needle = "require_once $_CONF['path'] . 'plugins/menu/classes/classMenuElement.php';\n"
replacement = needle + "require_once $_CONF['path'] . 'plugins/menu/runtime_loader.php';\n"
if needle not in s:
    raise SystemExit('class include not found')
s = s.replace(needle, replacement, 1)

start = s.find('function MENU_initMenu() {')
if start < 0:
    raise SystemExit('MENU_initMenu start not found')
end = s.find('\nfunction MENU_getMenu(', start)
if end < 0:
    raise SystemExit('MENU_initMenu end not found')

new_func = '''function MENU_initMenu($force = false) {
    global $Menus, $_GROUPS;

    $mbadmin = SEC_hasRights('menu.admin');
    $root = SEC_inGroup('Root');
    $Menus = MENU_loadRuntimeMenus($mbadmin, $root, $_GROUPS);
}
'''

s = s[:start] + new_func + s[end:]
p.write_text(s)
