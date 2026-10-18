import * as THREE from 'three';
import { mergeVertices } from 'three/addons/utils/BufferGeometryUtils.js';
import { BADGE, BORDER, GEL, badgeWidth, badgeZone, gelWidth, getBadge, getBorder, loadFlagImage } from './plate-badges.js';
import { badgeOffset, clipMax, plateShape } from './plate-sizes.js';

/**
 * Physical plate model. 1 scene unit = 1 mm.
 *
 * Construction mirrors a real plate: a clear acrylic body with polished,
 * bevelled edges, a reflective face (white front / yellow rear) under a
 * glossy clearcoat, a matte backing, and printed extras (UK badge, EV strip,
 * border) between the face and the acrylic, with clear resin domes for gel badges.
 */

export const FACE_COLOURS = {
	front: '#F4F4F0', // reflective white
	rear: '#FFD200', // reflective yellow
};

export const THICKNESS = 3.2; // acrylic sheet, mm
const EDGE_BEVEL = 0.35; // polished edge radius, mm
const EDGE_TINT = '#9FB0AC'; // clear acrylic edge picks up a cool green-grey cast
const MAX_TEXTURE_PX = 4096; // mobile GPU limit
const TEXTURE_PX_PER_MM = 7;
const BADGE_PX_PER_MM = 12;
const FACE_OFFSET = 0.02; // lift face above the cap to avoid z-fighting
const LOGO_MAX_HEIGHT = 8; // supplier logo height when there's room, mm
const FOOTER_GAP = 1.2; // clearance above a border and below the characters, mm
export const FACE_Z = THICKNESS / 2 + FACE_OFFSET; // front surface; characters build up from here

/** Printed layers stack just above the face: EV strip, then border, then badge. */
const PRINT_Z = { ev: 0.01, border: 0.015, badge: 0.02 };

/**
 * Canvas texture for the legal footer. Drawn on white and tinted by the face
 * material, so swapping front/rear is a cheap colour change.
 */
class FaceArtwork {
	constructor(plate) {
		this.plate = plate;
		this.pxPerMm = Math.min(TEXTURE_PX_PER_MM, MAX_TEXTURE_PX / Math.max(plate.width, plate.height));
		this.canvas = document.createElement('canvas');
		this.canvas.width = Math.round(plate.width * this.pxPerMm);
		this.canvas.height = Math.round(plate.height * this.pxPerMm);
		this.ctx = this.canvas.getContext('2d');

		this.texture = new THREE.CanvasTexture(this.canvas);
		this.texture.colorSpace = THREE.SRGBColorSpace;
		this.texture.minFilter = THREE.LinearMipmapLinearFilter;
	}

	px(mm) {
		return mm * this.pxPerMm;
	}

