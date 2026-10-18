#!/usr/bin/env python3
"""
Fill the site's [PLACEHOLDERS] from placeholders.json and remove the draft price notes.

    python3 tools/fill-placeholders.py            # fill values, keep draft notes
    python3 tools/fill-placeholders.py --final    # also remove every <span class="draft-note">

Run it from the site folder (the one with index.html). Keep a copy of the unfilled site first:
it edits the files in place. Empty values in placeholders.json are left untouched.
The builder's own files (builder/) are never changed.
"""
import json, pathlib, re, sys

root = pathlib.Path(__file__).resolve().parent.parent
# Placeholders that may be filled with nothing (the sentence reads fine without them).
BLANK_OK = {'[MONTHLY HOURS TERMS]'}
values = {}
for group, items in json.loads((root / 'placeholders.json').read_text(encoding='utf-8')).items():
    if isinstance(items, dict):
        values.update({k: v for k, v in items.items() if v or k in BLANK_OK})

final = '--final' in sys.argv
files = [p for p in root.rglob('*') if p.suffix in {'.html', '.js', '.md', '.txt', '.xml', '.toml', '.php'}
         and 'builder' not in p.parts and p.name not in {'fill-placeholders.py', 'LAUNCH-NOTES.md'}]
changed = 0
for path in files:
    text = path.read_text(encoding='utf-8')
    new = text
    if '[PHONE]' in values:
        # tel: links need the number without spaces
        new = new.replace('tel:[PHONE]', 'tel:' + re.sub(r'[^\d+]', '', values['[PHONE]']))
    for key, value in values.items():
        new = new.replace(key, value)
    if final:
        new = re.sub(r'\s*<span class="draft-note">.*?</span>', '', new)
        new = re.sub(r'<p[^>]*>\s*</p>', '', new)
    if new != text:
        path.write_text(new, encoding='utf-8')
        changed += 1

left = set()
for path in files:
    left |= set(re.findall(r"\[(?:£ )?[A-Z][A-Z0-9 /:&'.,-]{2,}\]", path.read_text(encoding='utf-8')))
left -= {'[SQUARE BRACKETS]', '[MLHVQCZ]'}
print(f'Updated {changed} files.')
if left:
    print(f'{len(left)} placeholders still to fill:')
    try:
        for k in sorted(left):
            print('  ', k)
    except BrokenPipeError:
        pass
