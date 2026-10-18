import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { createDetailingStudio } from './plate-studio.js';
import { PlateModel, THICKNESS } from './plate-model.js';
import { loadPlateFont } from './plate-font.js';
import { PlateText } from './plate-text.js';
import { CHARACTER_SPECS, formatRegistration, layoutRegistration, placeOnPlate } from './plate-layout.js';
import { getAccent, getStyle } from './plate-styles.js';
import { fixedSide, getSize, styleAvailable } from './plate-sizes.js';
import { badgeAvailable, badgeZone, getBadge, getBorder } from './plate-badges.js';
import { CARS, createCar, disposeCar, getCar, getPaint, PAINTS } from './car-model.js';

const MIN_ZOOM = 0.25; // closest the camera gets, as a fraction of the fit-to-frame distance
const MAX_ZOOM = 1.25; // furthest: a little smaller than the framed view, never lost in space
// Studio view: the plate spins all the way round on its centre; tilt stops short of straight
// over or under it, where the plate would go edge-on.
const MAX_TILT = THREE.MathUtils.degToRad(80);
// Double-tap / double-click: zoom in to this fraction of the framed distance, on the plate's centre.
const TAP_ZOOM = 0.42;
const TAP_MS = 320; // max gap between taps
const TAP_SLOP = 24; // px: taps further apart than this aren't a double-tap
const ZOOM_MS = 450;
const ORIGIN = new THREE.Vector3();
const PLACEHOLDER = 'YOUR REG';

/** Short and hex plates come in three cut lengths: for 5, 6 and 7 characters (fewer than 5 uses the 5 length). */
const CUT_LENGTHS = [5, 6, 7];

/** A widest-case registration of `n` characters (no narrow 1s or Is), for sizing a cut length. */
const cutReference = (n) => {
	const chars = 'ABCDEFG'.slice(0, n);
	return `${chars.slice(0, n - 3)} ${chars.slice(n - 3)}`;
};

const FOV = 26;
const FRAME_MARGIN = 1.16; // breathing room around the plate when framed
const FRAME_MARGIN_V = 1.5; // extra top/bottom room so tall plates clear the hint and toolbar
const DEFAULT_AZIMUTH = THREE.MathUtils.degToRad(-14);
const DEFAULT_ELEVATION = THREE.MathUtils.degToRad(7);
// "On a car": a three-quarter view from the plate's end of the car.
const CAR_AZIMUTH = THREE.MathUtils.degToRad(-38);
const CAR_ELEVATION = THREE.MathUtils.degToRad(11);
const CAR_MIN_DISTANCE = 220; // close enough to read the plate's characters
// Close-up views, still turning on the plate's centre. zoom is a fraction of the fit-to-frame distance.
const SHOWCASES = {
	// Reg typed or character style picked: a steep view along the plate from its left end, so the
	// depth (4D edges, gel domes, bevels) shows in profile.
	// Framed so the whole plate fits (see #frameAt), so zoom is only the starting guess.
	style: { azimuth: THREE.MathUtils.degToRad(-42), elevation: THREE.MathUtils.degToRad(-20), zoom: 0.62, frame: true },
	// Badge picked: aimed at the badge at the left end (the only view not centred on the plate;
	// it slides back to the centre as soon as the customer turns it).
	badge: { azimuth: THREE.MathUtils.degToRad(-34), elevation: THREE.MathUtils.degToRad(8), zoom: 0.34, onBadge: true },
};
const SHOWCASE_MS = 900;
const SETTLE_MS = 420; // pivot easing back to the plate's visible centre after a turn
const FRAME_LIFT = 0.1; // NDC: aim a little above centre, clear of the view buttons
const RESET_MS = 700;
const SIDE_MS = 320;
const FRAME_MS = 450;

const easeInOutCubic = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
const prefersReducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

/**
 * The 3D viewport. Renders on demand: the loop only runs while the user is
 * dragging, damping is settling, or a tween is playing. An idle builder
 * costs no GPU time, which matters on phones.
 */