	/**
	 * Legal footer: supplier logo + postcode (centre) and the British Standard mark (right),
	 * in the bottom margin.
	 *
	 * @param footer  { supplier, postcode, standard }
	 * @param logo    loaded <img> of the supplier logo, or null to fall back to text
	 * @param box     { bottom, top } mm from the plate's bottom edge: above any border, below the characters
	 */
	draw({ supplier = '', postcode = '', standard = 'BS AU 145e' }, logo, box) {
		const { ctx, canvas, plate } = this;
		ctx.fillStyle = '#FFFFFF';
		ctx.fillRect(0, 0, canvas.width, canvas.height);
		ctx.fillStyle = '#1A1A1A';
		ctx.textBaseline = 'alphabetic';

		const y = (mm) => canvas.height - this.px(mm); // mm above the bottom edge -> canvas y
		const height = Math.max(3.5, Math.min(LOGO_MAX_HEIGHT, box.top - box.bottom));
		const textSize = Math.max(2.4, height * 0.36);
		const font = (mm) => `600 ${this.px(mm)}px "Arial Narrow", Arial, Helvetica, sans-serif`;
		const textBaseline = y(box.bottom + height * 0.22);

		// Logo (or name) followed by the postcode, centred as one group.
		ctx.font = font(textSize);
		const postcodeW = postcode ? ctx.measureText(postcode).width : 0;
		const gap = this.px(1.5);
		if (logo) {
			const logoW = this.px(height) * (logo.naturalWidth / logo.naturalHeight);
			const groupW = logoW + (postcode ? gap + postcodeW : 0);
			const x = (canvas.width - groupW) / 2;
			ctx.drawImage(logo, x, y(box.bottom + height), logoW, this.px(height));
			if (postcode) {
				ctx.textAlign = 'left';
				ctx.fillText(postcode, x + logoW + gap, textBaseline);
			}
		} else {
			const line = [supplier, postcode].filter(Boolean).join('  ');
			ctx.font = font(Math.min(3.2, height * 0.6));
			ctx.textAlign = 'center';
			ctx.fillText(line, canvas.width / 2, textBaseline);
		}

		if (standard) {
			// Hex plates taper at the ends, so keep the mark inside the outline.
			const rightInset = plate.shape === 'hex' ? plate.chamfer + 6 : box.bottom + 8;
			ctx.font = font(textSize);
			ctx.textAlign = 'right';
			ctx.fillText(standard, canvas.width - this.px(rightInset), textBaseline);
		}
		this.texture.needsUpdate = true;
	}

	dispose() {
		this.texture.dispose();
	}
}

function roundedRectPath(ctx, x, y, w, h, r) {
	ctx.beginPath();
	ctx.moveTo(x + r, y);
	ctx.arcTo(x + w, y, x + w, y + h, r);
	ctx.arcTo(x + w, y + h, x, y + h, r);
	ctx.arcTo(x, y + h, x, y, r);
	ctx.arcTo(x, y, x + w, y, r);
	ctx.closePath();
}

function roundedRectShape(x, y, w, h, r) {
	const s = new THREE.Shape();
	s.moveTo(x + r, y);
	s.lineTo(x + w - r, y);
	s.absarc(x + w - r, y + r, r, -Math.PI / 2, 0, false);
	s.lineTo(x + w, y + h - r);
	s.absarc(x + w - r, y + h - r, r, 0, Math.PI / 2, false);
	s.lineTo(x + r, y + h);
	s.absarc(x + r, y + h - r, r, Math.PI / 2, Math.PI, false);
	s.lineTo(x, y + r);
	s.absarc(x + r, y + r, r, Math.PI, Math.PI * 1.5, false);
	return s;
}

/** Map a ShapeGeometry's UVs from mm to 0..1 across the plate. */
function plateUVs(geo, plate) {
	const pos = geo.attributes.position;
	const uv = geo.attributes.uv;
	for (let i = 0; i < pos.count; i++) {
		uv.setXY(i, (pos.getX(i) + plate.width / 2) / plate.width, (pos.getY(i) + plate.height / 2) / plate.height);
	}
}

/**
 * The back of a finished plate is the back of the reflective sheet: off-white with the maker's
 * print repeated across it in grey, tilted slightly, alternate rows staggered. One tile is
 * BACKING.w × BACKING.h mm and holds two prints; the body's cap UVs are in mm, so the texture
 * repeats every tile.
 */
const BACKING = { w: 160, h: 52, px: 6, ground: '#ECEBE6', ink: '#9C978F' };

