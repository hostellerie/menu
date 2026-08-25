from pathlib import Path

p = Path('admin/index.php')
s = p.read_text()
repls = {
"        'menu_bg_filename'          => $menuConfig['menu_bg_filename'],": "        'menu_bg_preview'           => MENU_legacyImagePreview($menuConfig['menu_bg_filename'], true),",
"        'menu_hover_filename'       => $menuConfig['menu_hover_filename'],": "        'menu_hover_preview'        => MENU_legacyImagePreview($menuConfig['menu_hover_filename'], true),",
"        'menu_parent_filename'      => $menuConfig['menu_parent_filename'],": "        'menu_parent_preview'       => MENU_legacyImagePreview($menuConfig['menu_parent_filename'], false),",
}
for old, new in repls.items():
    if old not in s:
        raise SystemExit('missing admin pattern: ' + old)
    s = s.replace(old, new, 1)
p.write_text(s)

p = Path('templates/default/menuconfig.thtml')
s = p.read_text()
s = s.replace('&nbsp;{LANG_MENU01[currently]}:\n\t\t&nbsp;<img src="{site_url}/images/menu/{menu_bg_filename}" width="27" height="27" style="vertical-align:middle;border:none;" alt="" {xhtml}>', '&nbsp;{LANG_MENU01[currently]}:\n        &nbsp;{menu_bg_preview}')
s = s.replace('&nbsp;{LANG_MENU01[currently]}:\n\t\t&nbsp;<img src="{site_url}/images/menu/{menu_hover_filename}" width="27" height="27" style="vertical-align:middle;border:none;" alt="" {xhtml}>', '&nbsp;{LANG_MENU01[currently]}:\n        &nbsp;{menu_hover_preview}')
s = s.replace('&nbsp;{LANG_MENU01[currently]}:\n\t\t&nbsp;<img id="cpiimage" src="{site_url}/images/menu/{menu_parent_filename}" style="vertical-align:middle;border:none;" alt="" {xhtml}>', '&nbsp;{LANG_MENU01[currently]}:\n        &nbsp;{menu_parent_preview}')
if '{menu_bg_filename}' in s or '{menu_hover_filename}' in s or '{menu_parent_filename}' in s:
    raise SystemExit('legacy image filename placeholder still present')
p.write_text(s)
