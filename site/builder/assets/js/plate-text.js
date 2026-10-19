import * as THREE from 'three';
import { mergeVertices } from 'three/addons/utils/BufferGeometryUtils.js';
import { FACE_Z } from './plate-model.js';
import { getAccent, getStyle } from './plate-styles.js';

const GLIDE_MS = 240; // characters slide to their new centred position
const POP_MS = 260; // new, changed or restyled characters settle in (raised ones grow up)
const PRINT_Z = 0.03; // flat ink sits just above the face
const EDGE = 0.25; // micro-bevel on laser-cut edges so they catch the light
const CURVE_SEGMENTS = 10;
const GHOST_LAYER = { kind: 'print', finish: 'ghost' };

const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
const clamp01 = (t) => Math.min(1, Math.max(0, t));

const CHAMFER_TRI = 0.5; // smallest triangle edge along fold lines, mm (finer = crisper creases)
const CHAMFER_TOLERANCE = 0.04; // max deviation from the true chamfer surface, mm

/**
 * Pressed/machined chamfer (Retro): height comes from each point's distance to the
 * nearest edge of the letter, rising from a short vertical wall to a narrow ridge.
 * Unlike an offset-based bevel this never self-intersects at sharp corners
 * (M, N, A, V…), and where strokes meet the faces fold along clean diagonal creases.
 *
 * @param layer.height  total height, mm
 * @param layer.inset   distance from the edge at which the slope reaches the top (≈ half the stroke
 *                      minus half the ridge width: 14 mm strokes with inset 6 leave a 2 mm ridge)
 * @param layer.wall    height of the vertical edge at the base, mm
 */