function backingTexture() {
	const { w, h, px } = BACKING;
	const canvas = document.createElement('canvas');
	canvas.width = w * px;
	canvas.height = h * 2 * px;
	const g = canvas.getContext('2d');
	g.fillStyle = BACKING.ground;
	g.fillRect(0, 0, canvas.width, canvas.height);
	g.scale(px, px); // draw in mm

	const print = (x, y) => {
		g.save();
		g.translate(x, y);
		g.rotate(THREE.MathUtils.degToRad(-5));
		g.fillStyle = BACKING.ink;
		g.strokeStyle = BACKING.ink;
		g.textBaseline = 'alphabetic';
		g.font = '800 10px Arial, Helvetica, sans-serif';
		g.fillText('Nikkalite', 0, 0);
		const nw = g.measureText('Nikkalite').width;
		g.font = '600 2.6px Arial, Helvetica, sans-serif';
		g.fillText('®', nw + 0.4, -6.2);
		g.fillText('Easyprint', nw + 3, -1.6);
		g.font = '400 7.6px Arial, Helvetica, sans-serif';
		g.fillText('Reflective Sheeting', 0.4, 8.6);
		g.font = '700 3.5px Arial, Helvetica, sans-serif';
		g.fillText('Nippon Carbide Industries Co., Inc.', 0.6, 13.4);
		g.lineWidth = 0.6;
		g.beginPath();
		g.moveTo(0.6, 15);
		g.lineTo(0.6 + g.measureText('Nippon Carbide Industries Co., Inc.').width, 15);
		g.stroke();
		g.restore();
	};
	// Two rows per tile, the second shifted half a tile, each print repeated across the seam.
	for (const dx of [-w, 0, w]) {
		print(14 + dx, 20);
		print(14 + w / 2 + dx, 20 + h);
	}

	const tex = new THREE.CanvasTexture(canvas);
	tex.colorSpace = THREE.SRGBColorSpace;
	tex.wrapS = tex.wrapT = THREE.RepeatWrapping;
	// Cap UVs are mm. Seen from behind, x runs the other way, so flip it to keep the print readable.
	tex.repeat.set(-1 / w, 1 / (h * 2));
	return tex;
}

const printed = (extra) => new THREE.MeshPhysicalMaterial({
	roughness: 0.42,
	clearcoat: 1,
	clearcoatRoughness: 0, // the acrylic over the print is glass-smooth
	envMapIntensity: 0.6,
	polygonOffset: true,
	polygonOffsetFactor: -1.5,
	polygonOffsetUnits: -1.5,
	...extra,
});

export class PlateModel {
	/**
	 * @param {object}   opts
	 * @param {object}   opts.plate     resolved size (numeric width)
	 * @param {Function} opts.onChange  called when async artwork finishes, so the viewport re-renders
	 */
	constructor({ plate, side = 'front', footer = {}, flagArtwork = {}, onChange = () => {} }) {
		this.group = new THREE.Group();
		this.group.name = 'Plate';
		this.footer = footer;
		this.flagArtwork = flagArtwork;
		this.onChange = onChange;
		this.decor = { badge: 'none', border: 'none', lines: 1, band: null, footerTop: null };

		// Supplier logo for the legal footer; until it loads the footer shows the name as text.
		this.logo = null;
		if (footer.logo) {
			const img = new Image();
			img.crossOrigin = 'anonymous';
			img.onload = () => {
				this.logo = img;
				this.#drawFooter(true);
				this.onChange();
			};
			img.src = footer.logo;
		}

		this.faceMaterial = printed({ polygonOffsetFactor: -1, polygonOffsetUnits: -1 });
		this.edgeMaterial = new THREE.MeshPhysicalMaterial({ roughness: 0.05, clearcoat: 1, clearcoatRoughness: 0.02 });
		// The key light faces the front, so the back glows a little to read as the off-white sheet.
		const backing = backingTexture();
		this.backMaterial = new THREE.MeshStandardMaterial({ map: backing, roughness: 0.55, emissive: '#ffffff', emissiveMap: backing, emissiveIntensity: 0.65 });
		this.evMaterial = printed({ color: BADGE.evColour });
		// Clear resin for gel badges: real transmission, so the print shows through with a lens-like dome.
		this.resinMaterial = new THREE.MeshPhysicalMaterial({
			color: '#FFFFFF',
			roughness: 0.03,
			transmission: 1,
			thickness: BADGE.gelHeight,
			ior: 1.5,
			clearcoat: 1,
			clearcoatRoughness: 0.02,
			envMapIntensity: 1.6,
		});
		this.borderMaterial = printed();
		// Alpha-tested, not transparent: gel resin only refracts opaque objects, so the badge must be one.
		this.badgeMaterial = printed({ alphaTest: 0.5, polygonOffsetFactor: -2, polygonOffsetUnits: -2 });
		// 4D badge tile: glossy blue acrylic, like the laser-cut characters.
		this.tileMaterial = new THREE.MeshPhysicalMaterial({ color: GEL.blue, roughness: 0.15, clearcoat: 1, clearcoatRoughness: 0.05 });

		this.decorGroup = new THREE.Group();
		this.decorGroup.position.z = FACE_Z;
		this.group.add(this.decorGroup);

		this.setPlate(plate);
		this.setSide(side);
	}

