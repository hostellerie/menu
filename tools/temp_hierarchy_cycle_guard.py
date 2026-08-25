from pathlib import Path

validation = Path('admin_element_validation.php')
views = Path('admin_element_views.php')

v = validation.read_text()
anchor = "function MENU_adminMutationReferenceError($mode, $post)\n{"
helper = r'''/**
 * Return all descendant element IDs for one element in a menu.
 * The hierarchy is loaded in one query and walked in memory. A visited set
 * prevents malformed pre-existing data from causing an infinite loop.
 *
 * @param int $menuId
 * @param int $elementId
 * @return array
 */
function MENU_adminDescendantIds($menuId, $elementId)
{
    global $_TABLES;

    $menuId = (int) $menuId;
    $elementId = (int) $elementId;
    if ($menuId <= 0 || $elementId <= 0 || !isset($_TABLES['menu_elements'])) {
        return array();
    }

    $childrenByParent = array();
    $result = DB_query(
        'SELECT id,pid FROM ' . $_TABLES['menu_elements']
        . ' WHERE menu_id=' . $menuId
    );
    while ($row = DB_fetchArray($result)) {
        $id = (int) $row['id'];
        $pid = (int) $row['pid'];
        if (!isset($childrenByParent[$pid])) {
            $childrenByParent[$pid] = array();
        }
        $childrenByParent[$pid][] = $id;
    }

    $descendants = array();
    $seen = array($elementId => true);
    $queue = isset($childrenByParent[$elementId])
        ? $childrenByParent[$elementId]
        : array();

    while (!empty($queue)) {
        $id = (int) array_shift($queue);
        if ($id <= 0 || isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $descendants[] = $id;
        if (isset($childrenByParent[$id])) {
            foreach ($childrenByParent[$id] as $childId) {
                if (!isset($seen[(int) $childId])) {
                    $queue[] = (int) $childId;
                }
            }
        }
    }

    return $descendants;
}

'''
if 'function MENU_adminDescendantIds(' not in v:
    if anchor not in v:
        raise SystemExit('validation insertion anchor not found')
    v = v.replace(anchor, helper + anchor, 1)

old = "    if ($pid < 0 || ($mid > 0 && $pid === $mid)) {\n        return 'Invalid parent menu element.';\n    }\n    if ($pid > 0) {"
new = "    if ($pid < 0 || ($mid > 0 && $pid === $mid)) {\n        return 'Invalid parent menu element.';\n    }\n    if ($mid > 0 && $pid > 0\n        && in_array($pid, MENU_adminDescendantIds($menuId, $mid), true)) {\n        return 'A menu element cannot use one of its descendants as parent.';\n    }\n    if ($pid > 0) {"
if old not in v:
    raise SystemExit('parent validation block not found')
v = v.replace(old, new, 1)
validation.write_text(v)

s = views.read_text()
start = s.find('function MENU_editElement')
end = s.find('function MENU_menuConfig', start)
if start < 0 or end < 0:
    raise SystemExit('editElement function boundaries not found')
segment = s[start:end]
parent_pos = segment.find("$parent_select = '<select")
if parent_pos < 0:
    raise SystemExit('edit parent select not found')
segment = segment[:parent_pos] + "    $blockedParentIds = array_flip(MENU_adminDescendantIds($menu_id, $mid));\n" + segment[parent_pos:]
while_pos = segment.find('while (', parent_pos)
brace_pos = segment.find('{', while_pos)
if while_pos < 0 or brace_pos < 0:
    raise SystemExit('edit parent loop not found')
row_guard = "\n        if ((int) $row['id'] === (int) $mid || isset($blockedParentIds[(int) $row['id']])) {\n            continue;\n        }"
segment = segment[:brace_pos + 1] + row_guard + segment[brace_pos + 1:]
s = s[:start] + segment + s[end:]
views.write_text(s)

contract = Path('tests/hierarchy_cycle_contract.php')
contract.write_text(r'''<?php

$root = dirname(__DIR__);
$validation = file_get_contents($root . '/admin_element_validation.php');
$views = file_get_contents($root . '/admin_element_views.php');

$requiredValidation = array(
    'function MENU_adminDescendantIds(',
    "'SELECT id,pid FROM '",
    'array_shift($queue)',
    'in_array($pid, MENU_adminDescendantIds($menuId, $mid), true)',
    'cannot use one of its descendants as parent',
);
foreach ($requiredValidation as $needle) {
    if (strpos($validation, $needle) === false) {
        fwrite(STDERR, "Missing hierarchy cycle guard: {$needle}\n");
        exit(1);
    }
}

$editStart = strpos($views, 'function MENU_editElement');
$editEnd = strpos($views, 'function MENU_menuConfig', $editStart);
$editBody = substr($views, $editStart, $editEnd - $editStart);
if (strpos($editBody, 'array_flip(MENU_adminDescendantIds($menu_id, $mid))') === false
    || strpos($editBody, 'isset($blockedParentIds[(int) $row[\'id\']])') === false) {
    fwrite(STDERR, "Edit parent selector does not filter descendants\n");
    exit(1);
}

echo "Hierarchy cycle contract tests passed\n";
''')
