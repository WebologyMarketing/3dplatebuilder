# 3D Plate Builder

Shops with a real-time 3D number plate builder, for UK registered number plate suppliers.
Built by, and running at, PlateBoss.co.uk. Private repository.

The build plan (customer journey, portal, provisioning, phases) is in the "3D Plate Builder: Build Plan" doc.

## What's here

| Folder | What it is | Status |
|---|---|---|
| `site/` | Marketing site for 3dplatebuilder.com (static HTML, plus `contact.php` for the demo form) | Built, not yet live |
| `site-src/` | Python scripts that generate the pages in `site/` | Working |
| `plugin/` | The builder WordPress/WooCommerce plugin (from kit 1.9.19, unchanged) | To be white-labelled in Phase 1 |
| `plugin-dev/` | Local test pages for the builder without WordPress | Working |
| `extras/` | Small checkout plugins used at PlateBoss (notices, express pay) | As in the kit |
| `tools/` | Local test server and the font-to-JSON tool | As in the kit |
| `docs/` | The kit's brief | Reference |

Coming in later phases: `shop-template/` (theme, must-use plugins, provisioning scripts) and `portal/` (Laravel customer portal and admin).

## Working locally

Builder test page: from the repo root run `python3 tools/nocache_server.py`, then open http://127.0.0.1:8765/plugin-dev/.

Marketing site: `cd site && python3 -m http.server 8765`, then open http://127.0.0.1:8765/. It runs the live demos through `phone.html` and `desktop.html`.

To rebuild the site's pages after changing `site-src/`:

```bash
python3 site-src/build.py                      # regenerates site/*.html with [PLACEHOLDERS]
python3 site/tools/fill-placeholders.py --final # fills them from site/placeholders.json
```

`site/plateboss-builder/` is the demo copy of the builder's assets. Once the plugin is white-labelled, it will be copied from `plugin/assets/` by a build step instead of kept by hand.

## Rules

- `main` is what's live. Work on a branch and open a pull request.
- No passwords, API keys or customer data in the repository. Secrets go in GitHub's encrypted secrets.
- Before launch, see `site/LAUNCH-NOTES.md` for open licence checks (Charles Wright font data, Nikkalite backing print).