	/* ------------------------------------------------------------- geometry */

	/** Rebuild for new dimensions/shape. Cheap enough to run per keystroke on short plates. */
	setPlate(plate) {
		const same = this.plate && ['width', 'height', 'shape', 'radius', 'chamfer', 'point'].every((k) => this.plate[k] === plate[k]);
		if (same) return false;
		this.plate = plate;

		this.body?.geometry.dispose();
		this.face?.geometry.dispose();
		if (this.body) this.group.remove(this.body, this.face);

		const b = EDGE_BEVEL;
		// Inset the outline by the bevel so the finished plate measures exactly width x height.
		const shape = plateShape(plate, b);

		// Body. ExtrudeGeometry groups: 0 = front/back caps, 1 = sides (incl. bevel).
		const bodyGeo = new THREE.ExtrudeGeometry(shape, {
			depth: THICKNESS - 2 * b,
			bevelEnabled: true,
			bevelThickness: b,
			bevelSize: b,
			bevelSegments: 4,
			curveSegments: 16,
		});
		bodyGeo.translate(0, 0, b - THICKNESS / 2); // centre on z = 0
		this.body = new THREE.Mesh(bodyGeo, [this.backMaterial, this.edgeMaterial]);
		this.body.name = 'PlateBody';

		const faceGeo = new THREE.ShapeGeometry(shape, 16);
		plateUVs(faceGeo, plate);
		faceGeo.translate(0, 0, FACE_Z);
		this.face = new THREE.Mesh(faceGeo, this.faceMaterial);
		this.face.name = 'PlateFace';
		this.face.receiveShadow = true; // raised characters cast onto the face

		this.group.add(this.body, this.face);

		// Footer artwork scales with the plate.
		const a = this.artwork;
		if (!a || a.plate.width !== plate.width || a.plate.height !== plate.height) {
			this.artwork?.dispose();
			this.artwork = new FaceArtwork(plate);
			if (this.anisotropy) this.artwork.texture.anisotropy = this.anisotropy;
			this.faceMaterial.map = this.artwork.texture;
			this.faceMaterial.needsUpdate = true;
		}
		this.artwork.plate = plate;
		this.#drawFooter(true);

		this.#buildDecor();
		return true;
	}

	/* ------------------------------------------------------------ decoration */

	/**
	 * @param {object} decor
	 * @param {string} decor.badge   BADGES id
	 * @param {string} decor.border  BORDERS id
	 * @param {number} decor.lines   1 or 2 (two-line plates put the badge beside the bottom line)
	 * @param {?{y:number, top:number}} decor.band  bottom-line band from placeOnPlate
	 */
	setDecor(decor) {
		this.decor = { ...this.decor, ...decor };
		this.#buildDecor();
		this.#drawFooter();
	}

