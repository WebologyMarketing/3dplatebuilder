"""
Convert a plate font (TTF/OTF) into compact glyph outlines in millimetres.

    python3 tools/build-font.py CharlesWright-Bold.ttf plugin/assets/fonts/charles-wright-bold.json --cap-height 79

Only A-Z and 0-9 are exported: that is all a registration needs, it keeps the
payload tiny (no runtime font parser), and the original font file is never
published on the website.

Requires: pip install fonttools
"""

import argparse
import json

from fontTools.pens.boundsPen import BoundsPen
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.ttLib import TTFont

CHARSET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"


def fmt(v):
    return f"{v:.2f}".rstrip("0").rstrip(".")


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("font")
    ap.add_argument("out")
    ap.add_argument("--cap-height", type=float, required=True, help="height of 'H' in mm")
    ap.add_argument("--name", default=None)
    args = ap.parse_args()

    font = TTFont(args.font)
    glyph_set = font.getGlyphSet()
    cmap = font.getBestCmap()

    h = BoundsPen(glyph_set)
    glyph_set[cmap[ord("H")]].draw(h)
    scale = args.cap_height / (h.bounds[3] - h.bounds[1])

    glyphs = {}
    for ch in CHARSET:
        name = cmap.get(ord(ch))
        if name is None:
            raise SystemExit(f"Font has no glyph for {ch!r}")

        bp = BoundsPen(glyph_set)
        glyph_set[name].draw(bp)
        x0, y0, x1, y1 = (v * scale for v in bp.bounds)

        # Normalise so the ink box starts at x = 0; y stays on the baseline (y-up).
        svg = SVGPathPen(glyph_set, ntos=fmt)
        glyph_set[name].draw(TransformPen(svg, (scale, 0, 0, scale, -x0, 0)))

        glyphs[ch] = {
            "w": round(x1 - x0, 2),
            "y0": round(y0, 2),
            "y1": round(y1, 2),
            "d": svg.getCommands(),
        }

    data = {
        "name": args.name or font["name"].getDebugName(4),
        "units": "mm",
        "capHeight": args.cap_height,
        # PostScript (CFF/OTF) outlines run counter-clockwise; TrueType runs clockwise.
        "ccw": "CFF " in font or "CFF2" in font,
        "glyphs": glyphs,
    }
    with open(args.out, "w") as f:
        json.dump(data, f, separators=(",", ":"))
    print(f"Wrote {len(glyphs)} glyphs to {args.out} (scale {scale:.5f} mm/unit)")


if __name__ == "__main__":
    main()
