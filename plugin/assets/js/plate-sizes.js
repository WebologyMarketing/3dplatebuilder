import * as THREE from 'three';

/**
 * Plate sizes in mm. Fields:
 *   width      number, or 'auto' = cut to the shortest legal length for the registration
 *   height     mm
 *   shape      'rect' (rounded corners) | 'hex' (pointed ends, `chamfer` = horizontal length of each point,
 *              `point` = how far down from the top edge the points sit, as a fraction of the height)
 *   lines      1, or 2 = one group per line
 *   chars      key into CHARACTER_SPECS ('standard' 79 mm | 'motorcycle' 64 mm)
 *   margin     override the spec's minimum margin (only where the size demands it)
 *   rearOnly   only offered as a rear plate (the front can be a different size)
 *   single     a one-plate vehicle (motorbike, trailer): no front plate, sold on its own
 *   face       'front' (white) | 'rear' (yellow): fixes the face colour (commercial trailers are white at the rear)
 *   styles     character styles offered on this size (null = all)
 *   onCar      false = no "On a car" preview (motorbike and trailer plates)
 */
// For now these sizes are printed only; 3D gel and 4D are coming.
const PRINTED_ONLY = ['printed'];

export const PLATE_SIZES = [
	{ id: 'standard', label: 'Standard', width: 520, height: 111 },
	{ id: 'short', label: 'Short', width: 'auto', height: 111, description: 'Cut to fit your registration.' },
	{ id: 'hex', label: 'Hex', width: 'auto', height: 111, shape: 'hex', chamfer: 28, point: 0.26, description: 'Pointed ends, cut to fit your registration.' },
	{ id: 'square', label: '4x4', width: 279, height: 203, lines: 2, rearOnly: true },
	{ id: 'oversized', label: 'Oversized', width: 533, height: 152, rearOnly: true },
	{ id: 'motorbike', label: 'Motorbike', width: 229, height: 178, lines: 2, chars: 'motorcycle', rearOnly: true, single: true, styles: PRINTED_ONLY, onCar: false },
	// Non-commercial trailer: the towing vehicle's registration on a standard yellow plate.
	{ id: 'trailer', label: 'Trailer', width: 520, height: 111, rearOnly: true, single: true, styles: PRINTED_ONLY, onCar: false, description: 'Your towing vehicle\'s registration.' },
	// Commercial trailers (over 750 kg, or travelling to Europe): white reflective, motorbike size and font.
	{ id: 'trailer-commercial', label: 'Commercial trailer', width: 229, height: 178, lines: 2, chars: 'motorcycle', rearOnly: true, single: true, face: 'front', styles: PRINTED_ONLY, onCar: false, description: 'Over 750 kg or travelling to Europe.' },
	{ id: 'japanese-import', label: 'Japanese import', width: 330, height: 165, lines: 2, chars: 'motorcycle', styles: PRINTED_ONLY },
	// 64 mm two-line characters need 163 mm with 11 mm margins; a 152 mm US recess only allows 5.5 mm.
	{ id: 'us-import', label: 'US import', width: 305, height: 152, lines: 2, chars: 'motorcycle', margin: 5.5, styles: PRINTED_ONLY },
].map((s) => ({ shape: 'rect', radius: 6, lines: 1, chars: 'standard', rearOnly: false, single: false, face: null, styles: null, ...s }));

export const getSize = (id) => PLATE_SIZES.find((s) => s.id === id) ?? PLATE_SIZES[0];

/** Is this character style offered on this size? */
export const styleAvailable = (size, styleId) => !size.styles || size.styles.includes(styleId);

/** The side a one-plate size is locked to (its face colour), or null if the customer can choose. */
export const fixedSide = (size) => (size.single ? size.face ?? 'rear' : null);

/* ------------------------------------------------------------------ outlines */

