# Notes for Claude working in this repo

- UK English in all user-facing copy, plain and trade-friendly. Homee Ltd is not VAT registered yet: never say "prices exclude VAT" or add VAT; say there's no VAT to add.
- Never claim DVLA approval or certification. The builder helps with BS AU 145e layout; the plate maker stays responsible.
- No phone numbers or call links anywhere: support is by email and (later) portal tickets.
- `plugin/` is the master copy of the builder. White-label through config and filters; keep the option ids in `includes/catalog.php` and the JS data files in step.
- `site/` pages are generated: edit `site-src/`, run `python3 site-src/build.py`, then `python3 site/tools/fill-placeholders.py --final`. Don't hand-edit the generated HTML.
- Three.js stays pinned to r170 (0.170.0) from jsDelivr until it's vendored.
- Commit on a branch, open a PR to `main`; never push secrets.
