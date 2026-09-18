<?php

// Resolved tree public contract tests. Compatible with PHP 5.6+.

$source = file_get_contents(dirname(__DIR__) . '/resolved_tree.php');
$doc = file_get_contents(dirname(__DIR__) . '/docs/resolved-tree-contract.md');

if ($source === false || $doc === false) {
    fwrite(STDERR, "FAIL: unable to read resolved-tree contract sources\n");
    exit(1);
}

$requiredSource = array(
    "define('MENU_RESOLVED_TREE_CONTRACT_VERSION', 1)",
    'function MENU_getResolvedTreeContractVersion()',
    "'id' => (int) \$element->id",
    "'parent_id' => (int) \$element->pid",
    "'label' => strip_tags((string) \$element->label)",
    "'type' => \$type",
    "'subtype' => \$subtype",
    "'url' => \$url",
    "'target' => (string) \$element->target",
    "'rel' => MENU_resolvedLinkRel",
    "'aria_label' => MENU_runtimeConfigEnabled",
    "'active' => true",
    "'selected' => MENU_resolvedNodeSelected",
    "'resolved' => \$resolved",
    "'children' => \$children",
);

foreach ($requiredSource as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, 'FAIL: resolved tree v1 source contract missing: ' . $needle . PHP_EOL);
        exit(1);
    }
}

$visibilityGuards = array(
    "empty(\$menu['active'])",
    "(int) \$menu['menu_perm'] !== 3",
    "(int) \$element->active !== 1",
    "(int) \$element->access <= 0",
    "draft_flag=0",
    "COM_getPermSQL('AND')",
    "COM_getPermSql('AND')",
    "if (!\$allowed)",
);

foreach ($visibilityGuards as $needle) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, 'FAIL: resolved tree permission guard missing: ' . $needle . PHP_EOL);
        exit(1);
    }
}

$requiredDocs = array(
    'Contract version: **1**',
    'permission-filtered for the current Geeklog request context',
    'inactive menus return an empty tree',
    'group-restricted elements are omitted',
    'Consumers must ignore unknown fields',
    'must perform their own `menu.admin`',
    'must never bypass Menu by querying or mutating',
    'The callback itself is not exposed as an arbitrary executable capability',
);

foreach ($requiredDocs as $needle) {
    if (strpos($doc, $needle) === false) {
        fwrite(STDERR, 'FAIL: resolved tree documentation contract missing: ' . $needle . PHP_EOL);
        exit(1);
    }
}

echo "Resolved tree public contract tests passed\n";
