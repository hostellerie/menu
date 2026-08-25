from pathlib import Path

p = Path('functions.inc')
s = p.read_text()

needle = "require_once $plugin_path . 'presentation.php';\n"
replacement = needle + "require_once $plugin_path . 'cache_filesystem.php';\n"
if needle not in s:
    raise SystemExit('presentation include not found')
s = s.replace(needle, replacement, 1)

start = s.find('function MENU_cache_clean_directories(')
if start < 0:
    raise SystemExit('legacy cache cleanup function not found')
end = s.find('\nfunction MENU_CACHE_cleanup_plugin', start)
if end < 0:
    raise SystemExit('legacy cache cleanup end not found')
s = s[:start] + s[end + 1:]

if s.count('function MENU_cache_clean_directories(') != 0:
    raise SystemExit('legacy cache cleanup function still present')

p.write_text(s)
