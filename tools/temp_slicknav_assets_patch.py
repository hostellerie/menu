from pathlib import Path

p = Path('functions.inc')
s = p.read_text()

s = s.replace("    $needsLegacyAssets = false;\n", "    $needsSlickNav = false;\n", 1)
s = s.replace("""            if (!empty($menu['active'])
                && !MENU_themeHandlesPresentation(isset($menu['menu_name']) ? $menu['menu_name'] : '')) {
                $needsLegacyAssets = true;
                break;
            }
""", """            if (!empty($menu['active'])
                && (int) $menu['menu_type'] === 1
                && !MENU_themeHandlesPresentation(isset($menu['menu_name']) ? $menu['menu_name'] : '')) {
                $needsSlickNav = true;
                break;
            }
""", 1)
s = s.replace("if ($needsLegacyAssets && $loadLegacyCss)", "if ($needsSlickNav && $loadLegacyCss)", 1)
s = s.replace("if ($needsLegacyAssets && $loadLegacyJs)", "if ($needsSlickNav && $loadLegacyJs)", 1)

old = '''                $siteName = json_encode((string) $_CONF['site_name']);
                if ($siteName === false) {
                    $siteName = '"Menu"';
                }
                $js .= LB . "jQuery(document).ready(function() {
                    jQuery('#gl_menu" . $menu['menu_id'] . "').slicknav(({
                        label: " . $siteName . ",
                        allowParentLinks: true,
                    }));
                    jQuery( '.slicknav_nav .gl_menu" . $menu['menu_id'] . "' ).removeClass( 'gl_menu" . $menu['menu_id'] . "' );
                });" . LB;
'''
new = '''                if ((int) $menu['menu_type'] === 1 && $loadLegacyJs) {
                    $siteName = json_encode((string) $_CONF['site_name']);
                    if ($siteName === false) {
                        $siteName = '"Menu"';
                    }
                    $js .= LB . "jQuery(document).ready(function() {
                        jQuery('#gl_menu" . $menu['menu_id'] . "').slicknav(({
                            label: " . $siteName . ",
                            allowParentLinks: true,
                        }));
                        jQuery( '.slicknav_nav .gl_menu" . $menu['menu_id'] . "' ).removeClass( 'gl_menu" . $menu['menu_id'] . "' );
                    });" . LB;
                }
'''
if old not in s:
    raise SystemExit('SlickNav initialization block not found')
s = s.replace(old, new, 1)

if '$needsLegacyAssets' in s:
    raise SystemExit('legacy asset flag remains')

p.write_text(s)
