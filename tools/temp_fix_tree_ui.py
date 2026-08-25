from pathlib import Path

class_path = Path('classes/classMenuElement.php')
views_path = Path('admin_element_views.php')

s = class_path.read_text()
old = "            $info       = COM_getTooltip('<img src=\"' . $_CONF['site_admin_url'] . '/plugins/menu/images/info.png\" alt=\"\"' . XHTML . '>', $elementDetails, '', MENU_escapeStoredText($this->label), $template = 'help');"
new = "            $info = '<img src=\"' . MENU_escapeHTML($_CONF['site_admin_url']) . '/plugins/menu/images/info.png\" alt=\"' . MENU_escapeHTML($LANG_MENU01['info']) . '\" title=\"' . MENU_escapeHTML(strip_tags($LANG_MENU01['type'] . ': ' . $LANG_MENU_TYPES[$this->type])) . '\"' . XHTML . '>';"
if old not in s:
    raise SystemExit('element tooltip source not found')
s = s.replace(old, new, 1)
class_path.write_text(s)

v = views_path.read_text()
line = "    $_SCRIPTS->setJavaScriptFile('menu_order_handle', '/admin/plugins/menu/js/menu-order-handle.js');\n"
if line not in v:
    raise SystemExit('registered ordering script line not found')
v = v.replace(line, '', 1)
views_path.write_text(v)
