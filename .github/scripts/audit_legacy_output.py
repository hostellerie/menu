from pathlib import Path
import re

patterns = [
    re.compile(r"\$Menus\[[^\n]+\]\['config'\]\[[^\n]+\]"),
    re.compile(r"menuConfig\[[^\]]+\]"),
    re.compile(r"background(?:-image)?\s*[:=]", re.I),
    re.compile(r"url\s*\(", re.I),
    re.compile(r"style=", re.I),
]

for p in Path('.').rglob('*'):
    if not p.is_file() or '.git' in p.parts or p.suffix.lower() not in {'.php','.inc','.thtml','.css'}:
        continue
    try:
        lines = p.read_text(errors='ignore').splitlines()
    except Exception:
        continue
    for i, line in enumerate(lines, 1):
        if any(rx.search(line) for rx in patterns):
            print(f"{p}:{i}:{line}")
