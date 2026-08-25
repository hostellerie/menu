from pathlib import Path

p = Path('admin/index.php')
s = p.read_text()

def remove_between(text, start_marker, end_marker, label):
    start = text.find(start_marker)
    if start < 0:
        raise SystemExit(label + ' start not found')
    end = text.find(end_marker, start)
    if end < 0:
        raise SystemExit(label + ' end not found')
    return text[:start] + text[end:]

s = remove_between(
    s,
    'function MENU_saveCloneMenu( ) {',
    'function MENU_createMenu( ) {',
    'legacy clone persistence function'
)

s = remove_between(
    s,
    'function MENU_saveNewMenuElement ( ) {',
    'function MENU_editElement( $menu_id, $mid ) {',
    'legacy element persistence function'
)

switch_start = s.rfind("        case 'save' :")
if switch_start < 0:
    raise SystemExit('legacy save switch case not found')
switch_end = s.find("        case 'savenewmenu' :", switch_start)
if switch_end < 0:
    raise SystemExit('savenewmenu switch case not found')
s = s[:switch_start] + s[switch_end:]

switch_start = s.rfind("        case 'saveclonemenu' :")
if switch_start < 0:
    raise SystemExit('legacy saveclonemenu switch case not found')
switch_end = s.find("        case 'saveeditmenu' :", switch_start)
if switch_end < 0:
    raise SystemExit('saveeditmenu switch case not found')
s = s[:switch_start] + s[switch_end:]

if 'function MENU_saveCloneMenu' in s or 'function MENU_saveNewMenuElement' in s:
    raise SystemExit('legacy persistence function still present')

p.write_text(s)