export class PlateViewport extends EventTarget {
	constructor(container, config = {}) {
		super();
		this.container = container;
		this.tweens = new Set();
		this.raf = 0;
		this.interacting = false;
		this.fitDistance = 1;

		this.#initRenderer();
		this.#initScene();
		this.#initControls();

		// Everything the customer can choose (Step 5 sends this to the cart).
		// Front and rear sizes are separate; `linked` keeps them the same until the customer splits them.
		let size = getSize(config.size);
		let rearSize = getSize(config.rearSize ?? size.id);
		if (size.single) rearSize = size;
		else if (size.rearOnly) [size, rearSize] = [getSize('standard'), size];
		const badge = getBadge(config.badge);
		this.options = {
			size: size.id,
			rearSize: rearSize.id,
			linked: size.id === rearSize.id,
			badge: badgeAvailable(badge, size.id) && badgeAvailable(badge, rearSize.id) ? badge.id : 'none',
			border: getBorder(config.border).id,
		};
		this.widths = { front: null, rear: null };

		this.plate = new PlateModel({
			plate: { ...size, width: size.width === 'auto' ? 520 : size.width },
			side: fixedSide(size) ?? (rearSize.id !== size.id && config.size === rearSize.id ? 'rear' : config.side),
			footer: { supplier: config.supplier, postcode: config.postcode, standard: config.standard, logo: config.logo },
			flagArtwork: config.flagArtwork ?? {},
			onChange: () => this.requestRender(),
		});
		this.plate.setAnisotropy(this.renderer.capabilities.getMaxAnisotropy());
		this.shadow = this.#createContactShadow();
		this.scene.add(this.plate.group, this.shadow);
		this.#fitLighting();

		this.resizeObserver = new ResizeObserver(() => this.#resize());
		this.resizeObserver.observe(container);
		this.#resize(); // no-op if the container has no size yet; the observer will retry
		this.requestRender();

		this.view = 'plate';
		this.car = null;
		this.carId = getCar(config.car).id;
		this.paintId = getPaint(config.paint).id;
		this.assetsUrl = config.assetsUrl ?? '';

		this.registration = config.registration ?? '';
		// `chosenStyle` is the customer's pick; sizes that only offer some styles override it without forgetting it.
		this.chosenStyle = getStyle(config.style).id;
		this.style = getStyle(styleAvailable(size, this.chosenStyle) && styleAvailable(rearSize, this.chosenStyle) ? this.chosenStyle : 'printed');
		this.chosenAccent = config.accent ?? null; // null = use each style's default effect colour
		// Both fonts are tiny (~11 KB), so load them up front and switch sizes instantly.
		const fontUrls = { standard: config.fontUrl, ...config.fonts };
		fontUrls.motorcycle ??= fontUrls.standard;
		this.fontReady = Promise.all(Object.entries(fontUrls).map(([key, url]) => loadPlateFont(url).then((f) => [key, f])))
			.then((entries) => {
				this.fonts = Object.fromEntries(entries);
				this.text = new PlateText(this.fonts.standard, { reducedMotion: prefersReducedMotion(), style: this.style.id, accent: this.accent });
				this.plate.group.add(this.text.group);
				this.#apply();
			})
			.catch((err) => {
				console.error('[PlateBoss] Could not load plate font', err);
				this.dispatchEvent(new CustomEvent('error', { detail: err }));
			});
	}

	/* ------------------------------------------------------------- characters */

	/** Effect colour in use: the customer's pick, else the style's default. */
	get accent() {
		return this.chosenAccent ?? this.style.accent ?? null;
	}

	setStyle(id) {
		this.chosenStyle = getStyle(id).id;
		this.#syncStyle();
	}

	/** Apply the chosen style if both plate sizes offer it, else printed. */
	#syncStyle() {
		const ok = styleAvailable(this.sizeFor('front'), this.chosenStyle) && styleAvailable(this.sizeFor('rear'), this.chosenStyle);
		const id = ok ? this.chosenStyle : 'printed';
		this.style = getStyle(id);
		this.text?.setStyle(this.style.id);
		if (this.accent) this.text?.setAccent(this.accent);
		this.#emitStyle();
	}

	setAccent(id) {
		this.chosenAccent = getAccent(id).id;
		this.text?.setAccent(this.chosenAccent);
		this.#emitStyle();
	}

