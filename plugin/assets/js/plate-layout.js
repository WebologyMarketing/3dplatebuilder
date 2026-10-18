/**
 * Registration formatting and legal character layout (DVLA / BS AU 145e).
 * Pure functions apart from the size helpers, so they can be unit-tested and mirrored in PHP.
 *
 * Spacing is measured ink-to-ink, exactly as the regulations define it, rather
 * than relying on the font's sidebearings or space glyph.
 */
import { badgeOffset, halfWidthAt } from './plate-sizes.js';

/** Character specs in mm. `font` is a key into the builder's fonts config. */
export const CHARACTER_SPECS = {
	standard: { font: 'standard', charHeight: 79, charWidth: 50, stroke: 14, charGap: 11, groupGap: 33, lineGap: 19, margin: 11 },
	// Motorbikes, imports and commercial trailers (Charles Wright Singapore).
	motorcycle: { font: 'motorcycle', charHeight: 64, charWidth: 44, stroke: 10, charGap: 10, groupGap: 30, lineGap: 13, margin: 11 },
};

export const MAX_CHARACTERS = 7;

/**
 * Current-style UK registration patterns. When the customer types without a
 * space we place it where the DVLA would; anything else is left as typed.
 */
const AUTO_SPACE = [
	/^([A-Z]{2}\d{2})([A-Z]{3})$/, // current:  AB12 CDE
	/^([A-Z]\d{1,3})([A-Z]{3})$/, // prefix:   A123 BCD
	/^([A-Z]{3})(\d{1,3}[A-Z])$/, // suffix:   ABC 123D
];

/**
 * Clean raw input: uppercase, A-Z/0-9 only, at most one space, at most 7 characters.
 * Returns the value to keep in the input box.
 */
export function sanitiseInput(raw) {
	let out = '';
	let chars = 0;
	for (const c of String(raw).toUpperCase().replace(/\s+/g, ' ').replace(/^ /, '')) {
		if (c === ' ') {
			if (!out.includes(' ')) out += c;
		} else if (/[A-Z0-9]/.test(c) && chars < MAX_CHARACTERS) {
			out += c;
			chars++;
		}
	}
	return out;
}

/** The registration as it will appear on the plate (auto-spaced, trimmed). */
export function formatRegistration(input) {
	const clean = sanitiseInput(input).trim();
	if (clean.includes(' ')) return clean;
	for (const re of AUTO_SPACE) {
		const m = clean.match(re);
		if (m) return `${m[1]} ${m[2]}`;
	}
	return clean;
}

/** Lay out one line; x from 0, ink-to-ink spacing. */
function layoutLine(font, text, spec, sx, sy) {
	const glyphs = [];
	let x = 0;
	let gap = 0;
	for (const char of text) {
		if (char === ' ') {
			if (glyphs.length) gap = spec.groupGap;
			continue;
		}
		if (!font.has(char)) continue;
		x += gap;
		const width = font.width(char) * sx;
		glyphs.push({ char, x, width, scaleX: sx, scaleY: sy });
		x += width;
		gap = spec.charGap;
	}
	return { glyphs, width: x };
}

/**
 * Position each character, centred on (0, 0). Two-line plates put one group per line.
 *
 * @param {import('./plate-font.js').PlateFont} font
 * @param {string} registration  already formatted (a space marks the group break)
 * @param {object} spec          entry from CHARACTER_SPECS
 * @param {number} lines         1 or 2
 */
export function layoutRegistration(font, registration, spec = CHARACTER_SPECS.standard, lines = 1) {
	// Scale the font to the spec's box. For a font built at the spec size both are 1.
	const sy = spec.charHeight / font.capHeight;
	const sx = spec.charWidth / font.width('H');

	// Two-line plates break at the space; without one (e.g. a commercial trailer number) split it evenly.
	const texts = lines === 1 || registration.length < 4 ? [registration]
		: registration.includes(' ') ? registration.split(' ')
		: [registration.slice(0, Math.ceil(registration.length / 2)), registration.slice(Math.ceil(registration.length / 2))];
	const rows = texts.map((t) => layoutLine(font, t, spec, sx, sy));

	const width = Math.max(0, ...rows.map((r) => r.width));
	const height = rows.length * spec.charHeight + (rows.length - 1) * spec.lineGap;

	const glyphs = [];
	rows.forEach((row, i) => {
		const top = height / 2 - i * (spec.charHeight + spec.lineGap);
		for (const g of row.glyphs) {
			glyphs.push({ ...g, x: g.x - row.width / 2, y: top - spec.charHeight, height: spec.charHeight, line: i });
		}
	});

	return { registration, glyphs, width, height, lines: rows.length, rows: rows.map((r) => ({ width: r.width })), spec };
}

/**
 * Fit a layout onto a plate. Resolves 'auto' (short) widths, keeps legal margins,
 * leaves the badge zone clear, and centres each line in the space that's left.
 * On two-line plates the badge sits beside the bottom line only.
 *
 * @param layout   from layoutRegistration
 * @param size     entry from PLATE_SIZES
 * @param zone     width of the left badge / EV strip zone (0 if none)
 * @returns {{ plate, glyphs, fits, needWidth, badgeBand }}
 */
export function placeOnPlate(layout, size, zone = 0) {
	const m = size.margin ?? layout.spec.margin;
	const plate = { ...size };
	const last = layout.lines - 1;
	const zoned = (line) => zone > 0 && (layout.lines === 1 || line === last);

	// Hex plates narrow towards the points: the characters' top and bottom corners must clear both slopes.
	const taperFor = (p) => p.width / 2 - Math.min(halfWidthAt(p, layout.height / 2), halfWidthAt(p, -layout.height / 2));

	if (size.width === 'auto') {
		// Cut to the registration; hex plates add the pointed ends (and push the badge in past the left one).
		const taper = taperFor({ ...plate, width: 0 });
		plate.width = Math.ceil(Math.max(...layout.rows.map((r, i) => r.width + 2 * m + (zoned(i) ? taper + badgeOffset(plate) + zone : 2 * taper))));
	}

	const taper = taperFor(plate);
	const side = plate.width / 2 - taper;
	const areaFor = (line) => ({
		x0: zoned(line) ? -plate.width / 2 + badgeOffset(plate) + zone + m : -side + m,
		x1: side - m,
	});

	const glyphs = layout.glyphs.map((g) => {
		const a = areaFor(g.line);
		return { ...g, x: g.x + (a.x0 + a.x1) / 2 };
	});

	const fitsWidth = layout.rows.every((r, i) => {
		const a = areaFor(i);
		return r.width <= a.x1 - a.x0 + 1e-6;
	});
	const fits = fitsWidth && layout.height <= plate.height - 2 * m + 1e-6;

	// Shortest plate that would take this registration (for the error message).
	const needWidth = Math.ceil(Math.max(...layout.rows.map((r, i) => r.width + 2 * m + 2 * taper + (zoned(i) ? badgeOffset(plate) + zone : 0))));

	// Vertical band the badge should centre on: the bottom line on two-line plates, else the whole plate.
	const bottom = glyphs.find((g) => g.line === last);
	const badgeBand = layout.lines === 2 && bottom
		? { y: bottom.y + layout.spec.charHeight / 2, top: bottom.y + layout.spec.charHeight + layout.spec.lineGap / 2 }
		: null;

	return { plate, glyphs, fits, needWidth, badgeBand };
}
