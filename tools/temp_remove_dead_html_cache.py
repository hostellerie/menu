from pathlib import Path

p = Path('functions.inc')
s = p.read_text()

start = s.find("function MENU_getMenu(")
end = s.find("\nfunction phpblock_getMenu", start)
if start < 0 or end < 0:
    raise SystemExit('MENU_getMenu block not found')
block = s[start:end]

block = block.replace("""    $optionHash = md5($wrapper.$ulclass.$liclass.$parentclass.$lastclass.$selected);

    $menuID = '';
""", """    $retval = '';
    $menuID = '';
""", 1)

block = block.replace("""        $mlname = $name . '_'.$lang;
        $cacheInstance = 'menu_' . $mlname . '_' . MENU_CACHE_security_hash() . '_' . $optionHash . '__' . $_CONF['theme'];
        $retval = MENU_CACHE_check_instance($cacheInstance, 0);
        if ($retval && $noid == 0) {
            return $retval;
        }
        $retval = '';
""", """        $mlname = $name . '_'.$lang;
""", 1)

block = block.replace("""    if ($menuID == '') {
        if (!empty($retval) && $noid == 0) {
            return $retval;
        }
        $retval = '';
        $menuID = '';
""", """    if ($menuID == '') {
        $menuID = '';
""", 1)

s = s[:start] + block + s[end:]

hash_start = s.find("function MENU_CACHE_security_hash()")
if hash_start >= 0:
    hash_end = s.find("\nfunction MENU_compress", hash_start)
    if hash_end < 0:
        raise SystemExit('security hash end not found')
    s = s[:hash_start] + s[hash_end + 1:]

if 'MENU_CACHE_security_hash' in s:
    raise SystemExit('security hash reference remains')

p.write_text(s)
