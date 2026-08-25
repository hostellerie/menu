from pathlib import Path

p = Path('functions.inc')
s = p.read_text()

# Rename the dedicated runtime flag everywhere first.
s = s.replace('$needsLegacyAssets', '$needsSlickNav')

old_condition = """            if (!empty($menu['active'])
                && !MENU_themeHandlesPresentation(isset($menu['menu_name']) ? $menu['menu_name'] : '')) {
                $needsSlickNav = true;
                break;
            }
"""
new_condition = """            if (!empty($menu['active'])
                && (int) $menu['menu_type'] === 1
                && !MENU_themeHandlesPresentation(isset($menu['menu_name']) ? $menu['menu_name'] : '')) {
                $needsSlickNav = true;
                break;
            }
"""
if old_condition not in s:
    raise SystemExit('SlickNav need condition not found')
s = s.replace(old_condition, new_condition, 1)

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
if s.count("(int) $menu['menu_type'] === 1") < 2:
    raise SystemExit('SlickNav menu type guards missing')

p.write_text(s)