	/**
	 * Footer band: above the border (if any) and below the lowest character.
	 * Redrawn only when the band changes, so typing doesn't re-upload the face texture.
	 */
	#drawFooter(force = false) {
		const border = getBorder(this.decor.border);
		const bottom = border.width ? BORDER.inset + border.width + FOOTER_GAP : 2;
		const top = (this.decor.footerTop ?? bottom + LOGO_MAX_HEIGHT + FOOTER_GAP) - FOOTER_GAP;
		const key = `${bottom.toFixed(2)}|${top.toFixed(2)}`;
		if (!force && key === this.footerKey) return;
		this.footerKey = key;
		this.artwork.draw(this.footer, this.logo, { bottom, top });
	}

	#buildDecor() {
		this.decorGroup.children.forEach((m) => m.geometry.dispose());
		this.decorGroup.clear();

		const { plate, decor } = this;
		const badge = getBadge(decor.badge);
		const border = getBorder(decor.border);
		const left = -plate.width / 2;
		const offset = badgeOffset(plate);
		const twoLine = decor.lines === 2 && decor.band;
		const centreY = twoLine ? decor.band.y : 0;

		// Printed EV strip: green band down the left, clipped to the plate outline.
		if (badge.ev && !badge.design) {
			let pts = plateShape(plate).getPoints(16);
			pts = clipMax(pts, 'x', left + offset + badgeZone(badge, decor.lines));
			if (twoLine) pts = clipMax(pts, 'y', decor.band.top);
			const geo = new THREE.ShapeGeometry(new THREE.Shape(pts));
			geo.translate(0, 0, PRINT_Z.ev);
			this.decorGroup.add(new THREE.Mesh(geo, this.evMaterial));
		}

		if (border.colour) {
			const ring = plateShape(plate, BORDER.inset);
			ring.holes.push(plateShape(plate, BORDER.inset + border.width));
			const geo = new THREE.ShapeGeometry(ring, 16);
			geo.translate(0, 0, PRINT_Z.border);
			this.borderMaterial.color.set(border.colour);
			this.decorGroup.add(new THREE.Mesh(geo, this.borderMaterial));
		}

		// Gel and 4D badges: a 42 x 98 mm design, clear of any border. Gel sits under a clear
		// resin dome; 4D is printed on top of a raised acrylic tile.
		if (badge.design) {
			const clear = border.width ? BORDER.inset + border.width + 0.5 : BADGE.inset;
			const room = twoLine ? 2 * (decor.band.top - decor.band.y) : plate.height - 2 * clear;
			let width = gelWidth(decor.lines);
			let height = Math.min(GEL.height * (width / GEL.width), room);
			width = Math.min(width, height * (GEL.width / GEL.height));
			const x = left + offset + Math.max(BADGE.inset, clear);
			const y = centreY - height / 2;
			const radius = GEL.radius * (width / GEL.width);
			const raised = badge.finish === '4d' ? BADGE.raised : 0;
			const geo = new THREE.PlaneGeometry(width, height);
			geo.translate(x + width / 2, centreY, PRINT_Z.badge + raised);
			this.decorGroup.add(new THREE.Mesh(geo, this.badgeMaterial));
			this.#drawBadge(width, height, badge.design, radius);
			if (raised) this.#addTile(roundedRectShape(x, y, width, height, radius), raised);
			else this.#addResin(roundedRectShape(x, y, width, height, radius), Math.min(3, width / 8));
			return;
		}

		if (badge.uk) {
			const width = badgeWidth(decor.lines);
			const height = this.#badgeHeight(width);
			const cx = left + offset + BADGE.inset + width / 2;
			const geo = new THREE.PlaneGeometry(width, height);
			geo.translate(cx, centreY, PRINT_Z.badge);
			this.decorGroup.add(new THREE.Mesh(geo, this.badgeMaterial));
			this.#drawBadge(width, height, 'printed');
		}
	}

	/** 4D: a solid acrylic tile under the badge artwork, casting a shadow like the characters. */
	#addTile(shape, depth) {
		const b = 0.3;
		const geo = new THREE.ExtrudeGeometry(shape, {
			depth: depth - b,
			bevelEnabled: true,
			bevelThickness: b,
			bevelSize: b,
			bevelOffset: -b,
			bevelSegments: 2,
			curveSegments: 12,
		});
		geo.translate(0, 0, PRINT_Z.badge - 0.01);
		const mesh = new THREE.Mesh(geo, this.tileMaterial);
		mesh.castShadow = true;
		this.decorGroup.add(mesh);
	}

	/** Gel: a clear domed resin layer over a printed shape. */
	#addResin(shape, inset) {
		const h = BADGE.gelHeight;
		let geo = new THREE.ExtrudeGeometry(shape, {
			depth: 0.05,
			bevelEnabled: true,
			bevelThickness: h,
			bevelSize: inset,
			bevelOffset: -inset,
			bevelSegments: 6,
			curveSegments: 12,
		});
		const pos = geo.attributes.position;
		for (let i = 0; i < pos.count; i++) if (pos.getZ(i) < 0) pos.setZ(i, 0); // flat underside
		geo.deleteAttribute('normal');
		geo.deleteAttribute('uv');
		geo = mergeVertices(geo, 1e-3);
		geo.computeVertexNormals();
		geo.translate(0, 0, PRINT_Z.badge + 0.01);
		const mesh = new THREE.Mesh(geo, this.resinMaterial);
		mesh.castShadow = false;
		this.decorGroup.add(mesh);
	}

	#badgeHeight(width) {
		const flagH = (width - 4) * BADGE.flagRatio;
		const identH = width * 0.36; // "UK" scales with the badge (≈16 mm on a 44 mm badge)
		return flagH + identH + 12;
	}

	/**
	 * Badge artwork on a transparent canvas, portrait, top of the canvas = top of the badge.
	 *   printed   Union Flag over a blue "UK"
	 *   uk-white  flag on its side, black divider, black "UK" on white
	 *   uk-blue   flag on its side, white "UK" on blue
	 *   ev        plain green
	 *   ev-uk     green, small flag, white "UK"
	 */
	async #drawBadge(widthMm, heightMm, design, radiusMm = 0) {
		const token = (this.badgeToken = Symbol('badge'));
		let img;
		try {
			img = await loadFlagImage(this.flagArtwork.uk);
		} catch (e) {
			console.warn('[PlateBoss] Flag artwork failed to load', e);
			return;
		}
		if (token !== this.badgeToken) return; // a newer badge was requested meanwhile

		const k = BADGE_PX_PER_MM;
		const canvas = this.badgeCanvas ?? (this.badgeCanvas = document.createElement('canvas'));
		canvas.width = Math.round(widthMm * k);
		canvas.height = Math.round(heightMm * k);
		const W = canvas.width;
		const H = canvas.height;
		const ctx = canvas.getContext('2d');
		ctx.clearRect(0, 0, W, H);

		// "UK" in heavy capitals, as wide as `maxW` allows, centred on (cx, baseline).
		const drawUK = (colour, cx, baseline, capH, maxW) => {
			let size = capH * 1.38; // font size whose cap height ≈ capH
			const font = (px) => `900 ${px}px "Arial Black", "Arial Narrow", Arial, sans-serif`;
			ctx.font = font(size);
			const w = ctx.measureText('UK').width;
			if (w > maxW) ctx.font = font((size *= maxW / w));
			ctx.fillStyle = colour;
			ctx.textAlign = 'center';
			ctx.textBaseline = 'alphabetic';
			ctx.fillText('UK', cx, baseline);
		};
		// Draw the flag to cover a box, cropping from the centre; `turned` = on its side.
		const drawFlag = (x, y, w, h, turned) => {
			ctx.save();
			ctx.beginPath();
			ctx.rect(x, y, w, h);
			ctx.clip();
			ctx.translate(x + w / 2, y + h / 2);
			if (turned) ctx.rotate(-Math.PI / 2);
			const bw = turned ? h : w;
			const bh = turned ? w : h;
			const s = Math.max(bw / img.width, bh / img.height);
			ctx.drawImage(img, (-img.width * s) / 2, (-img.height * s) / 2, img.width * s, img.height * s);
			ctx.restore();
		};

		if (design === 'printed') {
			// Flag, cropped from the centre of the 2:1 artwork, over a blue "UK".
			const pad = 2 * k;
			const fw = W - 2 * pad;
			drawFlag(pad, 4 * k, fw, fw * BADGE.flagRatio, false);
			drawUK(BADGE.identColour, W / 2, H - 4 * k, widthMm * 0.36 * k, fw * 0.8);
		} else {
			ctx.save();
			roundedRectPath(ctx, 0, 0, W, H, radiusMm * k);
			ctx.clip();
			const panel = H * (1 - GEL.flagShare);
			const capH = Math.min(panel * 0.5, W * 0.34);
			if (design === 'uk-white' || design === 'uk-blue') {
				const flagH = H - panel;
				ctx.fillStyle = design === 'uk-white' ? GEL.white : GEL.blue;
				ctx.fillRect(0, flagH, W, panel);
				drawFlag(0, 0, W, flagH, true);
				if (design === 'uk-white') {
					ctx.fillStyle = GEL.black;
					ctx.fillRect(0, flagH - (GEL.divider * k) / 2, W, GEL.divider * k);
				}
				drawUK(design === 'uk-white' ? GEL.black : GEL.white, W / 2, flagH + panel / 2 + capH / 2, capH, W * 0.8);
			} else {
				ctx.fillStyle = GEL.green;
				ctx.fillRect(0, 0, W, H);
				if (design === 'ev-uk') {
					const m = W * 0.14;
					const fw = W - 2 * m;
					drawFlag(m, m, fw, fw * 0.6, false);
					drawUK(GEL.white, W / 2, H - panel / 2 + capH / 2, capH, W * 0.8);
				}
			}
			ctx.restore();
		}

		if (!this.badgeTexture) {
			this.badgeTexture = new THREE.CanvasTexture(canvas);
			this.badgeTexture.colorSpace = THREE.SRGBColorSpace;
			this.badgeTexture.anisotropy = this.anisotropy ?? 1;
			this.badgeMaterial.map = this.badgeTexture;
			this.badgeMaterial.needsUpdate = true;
		} else {
			// Canvas may have been resized: three.js needs a fresh upload.
			this.badgeTexture.dispose();
			this.badgeTexture.image = canvas;
		}
		this.badgeTexture.needsUpdate = true;
		this.onChange();
	}

	/* --------------------------------------------------------------- colour */

	/** Colours for a side, so the viewport can tween between them. */
	static coloursFor(side) {
		const face = new THREE.Color(FACE_COLOURS[side] ?? FACE_COLOURS.front);
		const edge = new THREE.Color(EDGE_TINT).lerp(face, 0.35);
		return { face, edge };
	}

	setSide(side) {
		this.side = side === 'rear' ? 'rear' : 'front';
		const { face, edge } = PlateModel.coloursFor(this.side);
		this.applyColours(face, edge);
	}

	applyColours(face, edge) {
		this.faceMaterial.color.copy(face);
		this.edgeMaterial.color.copy(edge);
	}

	setAnisotropy(value) {
		this.anisotropy = value;
		this.artwork.texture.anisotropy = value;
		this.artwork.texture.needsUpdate = true;
		if (this.backMaterial.map) {
			this.backMaterial.map.anisotropy = value; // also the emissive map: the same texture
			this.backMaterial.map.needsUpdate = true;
		}
	}

	dispose() {
		this.body.geometry.dispose();
		this.face.geometry.dispose();
		this.decorGroup.children.forEach((m) => m.geometry.dispose());
		this.backMaterial.map?.dispose();
		[this.faceMaterial, this.edgeMaterial, this.backMaterial, this.evMaterial, this.resinMaterial, this.borderMaterial, this.badgeMaterial].forEach((m) => m.dispose());
		this.badgeTexture?.dispose();
		this.artwork.dispose();
	}
}
