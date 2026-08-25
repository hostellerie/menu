from pathlib import Path

# Load CSS security helpers with the shared runtime helpers.
p = Path('storage.php')
s = p.read_text()
needle = "require_once __DIR__ . '/runtime_config.php';\n"
if "css_security.php" not in s:
    s = s.replace(needle, needle + "require_once __DIR__ . '/css_security.php';\n", 1)
p.write_text(s)

# Frontend legacy CSS: never feed stored values directly into CSS.
p = Path('functions.inc')
s = p.read_text()
old = '''                    $ms->set_var('menu_id',$menu['menu_id']);
                    $ms->set_var('site_url',$_CONF['site_url']);
                    if (is_array($menu['config'])) {
                        foreach ($menu['config'] AS $name => $value) {
                            if ($name == 'use_images' && $value == 0) {
                                $ms->set_var('url1','');
                                $ms->set_var('url2','');
                                continue;
                            } else if ($name == 'use_images' && $value == 1) {
                                $url1 = "url({$_CONF['site_url']}/images/menu/{menu_bg_filename}) repeat-x";
                                $url2 = "url({$_CONF['site_url']}/images/menu/{menu_hover_filename}) repeat-x";
                                $ms->set_var('url1',$url1);
                                $ms->set_var('url2',$url2);
                                continue;
                            }
                            $ms->set_var($name,$value);
                        }
                    }
'''
new = '''                    $ms->set_var('menu_id', (int) $menu['menu_id']);
                    $ms->set_var('url1', '');
                    $ms->set_var('url2', '');
                    $ms->set_var('menu_parent_background', '');
                    $colorNames = array(
                        'main_menu_bg_color', 'main_menu_hover_bg_color',
                        'main_menu_text_color', 'main_menu_hover_text_color',
                        'submenu_text_color', 'submenu_hover_text_color',
                        'submenu_background_color', 'submenu_hover_bg_color',
                        'submenu_highlight_color', 'submenu_shadow_color',
                    );
                    if (is_array($menu['config'])) {
                        foreach ($menu['config'] AS $name => $value) {
                            if (in_array($name, $colorNames, true)) {
                                $ms->set_var($name, MENU_cssColor($value));
                                continue;
                            }
                            if ($name === 'menu_bg_filename'
                                || $name === 'menu_hover_filename'
                                || $name === 'menu_parent_filename') {
                                $ms->set_var($name, MENU_cssImageFilename($value));
                                continue;
                            }
                            if ($name === 'use_images') {
                                continue;
                            }
                            if ($name === 'menu_alignment') {
                                $ms->set_var($name, ((int) $value === 1) ? 1 : 0);
                                continue;
                            }
                        }
                        if (isset($menu['config']['use_images']) && (int) $menu['config']['use_images'] === 1) {
                            $ms->set_var('url1', MENU_cssImageBackground(
                                isset($menu['config']['menu_bg_filename']) ? $menu['config']['menu_bg_filename'] : '',
                                'repeat-x'
                            ));
                            $ms->set_var('url2', MENU_cssImageBackground(
                                isset($menu['config']['menu_hover_filename']) ? $menu['config']['menu_hover_filename'] : '',
                                'repeat-x'
                            ));
                            $ms->set_var('menu_parent_background', MENU_cssImageBackground(
                                isset($menu['config']['menu_parent_filename']) ? $menu['config']['menu_parent_filename'] : ''
                            ));
                        }
                    }
'''
if old not in s:
    raise SystemExit('functions.inc CSS block not found')
s = s.replace(old, new, 1)
p.write_text(s)

