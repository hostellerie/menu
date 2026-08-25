from pathlib import Path

index_path = Path('admin/index.php')
test_path = Path('tests/admin_controller_contract.php')

index = index_path.read_text()

start = index.find('function MENU_hexrgb(')
if start >= 0:
    comment_start = index.rfind('\n\n', 0, start)
    end = index.find('\n}\n', start)
    if end < 0:
        raise SystemExit('MENU_hexrgb end not found')
    end += 3
    if comment_start < 0:
        comment_start = start
    index = index[:comment_start] + '\n\n' + index[end:].lstrip('\n')

old = "            $currentSelect = $LANG_MENU01['configuration'];\n            $currentSelect = $LANG_MENU01['menu_builder'];\n"
new = "            $currentSelect = $LANG_MENU01['menu_builder'];\n"
if old in index:
    index = index.replace(old, new, 1)

index_path.write_text(index)

test_path.write_text(r'''<?php

$index = file_get_contents(dirname(__DIR__) . '/admin/index.php');

$forbidden = array(
    'function MENU_',
    'DB_query(',
    'DB_save(',
    'DB_delete(',
    'DB_insertId(',
);
foreach ($forbidden as $needle) {
    if (strpos($index, $needle) !== false) {
        fwrite(STDERR, "Admin controller still contains non-routing logic: {$needle}\n");
        exit(1);
    }
}

$requiredModules = array(
    'admin_menu_views.php',
    'admin_menu_mutations.php',
    'admin_element_views.php',
);
foreach ($requiredModules as $module) {
    if (strpos($index, $module) === false) {
        fwrite(STDERR, "Admin controller missing module: {$module}\n");
        exit(1);
    }
}

if (substr_count($index, "$currentSelect = $LANG_MENU01['configuration'];") > 0) {
    fwrite(STDERR, "Obsolete overwritten configuration selection remains\n");
    exit(1);
}

echo "Admin controller contract tests passed\n";
''')
