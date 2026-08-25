from pathlib import Path

class_path = Path('classes/classMenuElement.php')
test_path = Path('tests/legacy_id_generation_contract.php')

text = class_path.read_text()
start = text.find('    function createElementID( $menu_id ) {')
if start < 0:
    raise SystemExit('createElementID definition not found')
end = text.find('\n    }', start)
if end < 0:
    raise SystemExit('createElementID end not found')
end += len('\n    }')
text = text[:start] + text[end:]
class_path.write_text(text)

test_path.write_text(r'''<?php

$root = dirname(__DIR__);
$extensions = array('php', 'inc');
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }
    if (strpos($file->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) !== false
        || strpos($file->getPathname(), DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR) !== false) {
        continue;
    }
    $extension = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
    if (!in_array($extension, $extensions, true)) {
        continue;
    }
    $content = file_get_contents($file->getPathname());
    if (strpos($content, 'createElementID(') !== false
        || stripos($content, 'SELECT MAX(id)') !== false) {
        fwrite(STDERR, "Legacy manual ID generation remains in " . $file->getPathname() . "\n");
        exit(1);
    }
}

echo "Legacy ID generation cleanup tests passed\n";
''')

# Trigger the temporary cleanup workflow on an existing workflow definition.