function roundedRect(width, height, radius) {
	const x = -width / 2;
	const y = -height / 2;
	const r = Math.max(0.5, Math.min(radius, width / 2, height / 2));
	const s = new THREE.Shape();
	s.moveTo(x + r, y);
	s.lineTo(x + width - r, y);
	s.absarc(x + width - r, y + r, r, -Math.PI / 2, 0, false);
	s.lineTo(x + width, y + height - r);
	s.absarc(x + width - r, y + height - r, r, 0, Math.PI / 2, false);
	s.lineTo(x + r, y + height);
	s.absarc(x + r, y + height - r, r, Math.PI / 2, Math.PI, false);
	s.lineTo(x, y + r);
	s.absarc(x + r, y + r, r, Math.PI, Math.PI * 1.5, false);
	return s;
}

/** Move every edge of a convex, counter-clockwise polygon inwards by d. */
function insetPolygon(points, d) {
	const n = points.length;
	const lines = points.map((p, i) => {
		const q = points[(i + 1) % n];
		const dx = q.x - p.x;
		const dy = q.y - p.y;
		const len = Math.hypot(dx, dy);
		const nx = -dy / len; // inward normal for CCW winding
		const ny = dx / len;
		return { p: new THREE.Vector2(p.x + nx * d, p.y + ny * d), dir: new THREE.Vector2(dx, dy) };
	});
	return lines.map((a, i) => {
		const b = lines[(i + n - 1) % n]; // previous edge
		const cross = b.dir.x * a.dir.y - b.dir.y * a.dir.x;
		const t = ((a.p.x - b.p.x) * a.dir.y - (a.p.y - b.p.y) * a.dir.x) / cross;
		return new THREE.Vector2(b.p.x + b.dir.x * t, b.p.y + b.dir.y * t);
	});
}

/** Height of a hex plate's points above the centre line (PlateBoss cuts them above the middle). */
const hexPointY = (plate) => plate.height / 2 - plate.height * (plate.point ?? 0.5);

function hexPoints(width, height, chamfer, py) {
	const w = width / 2;
	const h = height / 2;
	return [
		new THREE.Vector2(-w, py),
		new THREE.Vector2(-w + chamfer, -h),
		new THREE.Vector2(w - chamfer, -h),
		new THREE.Vector2(w, py),
		new THREE.Vector2(w - chamfer, h),
		new THREE.Vector2(-w + chamfer, h),
	];
}

/**
 * The plate outline as a THREE.Shape, optionally inset (for bevels and borders).
 * @param {{width:number,height:number,shape:string,radius:number,chamfer?:number}} plate resolved size
 */
export function plateShape(plate, inset = 0) {
	if (plate.shape === 'hex') {
		const pts = hexPoints(plate.width, plate.height, plate.chamfer, hexPointY(plate));
		return new THREE.Shape(inset ? insetPolygon(pts, inset) : pts);
	}
	return roundedRect(plate.width - 2 * inset, plate.height - 2 * inset, plate.radius - inset);
}

/** Clip a convex outline to points where p[axis] <= max (Sutherland–Hodgman), e.g. for the EV strip. */
export function clipMax(points, axis, max) {
	const other = axis === 'x' ? 'y' : 'x';
	const out = [];
	for (let i = 0; i < points.length; i++) {
		const a = points[i];
		const b = points[(i + 1) % points.length];
		const aIn = a[axis] <= max;
		const bIn = b[axis] <= max;
		if (aIn) out.push(a);
		if (aIn !== bIn) {
			const t = (max - a[axis]) / (b[axis] - a[axis]);
			const p = new THREE.Vector2();
			p[axis] = max;
			p[other] = a[other] + (b[other] - a[other]) * t;
			out.push(p);
		}
	}
	return out;
}

/** Half-width of the plate at height y (hex plates narrow towards their points). */
export function halfWidthAt(plate, y) {
	const w = plate.width / 2;
	if (plate.shape !== 'hex') return w;
	const h = plate.height / 2;
	const py = hexPointY(plate);
	const t = y >= py ? (y - py) / (h - py) : (py - y) / (py + h);
	return w - plate.chamfer * Math.min(1, t);
}

/** Where the badge zone starts: hex plates push it in past the pointed end. */
export const badgeOffset = (plate) => (plate.shape === 'hex' ? plate.chamfer * 0.75 : 0);