# Preview uses exactly the same safe CSS contract.
p = Path('admin/preview.php')
s = p.read_text()
old = '''$T->set_var('menu_id', $menu_id);
$T->set_var('site_url', $_CONF['site_url']);
$T->set_var('url1', '');
$T->set_var('url2', '');

$image_url = MENU_imageUrl();

if (isset($Menus[$menu_id]['config']) && is_array($Menus[$menu_id]['config'])) {
    foreach ($Menus[$menu_id]['config'] as $name => $value) {
        if ($name == 'use_images') {
            if ((int) $value === 1) {
                $T->set_var('url1', 'url(' . $image_url . '{menu_bg_filename}) repeat-x');
                $T->set_var('url2', 'url(' . $image_url . '{menu_hover_filename}) repeat-x');
            }
            continue;
        }
        $T->set_var($name, $value);
    }
}
'''
new = '''$T->set_var('menu_id', $menu_id);
$T->set_var('url1', '');
$T->set_var('url2', '');
$T->set_var('menu_parent_background', '');

$colorNames = array(
    'main_menu_bg_color', 'main_menu_hover_bg_color',
    'main_menu_text_color', 'main_menu_hover_text_color',
    'submenu_text_color', 'submenu_hover_text_color',
    'submenu_background_color', 'submenu_hover_bg_color',
    'submenu_highlight_color', 'submenu_shadow_color',
);

if (isset($Menus[$menu_id]['config']) && is_array($Menus[$menu_id]['config'])) {
    foreach ($Menus[$menu_id]['config'] as $name => $value) {
        if (in_array($name, $colorNames, true)) {
            $T->set_var($name, MENU_cssColor($value));
            continue;
        }
        if ($name === 'menu_bg_filename'
            || $name === 'menu_hover_filename'
            || $name === 'menu_parent_filename') {
            $T->set_var($name, MENU_cssImageFilename($value));
            continue;
        }
        if ($name === 'menu_alignment') {
            $T->set_var($name, ((int) $value === 1) ? 1 : 0);
        }
    }

    if (isset($Menus[$menu_id]['config']['use_images'])
        && (int) $Menus[$menu_id]['config']['use_images'] === 1) {
        $T->set_var('url1', MENU_cssImageBackground(
            isset($Menus[$menu_id]['config']['menu_bg_filename']) ? $Menus[$menu_id]['config']['menu_bg_filename'] : '',
            'repeat-x'
        ));
        $T->set_var('url2', MENU_cssImageBackground(
            isset($Menus[$menu_id]['config']['menu_hover_filename']) ? $Menus[$menu_id]['config']['menu_hover_filename'] : '',
            'repeat-x'
        ));
        $T->set_var('menu_parent_background', MENU_cssImageBackground(
            isset($Menus[$menu_id]['config']['menu_parent_filename']) ? $Menus[$menu_id]['config']['menu_parent_filename'] : ''
        ));
    }
}

$image_url = MENU_imageUrl();
'''
if old not in s:
    raise SystemExit('preview CSS block not found')
s = s.replace(old, new, 1)
p.write_text(s)

# Vertical cascading templates consume a complete validated background fragment.
for fn in ['templates/gl_vertical-cascading.thtml', 'templates/default/gl_vertical-cascading.thtml']:
    p = Path(fn)
    s = p.read_text()
    s = s.replace('url({site_url}/images/menu/{menu_parent_filename})', '{menu_parent_background}')
    p.write_text(s)

# Validate posted legacy color settings before persistence and before admin inline CSS.
p = Path('admin/index.php')
s = p.read_text()
needle = "    $mc['use_images']                 = (int) Geeklog\\Input::fPost('gorc', 0);\n"
insert = '''    $mc['use_images']                 = (int) Geeklog\\Input::fPost('gorc', 0);

    $legacyColorKeys = array(
        'main_menu_bg_color', 'main_menu_hover_bg_color',
        'main_menu_text_color', 'main_menu_hover_text_color',
        'submenu_text_color', 'submenu_hover_text_color',
        'submenu_background_color', 'submenu_hover_bg_color',
        'submenu_highlight_color', 'submenu_shadow_color',
    );
    foreach ($legacyColorKeys as $colorKey) {
        $mc[$colorKey] = MENU_cssColor($mc[$colorKey]);
    }
    $mc['menu_alignment'] = ($mc['menu_alignment'] === 1) ? 1 : 0;
    $mc['use_images'] = ($mc['use_images'] === 1) ? 1 : 0;
'''
if needle not in s:
    raise SystemExit('admin save config insertion point missing')
s = s.replace(needle, insert, 1)

# Stored values shown by the admin editor are sanitized too.
needle = "    $use_colors_checked = ($menuConfig['use_images'] == 0 ? ' checked=\"checked\"' : '');\n"
insert = '''    $use_colors_checked = ($menuConfig['use_images'] == 0 ? ' checked="checked"' : '');

    $legacyColorKeys = array(
        'main_menu_bg_color', 'main_menu_hover_bg_color',
        'main_menu_text_color', 'main_menu_hover_text_color',
        'submenu_text_color', 'submenu_hover_text_color',
        'submenu_background_color', 'submenu_hover_bg_color',
        'submenu_highlight_color', 'submenu_shadow_color',
    );
    foreach ($legacyColorKeys as $colorKey) {
        $menuConfig[$colorKey] = MENU_cssColor($menuConfig[$colorKey]);
    }
'''
if needle not in s:
    raise SystemExit('admin display config insertion point missing')
s = s.replace(needle, insert, 1)
p.write_text(s)

# Roadmap.
p = Path('ROADMAP.md')
s = p.read_text()
s = s.replace(
    'legacy presentation-image uploads are centralized, content-validated and site-specific; broader output/CSS review remains in progress.',
    'legacy presentation-image uploads are centralized, content-validated and site-specific; legacy CSS colors and image URLs are now allow-listed before rendering.'
)
p.write_text(s)
