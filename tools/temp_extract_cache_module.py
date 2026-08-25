from pathlib import Path

p = Path('functions.inc')
s = p.read_text()

include_needle = "require_once $plugin_path . 'cache_filesystem.php';\n"
include_replacement = include_needle + "require_once $plugin_path . 'cache.php';\n"
if include_needle not in s:
    raise SystemExit('cache_filesystem include not found')
if "require_once $plugin_path . 'cache.php';" not in s:
    s = s.replace(include_needle, include_replacement, 1)

start = s.find('$MENU_TEMPLATE_OPTIONS = array(')
if start < 0:
    raise SystemExit('MENU_TEMPLATE_OPTIONS block not found')

compress_start = s.find('function MENU_compress(', start)
if compress_start < 0:
    raise SystemExit('MENU_compress not found')

brace = s.find('{', compress_start)
if brace < 0:
    raise SystemExit('MENU_compress opening brace not found')

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
    raise SystemExit('MENU_compress closing brace not found')

s = s[:start] + s[end:]

for name in (
    'MENU_CACHE_cleanup_plugin',
    'MENU_CACHE_remove_instance',
    'MENU_CACHE_create_instance',
    'MENU_CACHE_check_instance',
    'MENU_CACHE_get_instance_update',
    'MENU_CACHE_instance_filename',
    'MENU_compress',
):
    if ('function ' + name + '(') in s:
        raise SystemExit(name + ' still remains in functions.inc')

p.write_text(s)
