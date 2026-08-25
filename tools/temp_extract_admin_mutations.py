from pathlib import Path

index_path = Path('admin/index.php')
module_path = Path('admin_menu_mutations.php')

s = index_path.read_text()
module = module_path.read_text().rstrip() + '\n\n'

include_needle = "require_once $_CONF['path'].'plugins/menu/admin_menu_views.php';\n"
include_line = "require_once $_CONF['path'].'plugins/menu/admin_menu_mutations.php';\n"
if include_needle not in s:
    raise SystemExit('admin view include not found')
if include_line not in s:
    s = s.replace(include_needle, include_needle + include_line, 1)

names = [
    'MENU_saveNewMenu',
    'MENU_saveEditMenuElement',
    'MENU_changeActiveStatusElement',
    'MENU_changeActiveStatusMenu',
    'MENU_deleteChildElements',
    'MENU_saveMenuConfig',
]

blocks = []
for name in names:
    marker = 'function ' + name
    pos = s.find(marker)
    if pos < 0:
        raise SystemExit(name + ' not found')

    start = pos
    comment_start = s.rfind('/*', 0, pos)
    comment_end = s.find('*/', comment_start, pos) if comment_start >= 0 else -1
    if comment_start >= 0 and comment_end >= 0:
        between = s[comment_end + 2:pos]
        if between.strip() == '':
            start = comment_start

    brace = s.find('{', pos)
    if brace < 0:
        raise SystemExit(name + ' opening brace not found')

    depth = 0
    end = None
    for i in range(brace, len(s)):
        if s[i] == '{':
            depth += 1
        elif s[i] == '}':
            depth -= 1
            if depth == 0:
                end = i + 1
                break
    if end is None:
        raise SystemExit(name + ' closing brace not found')

    blocks.append(s[start:end].strip())
    s = s[:start] + s[end:]

for name in names:
    if ('function ' + name) in s:
        raise SystemExit(name + ' still remains in admin/index.php')

module += '\n\n'.join(blocks) + '\n'
module_path.write_text(module)
index_path.write_text(s)
