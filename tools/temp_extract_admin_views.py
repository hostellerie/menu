from pathlib import Path

p = Path('admin/index.php')
s = p.read_text()

include_needle = "require_once $_CONF['path'].'plugins/menu/image_upload.php';\n"
include_replacement = include_needle + "require_once $_CONF['path'].'plugins/menu/admin_menu_views.php';\n"
if include_needle not in s:
    raise SystemExit('image_upload include not found')
if "admin_menu_views.php" not in s:
    s = s.replace(include_needle, include_replacement, 1)

s = s.replace('Menu Plugin 1.2.8', 'Menu Plugin 1.3.0', 1)

names = ['MENU_displayMenuList', 'MENU_cloneMenu', 'MENU_createMenu']
blocks = []

for name in names:
    marker = 'function ' + name
    start = s.find(marker)
    if start < 0:
        raise SystemExit(name + ' not found')

    comment_start = s.rfind('/*', 0, start)
    if comment_start >= 0 and s.find('*/', comment_start, start) >= 0:
        start = comment_start

    brace = s.find('{', s.find(marker, start))
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
        raise SystemExit(name + ' remains in admin/index.php')

# The templates now own their actual action URLs, so these legacy variables are dead.
cleaned = []
for block in blocks:
    lines = []
    for line in block.splitlines():
        if "'form_action'" in line:
            continue
        lines.append(line)
    cleaned.append('\n'.join(lines))

module = '''<?php

if (!defined('VERSION')) {
    die('This file can not be used on its own.');
}

/**
 * Menu administration view builders.
 *
 * These functions render administration pages only. Mutations remain in
 * dedicated controllers/helpers so presentation does not own persistence.
 */

''' + '\n\n'.join(cleaned) + '\n'

Path('admin_menu_views.php').write_text(module)
p.write_text(s)
