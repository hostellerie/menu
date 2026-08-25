from pathlib import Path

# Admin controller: load helper, use color defaults, centralize uploads.
p = Path('admin/index.php')
s = p.read_text()
needle = "require_once $_CONF['path'].'system/lib-admin.php';\n"
if "plugins/menu/image_upload.php" not in s:
    s = s.replace(needle, needle + "require_once $_CONF['path'].'plugins/menu/image_upload.php';\n", 1)

# Legacy image presentation is opt-in for new/reset menus. Do not reference
# assets that are not shipped with the plugin.
s = s.replace("'use_images','1'", "'use_images','0'")
s = s.replace("'menu_bg_filename','menu_bg.gif'", "'menu_bg_filename',''")
s = s.replace("'menu_hover_filename','menu_hover_bg.gif'", "'menu_hover_filename',''")
s = s.replace("'menu_parent_filename','menu_parent.png'", "'menu_parent_filename',''")
s = s.replace("'menu_parent_filename','vmenu_parent.gif'", "'menu_parent_filename',''")

start = s.find("    $file = array();\n    $file = $_FILES['bgimg'];", s.find('function MENU_saveMenuConfig'))
end = s.find("    MENU_CACHE_remove_instance('menu');", start)
if start == -1 or end == -1:
    raise SystemExit('legacy upload block not found')

replacement = '''    // Optional images belong only to the legacy renderer. Validate them from
    // their actual file contents and store them in the site-specific Geeklog
    // image directory. Existing images are preserved when no valid replacement
    // is uploaded.
    $imageUploads = array(
        'bgimg' => array('prefix' => 'menu_bg', 'config' => 'menu_bg_filename'),
        'hvimg' => array('prefix' => 'menu_hover_bg', 'config' => 'menu_hover_filename'),
        'piimg' => array('prefix' => 'menu_parent', 'config' => 'menu_parent_filename'),
    );

    foreach ($imageUploads as $field => $settings) {
        $file = isset($_FILES[$field]) ? $_FILES[$field] : array();
        $configName = $settings['config'];
        $oldFilename = isset($Menus[$menu_id]['config'][$configName])
            ? $Menus[$menu_id]['config'][$configName]
            : '';
        $newFilename = MENU_storeLegacyImageUpload($file, $settings['prefix'], $oldFilename);

        if ($newFilename !== '') {
            $configNameSql = MENU_dbEscape($configName);
            $newFilenameSql = MENU_dbEscape($newFilename);
            DB_save(
                $_TABLES['menu_config'],
                'menu_id,conf_name,conf_value',
                "$menu_id,'$configNameSql','$newFilenameSql'"
            );
        }
    }
'''
s = s[:start] + replacement + s[end:]
p.write_text(s)

# Fresh install defaults: colors are safe and complete; legacy image mode is
# opt-in because historical image files are not shipped in the package.
p = Path('sql/mysql_install.php')
s = p.read_text()
s = s.replace("'use_images', '1'", "'use_images', '0'")
s = s.replace("'menu_bg_filename', 'menu_bg.gif'", "'menu_bg_filename', ''")
s = s.replace("'menu_hover_filename', 'menu_hover_bg.gif'", "'menu_hover_filename', ''")
s = s.replace("'menu_parent_filename', 'menu_parent.png'", "'menu_parent_filename', ''")
s = s.replace("'menu_parent_filename', 'vmenu_parent.gif'", "'menu_parent_filename', ''")
p.write_text(s)

# Track actual Phase 2 state.
p = Path('ROADMAP.md')
s = p.read_text()
s = s.replace(
    '- Phase 2 output/upload safety: not yet fully audited.',
    '- Phase 2 upload safety: legacy presentation-image uploads are centralized, content-validated and site-specific; broader output/CSS review remains in progress.'
)
for old, new in [
    ('- [ ] Validate uploads with `is_uploaded_file()`.', '- [x] Validate uploads with `is_uploaded_file()`.'),
    ('- [ ] Detect MIME type server-side.', '- [x] Detect image type server-side from image contents.'),
    ('- [ ] Keep a strict PNG/GIF/JPEG allow-list unless formats are intentionally expanded.', '- [x] Keep a strict PNG/GIF/JPEG allow-list unless formats are intentionally expanded.'),
    ('- [ ] Validate image contents and dimensions.', '- [x] Validate image contents and dimensions.'),
    ('- [ ] Add file-size and dimension limits.', '- [x] Add file-size and dimension limits.'),
    ('- [ ] Generate server-side filenames.', '- [x] Generate server-side filenames.'),
    ('- [ ] Fix inconsistent old-image deletion paths.', '- [x] Fix inconsistent old-image deletion paths.'),
]:
    s = s.replace(old, new)
p.write_text(s)
