import * as THREE from 'three';

/**
 * Plate font: glyph outlines in millimetres, produced by tools/build-font.py.
 * Each glyph's ink box starts at x = 0 and sits on the baseline (y-up).
 */
export class PlateFont {
	constructor(data) {
		this.name = data.name;
		this.capHeight = data.capHeight;
		this.ccw = Boolean(data.ccw);
		this.glyphs = data.glyphs;
		this.shapeCache = new Map();
	}

	has(char) {
		return char in this.glyphs;
	}

	/** Ink width of a character in mm. */
	width(char) {
		return this.glyphs[char]?.w ?? 0;
	}

	/** THREE.Shape[] for a character (holes resolved), cached. */
	shapes(char) {
		let shapes = this.shapeCache.get(char);
		if (!shapes) {
			const glyph = this.glyphs[char];
			// TrueType outer contours run clockwise, OTF/CFF counter-clockwise; tell toShapes which is solid.
			shapes = glyph ? parsePath(glyph.d).toShapes(this.ccw) : [];
			this.shapeCache.set(char, shapes);
		}
		return shapes;
	}
}

const cache = new Map();

export function loadPlateFont(url) {
	if (!cache.has(url)) {
		cache.set(url, fetch(url).then((r) => {
			if (!r.ok) throw new Error(`Font ${url}: HTTP ${r.status}`);
			return r.json();
		}).then((data) => new PlateFont(data)));
	}
	return cache.get(url);
}

/** Minimal SVG path parser for the commands SVGPathPen emits (absolute M L H V Q C Z). */
function parsePath(d) {
	const path = new THREE.ShapePath();
	const tokens = d.match(/[MLHVQCZ]|-?\d*\.?\d+(?:e-?\d+)?/gi) ?? [];
	let i = 0;
	let cmd = '';
	let x = 0;
	let y = 0;
	const num = () => parseFloat(tokens[i++]);

	while (i < tokens.length) {
		if (/[A-Z]/i.test(tokens[i])) cmd = tokens[i++];
		switch (cmd) {
			case 'M':
				path.moveTo((x = num()), (y = num()));
				cmd = 'L'; // further pairs after M are implicit line-tos
				break;
			case 'L':
				path.lineTo((x = num()), (y = num()));
				break;
			case 'H':
				path.lineTo((x = num()), y);
				break;
			case 'V':
				path.lineTo(x, (y = num()));
				break;
			case 'Q': {
				const cx = num();
				const cy = num();
				path.quadraticCurveTo(cx, cy, (x = num()), (y = num()));
				break;
			}
			case 'C': {
				const c1x = num(), c1y = num(), c2x = num(), c2y = num();
				path.bezierCurveTo(c1x, c1y, c2x, c2y, (x = num()), (y = num()));
				break;
			}
			case 'Z':
				break;
			default:
				i++; // unknown token; skip rather than loop forever
		}
	}
	return path;
}
