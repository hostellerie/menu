<?php
function assertTrue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

define('VERSION', '2.2.2');
function DB_escapeString($value) { return str_replace("'", "''", $value); }
require dirname(__DIR__) . '/compat.php';

assertTrue(MENU_dbEscape("O'Reilly") === "O''Reilly", 'SQL escape helper');
assertTrue(MENU_escapeHTML('"<script>') === '&quot;&lt;script&gt;', 'HTML text/attribute escape');
assertTrue(MENU_escapeStoredText('&lt;b&gt;x&lt;/b&gt;') === '&lt;b&gt;x&lt;/b&gt;', 'legacy stored label normalization');
assertTrue(MENU_safeHref('javascript:alert(1)') === '#', 'javascript URL blocked');
assertTrue(MENU_safeHref(' data:text/html,x') === '#', 'data URL blocked');
assertTrue(MENU_safeHref('https://example.com/?a=1&b=2') === 'https://example.com/?a=1&amp;b=2', 'normal URL escaped');

$class = file_get_contents(dirname(__DIR__) . '/classes/classMenuElement.php');
$admin = file_get_contents(dirname(__DIR__) . '/admin/index.php');
$functions = file_get_contents(dirname(__DIR__) . '/functions.inc');

assertTrue(strpos($class, '$label    = MENU_dbEscape($this->label);') !== false, 'element label escaped at DB sink');
assertTrue(strpos($class, 'MENU_safeHref($this->url)') !== false, 'legacy href uses safe URL helper');
assertTrue(strpos($class, 'strip_tags($this->label)') === false, 'legacy label output no longer uses strip_tags alone');
assertTrue(strpos($admin, 'WHERE id=$id AND menu_id=$menu_id') !== false, 'element update scoped to menu');
assertTrue(strpos($admin, "preg_match('/^mid_([1-9][0-9]*)$/', $rowId") !== false, 'drag IDs validated');
assertTrue(strpos($functions, '$menuIDSql = MENU_dbEscape($menuID);') !== false, 'autotag menu name escaped');

echo "SQL/XSS contract: OK\n";