function buildChamferGeometry(shapes, { height, inset, wall = 0.8 }) {
	// Outline segments, oriented so the solid is on the left (outward normal to the right).
	const contours = [];
	for (const shape of shapes) {
		const outer = shape.getPoints(CURVE_SEGMENTS);
		if (THREE.ShapeUtils.isClockWise(outer)) outer.reverse();
		contours.push(outer);
		for (const hole of shape.holes) {
			const pts = hole.getPoints(CURVE_SEGMENTS);
			if (!THREE.ShapeUtils.isClockWise(pts)) pts.reverse();
			contours.push(pts);
		}
	}
	const segs = [];
	for (const c of contours) {
		for (let i = 0; i < c.length; i++) {
			const a = c[i];
			const b = c[(i + 1) % c.length];
			if (a.distanceToSquared(b) > 1e-10) segs.push(a.x, a.y, b.x, b.y);
		}
	}

	// Only distances up to `inset` matter (beyond that the surface is flat), so bucket the
	// segments into an inset-sized grid and check just the 3×3 cells around each point.
	const cell = inset;
	const grid = new Map();
	const cellKey = (cx, cy) => `${cx},${cy}`;
	for (let i = 0; i < segs.length; i += 4) {
		const x0 = Math.floor(Math.min(segs[i], segs[i + 2]) / cell);
		const x1 = Math.floor(Math.max(segs[i], segs[i + 2]) / cell);
		const y0 = Math.floor(Math.min(segs[i + 1], segs[i + 3]) / cell);
		const y1 = Math.floor(Math.max(segs[i + 1], segs[i + 3]) / cell);
		for (let cx = x0; cx <= x1; cx++) {
			for (let cy = y0; cy <= y1; cy++) {
				const k = cellKey(cx, cy);
				if (!grid.has(k)) grid.set(k, []);
				grid.get(k).push(i);
			}
		}
	}

	// Distance to the nearest edge, and the direction away from it (the way the slope rises).
	const away = { x: 0, y: 0 };
	const distance = (x, y) => {
		let best = inset * inset;
		away.x = 0;
		away.y = 0;
		const gx = Math.floor(x / cell);
		const gy = Math.floor(y / cell);
		for (let cx = gx - 1; cx <= gx + 1; cx++) for (let cy = gy - 1; cy <= gy + 1; cy++) for (const i of grid.get(cellKey(cx, cy)) ?? []) {
			const ax = segs[i], ay = segs[i + 1], dx = segs[i + 2] - ax, dy = segs[i + 3] - ay;
			const t = Math.max(0, Math.min(1, ((x - ax) * dx + (y - ay) * dy) / (dx * dx + dy * dy)));
			const ex = ax + t * dx - x, ey = ay + t * dy - y;
			const d = ex * ex + ey * ey;
			if (d < best) {
				best = d;
				away.x = -ex;
				away.y = -ey;
			}
		}
		return Math.sqrt(best);
	};
	const zCache = new Map();
	const zAt = (x, y) => {
		const key = Math.round(x * 1e4) * 1e7 + Math.round(y * 1e4); // numeric key: glyph coords stay well under 1000 mm
		let z = zCache.get(key);
		if (z === undefined) {
			z = wall + ((height - wall) * Math.min(distance(x, y), inset)) / inset;
			zCache.set(key, z);
		}
		return z;
	};

	const out = [];
	const vert = (x, y, z) => out.push(x, y, z);

	// Top: triangulate the flat outline, split triangles until they follow the surface,
	// then lift each vertex to its chamfer height.
	const base = new THREE.ShapeGeometry(shapes, CURVE_SEGMENTS);
	const bp = base.attributes.position;
	const idx = base.index.array;
	const stack = [];
	for (let i = 0; i < idx.length; i += 3) {
		stack.push([bp.getX(idx[i]), bp.getY(idx[i]), bp.getX(idx[i + 1]), bp.getY(idx[i + 1]), bp.getX(idx[i + 2]), bp.getY(idx[i + 2])]);
	}
	// Adaptive: only split where the surface bends (ridge and fold lines). A triangle whose edge
	// midpoints and centre already lie on the true surface stays big, so flat facets cost almost nothing.
	const min2 = CHAMFER_TRI * CHAMFER_TRI;
	const flatEnough = (ax, ay, bx, by, cx, cy) => {
		const za = zAt(ax, ay), zb = zAt(bx, by), zc = zAt(cx, cy);
		const off = (x, y, zLinear) => Math.abs(zAt(x, y) - zLinear) > CHAMFER_TOLERANCE;
		return !(
			off((ax + bx) / 2, (ay + by) / 2, (za + zb) / 2)
			|| off((bx + cx) / 2, (by + cy) / 2, (zb + zc) / 2)
			|| off((cx + ax) / 2, (cy + ay) / 2, (zc + za) / 2)
			|| off((ax + bx + cx) / 3, (ay + by + cy) / 3, (za + zb + zc) / 3)
		);
	};
	while (stack.length) {
		const [ax, ay, bx, by, cx, cy] = stack.pop();
		const ab = (bx - ax) ** 2 + (by - ay) ** 2;
		const bc = (cx - bx) ** 2 + (cy - by) ** 2;
		const ca = (ax - cx) ** 2 + (ay - cy) ** 2;
		const longest = Math.max(ab, bc, ca);
		if (longest <= min2 || flatEnough(ax, ay, bx, by, cx, cy)) {
			vert(ax, ay, zAt(ax, ay));
			vert(bx, by, zAt(bx, by));
			vert(cx, cy, zAt(cx, cy));
		} else if (longest === ab) {
			const mx = (ax + bx) / 2, my = (ay + by) / 2;
			stack.push([ax, ay, mx, my, cx, cy], [mx, my, bx, by, cx, cy]);
		} else if (longest === bc) {
			const mx = (bx + cx) / 2, my = (by + cy) / 2;
			stack.push([ax, ay, bx, by, mx, my], [ax, ay, mx, my, cx, cy]);
		} else {
			const mx = (cx + ax) / 2, my = (cy + ay) / 2;
			stack.push([ax, ay, bx, by, mx, my], [mx, my, bx, by, cx, cy]);
		}
	}

	const topCount = out.length / 3;

	// Black floor just under the faces: hides hairline gaps where neighbouring
	// triangles were split a different number of times.
	for (let i = 0; i < idx.length; i++) vert(bp.getX(idx[i]), bp.getY(idx[i]), wall - 0.02);
	base.dispose();

	// Vertical wall around the base, facing outwards.
	for (const c of contours) {
		for (let i = 0; i < c.length; i++) {
			const a = c[i];
			const b = c[(i + 1) % c.length];
			vert(a.x, a.y, 0); vert(b.x, b.y, 0); vert(b.x, b.y, wall);
			vert(a.x, a.y, 0); vert(b.x, b.y, wall); vert(a.x, a.y, wall);
		}
	}

	const geo = new THREE.BufferGeometry();
	geo.setAttribute('position', new THREE.Float32BufferAttribute(out, 3));
	geo.computeVertexNormals(); // flat per triangle: right for the walls and the hidden floor

	// The top is many small triangles; flat normals make each one catch the light on its own
	// (shards in the reflections). Use the true slope at every vertex instead: the faces shade
	// smoothly and the creases stay where the faces meet.
	// Triangles that straddle a fold (where two faces meet) would blend the two faces' normals and
	// show zigzags along the crease: those get the one normal of the face their centre lies on.
	const slope = (height - wall) / inset;
	const normals = geo.attributes.normal;
	const normalAt = (x, y, target) => {
		const d = distance(x, y);
		if (d >= inset || d < 1e-6) return target.set(0, 0, 1);
		return target.set((-slope * away.x) / d, (-slope * away.y) / d, 1).normalize();
	};
	const na = new THREE.Vector3(), nb = new THREE.Vector3(), nc = new THREE.Vector3();
	for (let t = 0; t < topCount; t += 3) {
		const ax = out[t * 3], ay = out[t * 3 + 1];
		const bx = out[t * 3 + 3], by = out[t * 3 + 4];
		const cx = out[t * 3 + 6], cy = out[t * 3 + 7];
		normalAt(ax, ay, na);
		normalAt(bx, by, nb);
		normalAt(cx, cy, nc);
		if (Math.min(na.dot(nb), nb.dot(nc), nc.dot(na)) < 0.995) {
			normalAt((ax + bx + cx) / 3, (ay + by + cy) / 3, na);
			nb.copy(na);
			nc.copy(na);
		}
		normals.setXYZ(t, na.x, na.y, na.z);
		normals.setXYZ(t + 1, nb.x, nb.y, nb.z);
		normals.setXYZ(t + 2, nc.x, nc.y, nc.z);
	}
	return geo;
}

