<?php

// Core Stats action visibility contract. Compatible with PHP 5.6+.

$source = file_get_contents(dirname(__DIR__) . '/runtime_loader.php');
if ($source === false) {
    fwrite(STDERR, "FAIL: unable to read runtime_loader.php\n");
    exit(1);
}

$checks = array(
    'MENU_coreStatsActionAllowed' => 'missing core Stats access helper',
    'statsloginrequired' => 'Stats action must honor statsloginrequired',
    'loginrequired' => 'Stats action must honor global loginrequired',
    '$elementType === 2 && $elementSubtype === 5' => 'Stats Geeklog action subtype is not normalized',
    "$row['element_type'] = 6;" => 'Stats action type is not converted to URL mode',
    "'/stats.php'" => 'Stats action URL is missing',
);

foreach ($checks as $needle => $message) {
    if (strpos($source, $needle) === false) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

if (strpos($source, 'if (!COM_isAnonUser())') === false) {
    fwrite(STDERR, "FAIL: registered users must be allowed to access core Stats\n");
    exit(1);
}

if (strpos($source, "isset($row['element_type'])") === false
    || strpos($source, "isset($row['element_subtype'])") === false) {
    fwrite(STDERR, "FAIL: Stats normalization must tolerate incomplete legacy rows\n");
    exit(1);
}

echo "Stats action visibility contract passed\n";
