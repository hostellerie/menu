<?php

define('VERSION', '2.1.1');

function DB_escapeString($value) { return $value; }

require_once dirname(__DIR__) . '/compat.php';

function menu_label_test_assert($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
}

menu_label_test_assert(
    MENU_escapeStoredText('<span class="uk-text-danger">reCAPTCHA</span>') === 'reCAPTCHA',
    'raw Geeklog warning markup must be removed'
);

menu_label_test_assert(
    MENU_escapeStoredText('&lt;span class=&quot;uk-text-danger&quot;&gt;reCAPTCHA&lt;/span&gt;') === 'reCAPTCHA',
    'encoded Geeklog warning markup must be removed'
);

menu_label_test_assert(
    MENU_escapeStoredText('&amp;lt;strong&amp;gt;Hello&amp;lt;/strong&amp;gt;') === 'Hello',
    'double-encoded plugin markup must be removed'
);

menu_label_test_assert(
    MENU_escapeStoredText('Fish &amp; Chips') === 'Fish &amp; Chips',
    'ordinary encoded text must remain safely escaped'
);

echo "Menu label normalization tests passed" . PHP_EOL;