/**
 * Build one layer of one character. Geometry is in mm, ink box at x = 0,
 * baseline at y = 0, base of the layer at z = 0.
 */
function buildLayerGeometry(shapes, layer) {
	let geo;

	if (layer.kind === 'print') {
		geo = new THREE.ShapeGeometry(shapes, CURVE_SEGMENTS);
		geo.translate(0, 0, PRINT_Z);
	} else if (layer.profile === 'pyramid') {
		geo = buildChamferGeometry(shapes, layer);
		if (layer.z) geo.translate(0, 0, layer.z);
	} else {
		const grow = layer.grow ?? 0;
		let opts;
		if (layer.profile === 'dome') {
			// Tall, many-segment bevel pulled in from the outline gives the rounded resin dome.
			const bt = layer.height * 0.9;
			opts = { depth: layer.height - bt, bevelThickness: bt, bevelSize: layer.inset, bevelOffset: -layer.inset + grow, bevelSegments: 7 };
		} else {
			opts = { depth: layer.height - EDGE, bevelThickness: EDGE, bevelSize: EDGE, bevelOffset: -EDGE + grow, bevelSegments: 2 };
		}
		geo = new THREE.ExtrudeGeometry(shapes, { ...opts, bevelEnabled: true, curveSegments: CURVE_SEGMENTS });

		// ExtrudeGeometry bevels both ends. Flatten the back one (z < 0) onto the base so
		// the outline at the face is exactly the legal glyph and nothing pokes through the plate.
		const pos = geo.attributes.position;
		for (let i = 0; i < pos.count; i++) {
			if (pos.getZ(i) < 0) pos.setZ(i, 0);
		}

		if (layer.profile === 'dome') {
			// Smooth shading across the whole dome (ExtrudeGeometry is faceted by default).
			geo.deleteAttribute('normal');
			geo.deleteAttribute('uv');
			geo = mergeVertices(geo, 1e-3);
		}
		geo.computeVertexNormals();
		if (layer.z) geo.translate(0, 0, layer.z);
	}

	if (layer.shift) geo.translate(layer.shift[0], layer.shift[1], 0);
	return geo;
}

/**
 * The registration characters. Each character slot is a group of layer meshes;
 * slots are reused between keystrokes and geometry is cached per (layer, char),
 * so typing and style switching only build what has never been built before.
 */
export class PlateText {
	constructor(font, { reducedMotion = false, style = 'printed', accent } = {}) {
		this.font = font;
		this.reducedMotion = reducedMotion;
		this.group = new THREE.Group();
		this.group.name = 'Characters';
		this.group.position.z = FACE_Z;

		this.geometries = new Map();
		this.layerKeys = new WeakMap();
		this.items = [];
		this.glideStart = 0;

		this.#createMaterials();
		this.style = getStyle(style);
		this.setAccent(accent ?? this.style.accent);
	}

	#createMaterials() {
		const black = '#0B0B0B';
		const printed = { polygonOffset: true, polygonOffsetFactor: -2, polygonOffsetUnits: -2 };
		const underAcrylic = { clearcoat: 1, clearcoatRoughness: 0, envMapIntensity: 0.6 };

