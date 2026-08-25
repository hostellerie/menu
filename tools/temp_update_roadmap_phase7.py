from pathlib import Path

path = Path('ROADMAP.md')
text = path.read_text()

replacements = {
    '- native drag ordering with keyboard move controls;': '- stable TableDnD 0.6 drag ordering loaded through Geeklog\'s jQuery stack, with keyboard move controls;',
    '1. Continue Phase 7 code decomposition, especially `admin/index.php` and legacy rendering responsibilities.': '1. Finish Phase 7 legacy asset/browser-specific cleanup and remaining contextual escaping review.',
    '- [ ] Continue reducing `admin/index.php` into focused components.': '- [x] Reduce `admin/index.php` to routing/page composition only, with mutations and view builders extracted.',
    '- [ ] Audit/remove unused `tableDnD` generations and other dead administration assets.': '- [x] Remove obsolete TableDnD generations and retain only `tablednd_0_6.js` as the tested compatibility implementation.\n- [ ] Audit/remove other dead administration assets.',
    '- [x] Modernize drag ordering without relying on legacy `tableDnD` behavior.': '- [x] Stabilize drag ordering on the retained TableDnD 0.6 compatibility layer, loaded after Geeklog-managed jQuery.',
    '- [ ] Review SlickNav version/dependency strategy.': '- [ ] Review SlickNav version/dependency strategy.\n- [x] Remove the dead `createElementID()` / `SELECT MAX(id)+1` generator and enforce `AUTO_INCREMENT` creation paths.\n- [x] Reject hierarchy cycles server-side and filter descendants from the edit-parent selector.',
}

for old, new in replacements.items():
    if old not in text:
        raise SystemExit('roadmap anchor not found: ' + old)
    text = text.replace(old, new, 1)

path.write_text(text)
