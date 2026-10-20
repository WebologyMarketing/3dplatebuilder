# Notes for Claude working in this repo

- UK English in all user-facing copy, plain and trade-friendly. Homee Ltd is not VAT registered yet: never say "prices exclude VAT" or add VAT; say there's no VAT to add.
- Never claim DVLA approval or certification. The builder helps with BS AU 145e layout; the plate maker stays responsible.
- No phone numbers or call links anywhere: support is by email and (later) portal tickets.
- `theme/3dpb-shop/` is the customer shop theme (child of Twenty Twenty-Five); keep it free of paid plugins and page builders.
- `plugin/` is the master copy of the builder. White-label through config and filters; keep the option ids in `includes/catalog.php` and the JS data files in step.
- `site/` pages are generated: edit `site-src/`, run `python3 site-src/build.py`, then `python3 site/tools/fill-placeholders.py --final`. Don't hand-edit the generated HTML.
- Three.js stays pinned to r170 (0.170.0) from jsDelivr until it's vendored.
- `portal/` is the Laravel customer area and admin (app.3dplatebuilder.com). Keys and passwords live only in `portal/.env` on the server. Run `php artisan test` in `portal/` before a PR. See `portal/README.md`.
- Commit on a branch, open a PR to `main`; never push secrets.
- Deploys (Plesk Git, both from GitHub `WebologyMarketing/3dplatebuilder`):
  - `staging` branch → staging.3dplatebuilder.com automatically on every push (webhook): `site/` is copied to the `site` folder and `plugin/` to the test shop's `wp-content/plugins/3d-plate-builder`. Merge work branches into `staging` to preview them.
  - `main` → 3dplatebuilder.com (`httpdocs`), deployed by hand with Deploy now after the PR is merged. Never deploy unmerged work to live.
