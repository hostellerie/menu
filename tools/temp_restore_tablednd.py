from pathlib import Path
import subprocess

root = Path('.')
legacy_commit = '570629259016523fb3419290a7b060985db833b4'
asset = subprocess.check_output([
    'git', 'show', legacy_commit + ':admin/js/tablednd_0_6.js'
])
(root / 'admin/js/tablednd_0_6.js').write_bytes(asset)

path = root / 'classes/classMenuElement.php'
s = path.read_text()
old = "            $info = '<img src=\"' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/images/info.png\" alt=\"' . MENU_escapeHTML($LANG_MENU01['info']) . '\" title=\"' . MENU_escapeHTML(strip_tags($LANG_MENU01['type'] . ': ' . $LANG_MENU_TYPES[$this->type])) . '\"' . XHTML . '>';"
new = "            $infoText = MENU_escapeHTML(strip_tags($LANG_MENU01['type'] . ': ' . $LANG_MENU_TYPES[$this->type]));\n            $info = '<span class=\"menu-info-tooltip\" tabindex=\"0\"><img src=\"' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/images/info.png\" alt=\"' . MENU_escapeHTML($LANG_MENU01['info']) . '\"' . XHTML . '><span class=\"menu-info-tooltip-text\">' . $infoText . '</span></span>';"
if old not in s:
    raise SystemExit('current native tooltip source not found')
s = s.replace(old, new, 1)
path.write_text(s)