	#emitStyle() {
		this.dispatchEvent(new CustomEvent('stylechange', { detail: { style: this.style, accent: this.accent } }));
		this.requestRender();
	}

	/** Accepts raw input; formatting and legal spacing are applied here. */
	setRegistration(input) {
		this.registration = input;
		if (this.text) this.#apply();
	}

	/* ---------------------------------------------------- size, flag, border */

	/**
	 * Pick a size for the plate on screen. Linked sizes change together; motorbike and trailer
	 * sizes are one plate; Oversized and 4x4 are rear only, so they split the sizes and show the rear.
	 */
	setSize(id) {
		const s = getSize(id);
		const o = this.options;
		const wasSingle = getSize(o.size).single;
		if (s.single || ((o.linked || wasSingle) && !s.rearOnly)) {
			o.size = o.rearSize = s.id;
			o.linked = true;
		} else if (s.rearOnly) {
			o.rearSize = s.id;
			if (wasSingle) o.size = 'standard';
			o.linked = false;
		} else if (this.side === 'rear') {
			o.rearSize = s.id;
		} else {
			o.size = s.id;
		}
		if (!this.#badgeFits(getBadge(o.badge))) o.badge = 'none';

		const side = fixedSide(s) ?? (s.rearOnly ? 'rear' : null);
		if (s.onCar === false) this.setView('plate');
		this.#syncStyle();
		if (side && this.side !== side) this.setSide(side);
		else this.#apply();
	}

	/** "Same size front & rear". Returns false if the plate on screen is rear only, so can't be linked. */
	setLinked(on) {
		const o = this.options;
		if (on) {
			const current = this.size;
			if (current.rearOnly && !current.single) return false;
			o.size = o.rearSize = current.id;
		}
		o.linked = Boolean(on);
		if (!this.#badgeFits(getBadge(o.badge))) o.badge = 'none';
		this.#syncStyle();
		this.#apply();
		return true;
	}

	/** Badges are only offered on some sizes; unavailable picks fall back to none. */
	setBadge(id) {
		const badge = getBadge(id);
		this.options.badge = this.#badgeFits(badge) ? badge.id : 'none';
		this.#apply();
	}

	/** A badge has to be available on both plate sizes. */
	#badgeFits(badge) {
		return badgeAvailable(badge, this.options.size) && badgeAvailable(badge, this.options.rearSize);
	}

	setBorder(id) {
		this.options.border = getBorder(id).id;
		this.#apply();
	}

	/** The size of the plate on screen. */
	get size() {
		return this.sizeFor(this.plate?.side ?? 'front');
	}

	sizeFor(side) {
		return getSize(side === 'rear' ? this.options.rearSize : this.options.size);
	}

	/** Road legal only if every chosen option is. */
	get roadLegal() {
		return this.style.roadLegal && getBorder(this.options.border).roadLegal;
	}

	/** Snapshot of the full configuration, e.g. for the cart. */
	getConfig() {
		return {
			registration: formatRegistration(this.registration),
			side: this.side,
			style: this.style.id,
			accent: this.style.accent ? this.accent : null,
			...this.options,
			// Cut-to-fit lengths for each side (short and hex plates).
			width: this.widths.front ?? this.plate.plate.width,
			rearWidth: this.widths.rear ?? this.plate.plate.width,
			height: this.plate.plate.height,
			roadLegal: this.roadLegal,
		};
	}

	/** Lay the registration out on a size, cutting short and hex plates to its length. */
	#place(size, registration, badge) {
		const zone = badgeZone(badge, size.lines);
		const spec = CHARACTER_SPECS[size.chars];
		const font = this.fonts[spec.font] ?? this.fonts.standard;
		const layout = layoutRegistration(font, registration || PLACEHOLDER, spec, size.lines);
		let placed = placeOnPlate(layout, size, zone);

		// Cut-to-fit plates use the length for the registration's character count, never shorter.
		if (size.width === 'auto') {
			const count = (registration || PLACEHOLDER).replace(/\s/g, '').length;
			const n = CUT_LENGTHS.find((c) => c >= count) ?? count;
			const ref = placeOnPlate(layoutRegistration(font, cutReference(n), spec, size.lines), size, zone);
			if (ref.plate.width > placed.plate.width) placed = placeOnPlate(layout, { ...size, width: ref.plate.width }, zone);
		}
		return { layout, placed, font };
	}

	/** Recompute layout, plate dimensions and decoration from the current options. */
	#apply() {
		const size = this.size;
		const side = this.side;
		const badge = getBadge(this.options.badge);
		let band = null;
		let footerTop = null;

		if (this.text) {
			const registration = formatRegistration(this.registration);
			const { layout, placed, font } = this.#place(size, registration, badge);

			// The plate off screen: its cut length, and whether the registration fits it too.
			const otherSide = side === 'rear' ? 'front' : 'rear';
			const otherSize = this.sizeFor(otherSide);
			const other = otherSize.id === size.id ? placed : this.#place(otherSize, registration, badge).placed;
			this.widths = { [side]: placed.plate.width, [otherSide]: other.plate.width };

			if (this.plate.setPlate(placed.plate)) this.#onPlateResized();
			this.text.setLayout(placed, { font });
			band = placed.badgeBand;
			footerTop = Math.min(...placed.glyphs.map((g) => g.y)) + placed.plate.height / 2;
			this.layout = { ...layout, ...placed };

			this.dispatchEvent(new CustomEvent('layoutchange', {
				detail: {
					registration, layout: this.layout, plate: placed.plate, fits: placed.fits, placeholder: !registration, size,
					// Both plates have to fit; `otherFits` explains which one doesn't.
					otherFits: other.fits, otherSide, otherSize, fitsAll: placed.fits && other.fits,
				},
			}));
		} else if (size.width !== 'auto' && this.plate.setPlate({ ...size })) {
			this.#onPlateResized();
		}
		this.plate.setDecor({ badge: badge.id, border: this.options.border, lines: size.lines, band, footerTop: Number.isFinite(footerTop) ? footerTop : null });

		this.dispatchEvent(new CustomEvent('configchange', { detail: this.getConfig() }));
		this.requestRender();
	}

	#onPlateResized() {
		this.#fitLighting();
		this.#reframe();
	}

	/** Shadow camera and floor shadow follow the plate's size. */
	#fitLighting() {
		const { width, height } = this.plate.plate;
		const cam = this.keyLight.shadow.camera;
		Object.assign(cam, { left: -width / 2 - 20, right: width / 2 + 20, top: height / 2 + 15, bottom: -height / 2 - 15 });
		cam.updateProjectionMatrix();
		this.shadow.scale.x = width / 520;
		this.shadow.position.y = -height / 2 - 28;
	}

	/* ------------------------------------------------------------------ setup */

	#initRenderer() {
		this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
		this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
		this.renderer.outputColorSpace = THREE.SRGBColorSpace;
		// Neutral tone mapping keeps the legal white/yellow true instead of washing them out.
		this.renderer.toneMapping = THREE.NeutralToneMapping;
		this.renderer.toneMappingExposure = 1.0;
		this.renderer.shadowMap.enabled = true;
		this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
		this.renderer.domElement.classList.add('pbb-canvas');
		this.container.appendChild(this.renderer.domElement);
	}

	#initScene() {
		this.scene = new THREE.Scene();

		// Reflections for the clearcoat and polished edges: a detailing studio with hexagon
		// ceiling lights, so the honeycomb slides across the plate as it turns.
		// Rendered at 512 px a side (PMREM's own scene render is fixed at 256), so the thin tubes
		// stay crisp in the gloss.
		const pmrem = new THREE.PMREMGenerator(this.renderer);
		const room = createDetailingStudio();
		const cube = new THREE.WebGLCubeRenderTarget(512, { type: THREE.HalfFloatType });
		new THREE.CubeCamera(0.1, 100, cube).update(this.renderer, room);
		this.envMap = pmrem.fromCubemap(cube.texture).texture;
		cube.dispose();
		this.scene.environment = this.envMap;
		this.scene.environmentIntensity = 0.7;
		room.dispose();
		pmrem.dispose();

		// A soft key light gives the acrylic a moving highlight as the plate turns.
		// It also casts the shadows of raised (gel/4D/hover) characters onto the face.
		const key = (this.keyLight = new THREE.DirectionalLight('#ffffff', 1.4));
		key.position.set(-260, 420, 600);
		key.castShadow = true;
		key.shadow.mapSize.set(2048, 1024);
		key.shadow.radius = 4;
		key.shadow.bias = -0.0004;
		key.shadow.normalBias = 0.3;
		Object.assign(key.shadow.camera, { left: -300, right: 300, top: 110, bottom: -110, near: 400, far: 1200 });
		this.scene.add(key, new THREE.AmbientLight('#ffffff', 0.25));

		this.camera = new THREE.PerspectiveCamera(FOV, 1, 10, 60000);

		// "On a car": the car is placed around the plate, which stays at the origin.
		this.carRoot = new THREE.Group();
		this.carRoot.visible = false;
		this.scene.add(this.carRoot);
	}

	/** Baked soft shadow on an invisible floor, so the plate sits in space from any angle. */
	#createContactShadow() {
		const c = document.createElement('canvas');
		c.width = 512;
		c.height = 128;
		const ctx = c.getContext('2d');
		const g = ctx.createRadialGradient(256, 64, 0, 256, 64, 256);
		g.addColorStop(0, 'rgba(0,0,0,0.6)'); // strong enough to read on the dark studio background
		g.addColorStop(0.55, 'rgba(0,0,0,0.22)');
		g.addColorStop(1, 'rgba(0,0,0,0)');
		ctx.setTransform(1, 0, 0, 0.25, 0, 48); // squash the circle into an ellipse
		ctx.fillStyle = g;
		ctx.fillRect(0, 0, 512, 512);

		this.shadowTexture = new THREE.CanvasTexture(c);
		const mesh = new THREE.Mesh(
			new THREE.PlaneGeometry(520 * 1.35, 520 * 0.34),
			new THREE.MeshBasicMaterial({ map: this.shadowTexture, transparent: true, depthWrite: false }),
		);
		mesh.rotation.x = -Math.PI / 2;
		mesh.renderOrder = -1;
		return mesh;
	}

	#initControls() {
		const c = new OrbitControls(this.camera, this.renderer.domElement);
		const touch = window.matchMedia?.('(pointer: coarse)').matches;
		c.enableDamping = true;
		c.dampingFactor = 0.12;
		c.rotateSpeed = touch ? 0.5 : 0.7;
		c.zoomSpeed = 1;
		// The plate stays on its centre: no panning, and zoom always heads for the middle.
		c.zoomToCursor = false;
		c.enablePan = false;
		c.mouseButtons = { LEFT: THREE.MOUSE.ROTATE, MIDDLE: THREE.MOUSE.DOLLY, RIGHT: THREE.MOUSE.ROTATE };
		c.touches = { ONE: THREE.TOUCH.ROTATE, TWO: THREE.TOUCH.DOLLY_ROTATE };
		c.target.set(0, 0, 0);

		c.addEventListener('start', () => {
			this.interacting = true;
			this.tweens.forEach((t) => t.kind === 'camera' && this.tweens.delete(t));
			// The badge close-up aims at the badge; turning from there goes back to the plate's centre.
			if (this.showcased === 'badge') this.#recentre();
			this.container.classList.add('is-dragging');
			this.dispatchEvent(new Event('interactionstart'));
		});
		c.addEventListener('end', () => {
			this.interacting = false;
			this.container.classList.remove('is-dragging');
			// Seen at an angle, the near end of the plate looks bigger, so the plate's centre isn't the
			// middle of what's on screen. Once they let go, slide the pivot so the plate sits centred.
			this.#settleCentre();
			this.requestRender();
		});
		c.addEventListener('change', () => this.requestRender());

		this.controls = c;
		this.#initTaps();
		this.#initWheelGuard();
	}

	/**
	 * Double-tap (phones) or double-click: zoom in on the plate's centre; again to go back to the whole plate.
	 * Phones don't reliably send dblclick, so taps are timed here.
	 */
	#initTaps() {
		const el = this.renderer.domElement;
		let down = null;
		let lastTap = null;
		let handledAt = 0;

		el.addEventListener('pointerdown', (e) => {
			down = e.isPrimary ? { x: e.clientX, y: e.clientY } : null; // a second finger cancels the tap
		});
		el.addEventListener('pointerup', (e) => {
			if (e.pointerType === 'mouse' || !down) return;
			const moved = Math.hypot(e.clientX - down.x, e.clientY - down.y) > 10;
			down = null;
			if (moved) return;
			const now = performance.now();
			if (lastTap && now - lastTap.t < TAP_MS && Math.hypot(e.clientX - lastTap.x, e.clientY - lastTap.y) < TAP_SLOP) {
				lastTap = null;
				handledAt = now;
				this.#toggleZoom();
			} else {
				lastTap = { t: now, x: e.clientX, y: e.clientY };
			}
		});
		el.addEventListener('dblclick', (e) => {
			if (performance.now() - handledAt < 600) return; // the taps above already did it
			this.#toggleZoom();
		});
	}

	#toggleZoom() {
		if (this.#carShown) {
			this.resetView();
			return;
		}
		const zoomedIn = this.#currentView().radius < this.fitDistance * 0.8;
		if (zoomedIn || this.showcased) {
			this.resetView();
			return;
		}
		const from = this.#currentView();
		const toRadius = this.fitDistance * TAP_ZOOM;
		this.dispatchEvent(new Event('interactionstart'));
		this.#tween('camera', ZOOM_MS, (k) => {
			this.#applyView(new THREE.Spherical(THREE.MathUtils.lerp(from.radius, toRadius, k), from.phi, from.theta));
		});
	}

	/**
	 * Desktop: the scroll wheel scrolls the page until the customer clicks the preview (or holds
	 * Ctrl / ⌘, which trackpad pinches also send). Otherwise scrolling past a product page zooms the plate.
	 */
	#initWheelGuard() {
		let armed = false;
		this.renderer.domElement.addEventListener('pointerdown', () => (armed = true));
		this.container.addEventListener('pointerleave', () => (armed = false));
		this.container.addEventListener('wheel', (e) => {
			if (armed || e.ctrlKey || e.metaKey) return;
			e.stopPropagation(); // OrbitControls never sees it, so the page scrolls as normal
			this.dispatchEvent(new Event('wheelblocked'));
		}, { capture: true, passive: true });
	}

	/* -------------------------------------------------------------- framing */

	/** Distance at which the plate fills the frame for the current aspect ratio. */
	#computeFitDistance(aspect) {
		const v = THREE.MathUtils.degToRad(FOV);
		const h = 2 * Math.atan(Math.tan(v / 2) * aspect);
		if (this.#carShown) {
			const r = this.carBox.getBoundingSphere(new THREE.Sphere()).radius;
			// Narrow (phone) frames need more room: the three-quarter view is wider than it is tall.
			const margin = THREE.MathUtils.clamp(1.2 - 0.25 * aspect, 0.74, 1);
			return (r * margin) / Math.sin(Math.min(v, h) / 2);
		}
		const { width, height } = this.plate.plate;
		const byWidth = (width / 2) * FRAME_MARGIN / Math.tan(h / 2);
		const byHeight = (height / 2) * FRAME_MARGIN_V / Math.tan(v / 2);
		return Math.max(byWidth, byHeight) + THICKNESS;
	}

	#defaultView() {
		if (this.#carShown) return new THREE.Spherical(this.fitDistance, Math.PI / 2 - CAR_ELEVATION, CAR_AZIMUTH);
		return new THREE.Spherical(this.fitDistance, Math.PI / 2 - DEFAULT_ELEVATION, DEFAULT_AZIMUTH);
	}

	get #carShown() {
		return this.view === 'car' && Boolean(this.carBox);
	}

	/** Orbit centre for the default view: the plate, or the car (nudged towards the plate). */
	#homeTarget() {
		if (!this.#carShown) return ORIGIN.clone();
		return this.carBox.getCenter(new THREE.Vector3()).lerp(ORIGIN, 0.3);
	}

	#setLimits() {
		const c = this.controls;
		if (this.#carShown) {
			c.minDistance = CAR_MIN_DISTANCE;
			c.maxDistance = this.fitDistance * 1.8;
			c.minAzimuthAngle = -Infinity;
			c.maxAzimuthAngle = Infinity;
			c.minPolarAngle = THREE.MathUtils.degToRad(15);
			c.maxPolarAngle = THREE.MathUtils.degToRad(165);
			return;
		}
		c.minDistance = this.fitDistance * MIN_ZOOM;
		c.maxDistance = this.fitDistance * MAX_ZOOM;
		c.minAzimuthAngle = -Infinity;
		c.maxAzimuthAngle = Infinity;
		c.minPolarAngle = Math.PI / 2 - MAX_TILT;
		c.maxPolarAngle = Math.PI / 2 + MAX_TILT;
	}

	#currentView() {
		return new THREE.Spherical().setFromVector3(this.camera.position.clone().sub(this.controls.target));
	}

	#applyView(spherical) {
		this.camera.position.setFromSpherical(spherical).add(this.controls.target);
		this.camera.lookAt(this.controls.target);
	}

	#resize() {
		const { clientWidth: w, clientHeight: h } = this.container;
		if (!w || !h) return;

		const aspect = w / h;
		const nextFit = this.#computeFitDistance(aspect);

		// First real size frames the default view; later changes keep the user's relative zoom.
		const view = this.framed ? this.#currentView() : null;
		if (view) view.radius *= nextFit / this.fitDistance;
		this.fitDistance = nextFit;
		this.#applyView(view ?? this.#defaultView());
		const firstFrame = !this.framed;
		this.framed = true;
		this.#setLimits();
		if (firstFrame && this.showcased) this.showcase(this.showcased);

		this.camera.aspect = aspect;
		this.camera.updateProjectionMatrix();
		this.renderer.setSize(w, h, false);
		this.requestRender();
	}

	/** Plate changed size: ease the camera to the new framing, keeping the user's angle and relative zoom. */
	#reframe() {
		if (!this.framed) return;
		const nextFit = this.#computeFitDistance(this.camera.aspect);
		const ratio = nextFit / this.fitDistance;
		this.fitDistance = nextFit;
		this.#setLimits();
		if (Math.abs(ratio - 1) < 0.002) return;

		const from = this.#currentView();
		const to = from.radius * ratio;
		this.#tween('frame', FRAME_MS, (k) => {
			const view = this.#currentView();
			view.radius = THREE.MathUtils.lerp(from.radius, to, k);
			this.#applyView(view);
		});
	}

	/* --------------------------------------------------------------- public */

	resetView() {
		this.showcased = false;
		this.#goHome();
	}

	showcaseStyle() {
		this.showcase('style');
	}

	showcaseBadge() {
		this.showcase('badge');
	}

	/**
	 * Ease in to a close-up ('style' or 'badge'), still turning on the plate's centre.
	 * Studio view only; resetView() (or the next step on phones) returns to the default view.
	 */
	showcase(kind = 'style') {
		const view = SHOWCASES[kind];
		if (this.#carShown || !view) return;
		this.showcased = kind;
		// Tapped before the preview had a size (just opened on a phone): run once it's framed.
		if (!this.framed) return;
		const from = this.#currentView();
		const to = new THREE.Spherical(this.fitDistance * view.zoom, Math.PI / 2 - view.elevation, view.azimuth);
		const fromTarget = this.controls.target.clone();
		let toTarget = view.onBadge ? this.#badgeCentre() : ORIGIN.clone();
		if (view.frame) {
			const framed = this.#frameAt(to, { fit: true });
			toTarget = framed.target;
			to.radius = framed.radius;
		}
		let dTheta = to.theta - from.theta;
		dTheta = ((dTheta + Math.PI) % (2 * Math.PI) + 2 * Math.PI) % (2 * Math.PI) - Math.PI;

		this.#tween('camera', SHOWCASE_MS, (k) => {
			this.controls.target.lerpVectors(fromTarget, toTarget, k);
			this.#applyView(new THREE.Spherical(
				THREE.MathUtils.lerp(from.radius, to.radius, k),
				THREE.MathUtils.lerp(from.phi, to.phi, k),
				from.theta + dTheta * k,
			));
		});
	}

	/** Middle of the badge area at the plate's left end (the whole plate's centre if there's no badge). */
	#badgeCentre() {
		const zone = badgeZone(getBadge(this.options.badge), this.size.lines);
		if (!zone) return ORIGIN.clone();
		// Aimed a little below the badge's middle, which lifts it clear of the view buttons at the
		// bottom of the preview so it sits in the middle of what the customer can see.
		return new THREE.Vector3(-this.plate.plate.width / 2 + zone / 2, -this.plate.plate.height * 0.2, THICKNESS);
	}

	/**
	 * Where to aim (and, with fit, how far back to stand) so that from this angle the whole plate is
	 * in the middle of the preview, nearly filling it. The near end of a plate seen at an angle
	 * looks bigger than the far end, so this measures the plate's corners on screen and nudges the
	 * aim until they're centred. The aim is lifted a little to clear the view buttons at the bottom.
	 */
	#frameAt(spherical, { fit = false } = {}) {
		const cam = this.camera.clone();
		const { width, height } = this.plate.plate;
		const corners = [];
		for (const x of [-width / 2, width / 2]) for (const y of [-height / 2, height / 2]) for (const z of [0, 8]) corners.push(new THREE.Vector3(x, y, z));
		const target = ORIGIN.clone();
		let radius = spherical.radius;
		const p = new THREE.Vector3();
		const right = new THREE.Vector3();
		const up = new THREE.Vector3();
		for (let pass = 0; pass < 4; pass++) {
			cam.position.setFromSpherical(new THREE.Spherical(radius, spherical.phi, spherical.theta)).add(target);
			cam.lookAt(target);
			cam.updateMatrixWorld();
			let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
			for (const c of corners) {
				p.copy(c).project(cam);
				minX = Math.min(minX, p.x); maxX = Math.max(maxX, p.x);
				minY = Math.min(minY, p.y); maxY = Math.max(maxY, p.y);
			}
			const halfH = Math.tan(THREE.MathUtils.degToRad(cam.fov / 2)) * radius;
			right.setFromMatrixColumn(cam.matrixWorld, 0);
			up.setFromMatrixColumn(cam.matrixWorld, 1);
			target.addScaledVector(right, ((minX + maxX) / 2) * halfH * cam.aspect);
			target.addScaledVector(up, ((minY + maxY) / 2 - FRAME_LIFT) * halfH);
			if (fit) radius *= Math.max((maxX - minX) / 2 / 0.95, (maxY - minY) / 2 / 0.8);
		}
		return { target, radius };
	}

	/** After a turn: ease the pivot so the plate is centred from the new angle (distance unchanged). */
	#settleCentre() {
		if (this.#carShown || !this.framed || this.showcased === 'badge') return;
		const view = this.#currentView();
		const to = this.#frameAt(view).target;
		const from = this.controls.target.clone();
		if (from.distanceToSquared(to) < 1) return;
		this.#tween('recentre', SETTLE_MS, (k) => {
			const shift = new THREE.Vector3().lerpVectors(from, to, k).sub(this.controls.target);
			this.controls.target.add(shift);
			this.camera.position.add(shift);
		});
	}

	/** The customer started turning: slide the orbit centre back to the middle of the plate. */
	#recentre() {
		if (this.#carShown || this.controls.target.lengthSq() < 1e-6) return;
		const from = this.controls.target.clone();
		this.#tween('recentre', SIDE_MS, (k) => {
			const shift = new THREE.Vector3().lerpVectors(from, ORIGIN, k).sub(this.controls.target);
			this.controls.target.add(shift);
			this.camera.position.add(shift);
		});
	}

	/** Ease to the default view for the current mode (plate or car). */
	#goHome() {
		const from = this.#currentView();
		const to = this.#defaultView();
		// Rotate the short way round, and orbit (not lerp) so we never pass through the plate.
		let dTheta = to.theta - from.theta;
		dTheta = ((dTheta + Math.PI) % (2 * Math.PI) + 2 * Math.PI) % (2 * Math.PI) - Math.PI;

		// Leaving the car view moves the orbit centre; bring it back home too.
		const fromTarget = this.controls.target.clone();
		const toTarget = this.#homeTarget();
		this.#tween('camera', RESET_MS, (k) => {
			this.controls.target.lerpVectors(fromTarget, toTarget, k);
			this.#applyView(new THREE.Spherical(
				THREE.MathUtils.lerp(from.radius, to.radius, k),
				THREE.MathUtils.lerp(from.phi, to.phi, k),
				from.theta + dTheta * k,
			));
		});
	}

	/* ------------------------------------------------------------- on a car */

	/** 'plate' (studio) or 'car' (the plate fitted to a car). Some sizes, like motorbike plates, are plate only. */
	setView(view) {
		view = view === 'car' && this.size.onCar !== false ? 'car' : 'plate';
		if (view === this.view) return;
		this.view = view;
		if (view === 'car') {
			this.#showCar();
		} else {
			this.carToken = null;
			this.carRoot.visible = false;
			this.shadow.visible = true;
			this.#refit();
		}
		this.dispatchEvent(new CustomEvent('viewchange', { detail: { view } }));
	}

	setCar(id) {
		this.carId = getCar(id).id;
		if (this.view === 'car') this.#showCar();
		this.dispatchEvent(new CustomEvent('viewchange', { detail: { view: this.view } }));
	}

	setPaint(id) {
		this.paintId = getPaint(id).id;
		this.#applyPaint();
		this.requestRender();
		this.dispatchEvent(new CustomEvent('viewchange', { detail: { view: this.view } }));
	}

	get cars() {
		return CARS;
	}

	get paints() {
		return PAINTS;
	}

	async #showCar() {
		const token = (this.carToken = Symbol('car'));
		if (!this.car || this.car.id !== this.carId) {
			this.dispatchEvent(new CustomEvent('carloading', { detail: { loading: true } }));
			let car;
			try {
				car = await createCar(this.carId, { assetsUrl: this.assetsUrl, onChange: () => this.requestRender() });
			} catch (e) {
				console.error('[PlateBoss] Could not load car', e);
				this.dispatchEvent(new CustomEvent('carloading', { detail: { loading: false } }));
				if (token === this.carToken) this.setView('plate');
				return;
			}
			this.dispatchEvent(new CustomEvent('carloading', { detail: { loading: false } }));
			if (token !== this.carToken) {
				disposeCar(car); // superseded while loading
				return;
			}
			disposeCar(this.car);
			this.carRoot.clear();
			this.car = car;
			this.carRoot.add(car.group);
			this.#applyPaint();
		}
		if (token !== this.carToken) return;
		this.carRoot.visible = true;
		this.shadow.visible = false;
		this.#placeCar();
		this.#refit();
	}

	#applyPaint() {
		const colour = getPaint(this.paintId).colour;
		this.car?.paint.forEach((m) => m.color.set(colour));
	}

	/** Put the car's front or rear plate mount on the plate (which sits at the origin, facing +z). */
	#placeCar() {
		const front = this.side === 'front';
		const mount = this.car.mounts[front ? 'front' : 'rear'].clone();
		const g = this.car.group;
		g.rotation.y = front ? Math.PI : 0; // turn the car so the end being shown faces the camera
		if (front) mount.set(-mount.x, mount.y, -mount.z);
		g.position.set(-mount.x, -mount.y, -mount.z - THICKNESS / 2 - 1);
		g.updateMatrixWorld(true);
		this.carBox = new THREE.Box3();
		g.traverse((o) => o.isMesh && !o.userData.isShadow && this.carBox.expandByObject(o));
	}

	/** Re-frame for the current mode and ease to its default view. */
	#refit() {
		if (!this.framed) return;
		this.fitDistance = this.#computeFitDistance(this.camera.aspect);
		this.#setLimits();
		this.#goHome();
	}

	setSide(side) {
		side = fixedSide(this.size) ?? side; // e.g. motorbikes are rear (yellow) only, commercial trailers white
		const before = this.size;
		const fromFace = this.plate.faceMaterial.color.clone();
		const fromEdge = this.plate.edgeMaterial.color.clone();
		this.plate.side = side === 'rear' ? 'rear' : 'front';
		const to = PlateModel.coloursFor(this.plate.side);
		const face = new THREE.Color();
		const edge = new THREE.Color();

		this.#tween('side', SIDE_MS, (k) => {
			this.plate.applyColours(face.lerpColors(fromFace, to.face, k), edge.lerpColors(fromEdge, to.edge, k));
		});
		this.dispatchEvent(new CustomEvent('sidechange', { detail: { side: this.plate.side } }));
		// Different front and rear sizes: show this side's plate.
		if (this.size !== before) this.#apply();
		if (this.view === 'car' && this.car && this.carToken) {
			this.#placeCar();
			this.#refit();
		}
	}

	get side() {
		return this.plate.side;
	}

	/* ---------------------------------------------------------- render loop */

	#tween(kind, duration, apply) {
		this.tweens.forEach((t) => t.kind === kind && this.tweens.delete(t));
		if (prefersReducedMotion()) {
			apply(1);
			this.requestRender();
			return;
		}
		this.tweens.add({ kind, apply, start: performance.now(), duration });
		this.requestRender();
	}

	requestRender() {
		if (!this.raf) this.raf = requestAnimationFrame(this.#tick);
	}

	#tick = (now) => {
		this.raf = 0;

		// Controls first so any leftover damping is consumed; tweens then win.
		const settling = this.controls.update();

		for (const t of this.tweens) {
			const k = Math.min(1, (now - t.start) / t.duration);
			t.apply(easeInOutCubic(Math.max(0, k)));
			if (k >= 1) this.tweens.delete(t);
		}

		const typing = this.text?.update(now) ?? false;

		this.renderer.render(this.scene, this.camera);

		if (!this.firstFrame) {
			this.firstFrame = true;
			this.dispatchEvent(new Event('ready'));
		}

		if (settling || typing || this.interacting || this.tweens.size) this.requestRender();
	};

	dispose() {
		cancelAnimationFrame(this.raf);
		this.resizeObserver.disconnect();
		this.controls.dispose();
		this.plate.dispose();
		disposeCar(this.car);
		this.text?.dispose();
		this.shadow.geometry.dispose();
		this.shadow.material.dispose();
		this.shadowTexture.dispose();
		this.envMap.dispose();
		this.renderer.dispose();
		this.renderer.domElement.remove();
	}
}
