<?php

$root = dirname(__DIR__);
$clone = file_get_contents($root . '/admin/clone.php');
$template = file_get_contents($root . '/templates/default/clonemenu.thtml');
$security = file_get_contents($root . '/admin_security.php');

$failures = array();

if (strpos($template, 'action="{site_admin_url}/plugins/menu/clone.php"') === false) {
    $failures[] = 'Clone form must use dedicated admin/clone.php endpoint.';
}

if (strpos($clone, 'DB_insertId()') === false) {
    $failures[] = 'Clone endpoint must use database auto-increment IDs.';
}

if (strpos($clone, 'SELECT MAX(id)') !== false || strpos($clone, 'createElementID') !== false) {
    $failures[] = 'Clone endpoint must not generate element IDs with MAX(id)+1.';
}

if (strpos($clone, '$idMap[$oldId] = $newId;') === false
    || strpos($clone, '$newPid = ($oldPid > 0 && isset($idMap[$oldPid]))') === false) {
    $failures[] = 'Clone endpoint must remap parent IDs after element insertion.';
}

if (strpos($clone, "WHERE menu_id=" . '$sourceMenuId' . " ORDER BY id ASC") === false) {
    $failures[] = 'Clone source elements must use deterministic ordering.';
}

if (strpos($clone, 'SEC_hasRights(\'menu.admin\')') === false
    || strpos($clone, 'SEC_checkToken()') === false
    || strpos($clone, "REQUEST_METHOD") === false) {
    $failures[] = 'Clone endpoint must enforce authorization, POST and CSRF.';
}

if (strpos($security, "if ($mode === 'saveclonemenu')") === false) {
    $failures[] = 'Legacy saveclonemenu controller path must be retired.';
}

if (!empty($failures)) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo "Menu clone persistence contract OK\n";