		this.materials = {
			ink: new THREE.MeshPhysicalMaterial({ color: '#111111', roughness: 0.45, ...underAcrylic, ...printed }),
			ghost: new THREE.MeshPhysicalMaterial({ color: '#111111', roughness: 0.45, ...underAcrylic, ...printed, transparent: true, opacity: 0.14, depthWrite: false }),
			gel: new THREE.MeshPhysicalMaterial({ color: black, roughness: 0.05, clearcoat: 1, clearcoatRoughness: 0, envMapIntensity: 2.2 }),
			// Polished cast acrylic: mirror-smooth, so reflections stay crisp rather than satin.
			acrylic: new THREE.MeshPhysicalMaterial({ color: black, roughness: 0.04, clearcoat: 1, clearcoatRoughness: 0, envMapIntensity: 1.6 }),
			accent: new THREE.MeshPhysicalMaterial({ roughness: 0.3, clearcoat: 0.6, clearcoatRoughness: 0.05, envMapIntensity: 1 }),
			accentPrint: new THREE.MeshPhysicalMaterial({ roughness: 0.45, ...underAcrylic, ...printed }),
		};
	}

	#material(layer) {
		if (layer.finish === 'accent') return layer.kind === 'print' ? this.materials.accentPrint : this.materials.accent;
		return this.materials[layer.finish] ?? this.materials.ink;
	}

	#geometry(char, layer) {
		let key = this.layerKeys.get(layer);
		if (!key) this.layerKeys.set(layer, (key = JSON.stringify(layer)));
		key += `${this.font.name}|${char}`;

		let geo = this.geometries.get(key);
		if (!geo) {
			geo = buildLayerGeometry(this.font.shapes(char), layer);
			this.geometries.set(key, geo);
		}
		return geo;
	}

	#buildSlot(item, now) {
		item.group.clear();
		const layers = this.ghost ? [GHOST_LAYER] : this.style.layers;
		for (const layer of layers) {
			const mesh = new THREE.Mesh(this.#geometry(item.char, layer), this.#material(layer));
			mesh.castShadow = layer.kind === 'solid';
			item.group.add(mesh);
		}
		item.popStart = now;
	}

	setStyle(id) {
		const style = getStyle(id);
		if (style === this.style) return;
		this.style = style;
		const now = performance.now();
		this.items.forEach((item) => this.#buildSlot(item, now));
		this.update(now);
	}

	setAccent(id) {
		const accent = getAccent(id);
		this.accent = accent;
		for (const m of [this.materials.accent, this.materials.accentPrint]) {
			m.color.set(accent.colour);
			m.metalness = accent.metal && m === this.materials.accent ? 0.9 : 0;
		}
		this.materials.accent.roughness = accent.metal ? 0.22 : 0.3;
	}

	/**
	 * @param {{glyphs: object[]}} layout  placed glyphs (plate-layout.js placeOnPlate)
	 * @param {{ghost?: boolean}} opts      ghost = placeholder text shown faintly
	 */
	setLayout(layout, { ghost = false, font = this.font } = {}) {
		const now = performance.now();
		const ghostChanged = this.ghost !== ghost;
		const fontChanged = this.font !== font;
		this.ghost = ghost;
		this.font = font;

		layout.glyphs.forEach((g, i) => {
			let item = this.items[i];
			if (!item) {
				const group = new THREE.Group();
				group.name = `Char${i}`;
				this.group.add(group);
				item = this.items[i] = { group, char: g.char, x: g.x };
				this.#buildSlot(item, now);
			} else if (item.char !== g.char || ghostChanged || fontChanged) {
				item.char = g.char;
				this.#buildSlot(item, now);
			}
			// Don't glide between placeholder and real text, or between fonts.
			item.fromX = ghostChanged || fontChanged ? g.x : item.x;
			item.toX = g.x;
			item.y = g.y;
			item.width = g.width;
			item.height = g.height;
			item.scaleX = g.scaleX;
			item.scaleY = g.scaleY;
		});

		for (const item of this.items.splice(layout.glyphs.length)) {
			this.group.remove(item.group);
		}

		this.glideStart = now;
		this.update(now);
	}

	/** Advance animations. Returns true while anything is still moving. */
	update(now) {
		const glide = this.reducedMotion ? 1 : clamp01((now - this.glideStart) / GLIDE_MS);
		const e = easeOutCubic(glide);
		let active = glide < 1;

		for (const item of this.items) {
			item.x = item.fromX + (item.toX - item.fromX) * e;

			const pop = this.reducedMotion ? 1 : clamp01((now - item.popStart) / POP_MS);
			if (pop < 1) active = true;
			const p = easeOutCubic(pop);
			const k = 0.86 + 0.14 * p;

			// Scale about the glyph's centre, not its baseline corner; raised layers grow up off the face.
			item.group.scale.set(item.scaleX * k, item.scaleY * k, 0.05 + 0.95 * p);
			item.group.position.set(item.x + (item.width * (1 - k)) / 2, item.y + (item.height * (1 - k)) / 2, 0);
		}
		return active;
	}

	dispose() {
		this.geometries.forEach((g) => g.dispose());
		Object.values(this.materials).forEach((m) => m.dispose());
	}
}
