<?php

$root = dirname(__DIR__);
$create = file_get_contents($root . '/admin/create_element.php');
$template = file_get_contents($root . '/templates/default/createelement.thtml');
$security = file_get_contents($root . '/admin_security.php');

$failures = array();

if (strpos($template, 'action="{site_admin_url}/plugins/menu/create_element.php"') === false) {
    $failures[] = 'Create-element form must use dedicated endpoint.';
}

if (strpos($create, 'DB_insertId()') === false) {
    $failures[] = 'New element persistence must use database auto-increment IDs.';
}

if (strpos($create, 'SELECT MAX(id)') !== false || strpos($create, 'createElementID') !== false) {
    $failures[] = 'New element endpoint must not generate IDs with MAX(id)+1.';
}

if (strpos($create, 'MENU_adminElementMutationError(\'save\', $_POST)') === false) {
    $failures[] = 'New element endpoint must reuse server-side element validation.';
}

if (strpos($create, 'ORDER BY element_order ASC, id ASC') === false
    || strpos($create, 'SET element_order=') === false) {
    $failures[] = 'Sibling ordering must be normalized after insertion.';
}

if (strpos($create, 'SEC_hasRights(\'menu.admin\')') === false
    || strpos($create, 'SEC_checkToken()') === false
    || strpos($create, 'REQUEST_METHOD') === false) {
    $failures[] = 'New element endpoint must enforce authorization, POST and CSRF.';
}

if (strpos($security, '$mode === \'save\' || $mode === \'saveclonemenu\'') === false) {
    $failures[] = 'Legacy create/clone controller persistence paths must be blocked.';
}

if (!empty($failures)) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo "Menu create-element persistence contract OK\n";
