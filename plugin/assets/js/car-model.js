import * as THREE from 'three';

/**
 * "On a car" view: the cars a customer can see their plate on. 1 unit = 1 mm.
 *
 * Every car, built-in or loaded, comes out in the same frame so the viewport
 * can treat them alike:
 *   length along z (rear at +z, front at -z), width along x, ground at y = 0,
 *   `mounts.rear` / `mounts.front` = centre of each plate's face position,
 *   `paint` = materials recoloured by the paint swatches.
 *
 * No maker badges are shown: every car carries the PB badge instead.
 *
 * Real models (GLB) go in `CARS` with a `url`. Name empties `plate_rear`,
 * `plate_front` and `badge_*` in the model to place the plates and badges
 * exactly; without them the plates go at sensible bumper heights.
 */

export const CARS = [
	{ id: 'sports-saloon', label: 'Sports saloon', build: buildSportsSaloon },
	// Purchased models, once converted to GLB:
	// { id: 'hot-hatch', label: 'Hot hatch', url: 'models/hot-hatch.glb' },
];

export const PAINTS = [
	{ id: 'nardo', label: 'Grey', colour: '#7C8186' },
	{ id: 'black', label: 'Black', colour: '#0E0F11' },
	{ id: 'white', label: 'White', colour: '#E9EBEC' },
	{ id: 'silver', label: 'Silver', colour: '#B9BDC1' },
	{ id: 'blue', label: 'Blue', colour: '#1D3E8F' },
	{ id: 'red', label: 'Red', colour: '#A3141C' },
];

export const getCar = (id) => CARS.find((c) => c.id === id) ?? CARS[0];
export const getPaint = (id) => PAINTS.find((p) => p.id === id) ?? PAINTS[0];

/** Badge artwork loaded once and shared by every car. */
let badgeTexture;
let sharedBadge;
function loadBadgeTexture(url) {
	badgeTexture ??= new Promise((resolve) => {
		const img = new Image();
		img.onload = () => {
			const c = document.createElement('canvas');
			c.width = 500;
			c.height = 300;
			c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
			const tex = new THREE.CanvasTexture(c);
			tex.colorSpace = THREE.SRGBColorSpace;
			tex.anisotropy = 8;
			sharedBadge = tex;
			resolve(tex);
		};
		img.onerror = () => resolve(null);
		img.src = url;
	});
	return badgeTexture;
}

/** PB badge (an oval decal), facing +z in its own frame. */
function pbBadge(width, texturePromise, onChange) {
	const mat = new THREE.MeshPhysicalMaterial({
		transparent: true,
		alphaTest: 0.4,
		metalness: 0.2,
		roughness: 0.25,
		clearcoat: 1,
		clearcoatRoughness: 0.05,
		polygonOffset: true,
		polygonOffsetFactor: -2,
		polygonOffsetUnits: -2,
	});
	texturePromise.then((tex) => {
		if (!tex) return;
		mat.map = tex;
		mat.needsUpdate = true;
		onChange();
	});
	return new THREE.Mesh(new THREE.PlaneGeometry(width, width * 0.6), mat);
}

/* ------------------------------------------------------------ stand-in car */

const L = 4720; // length
const W = 1880; // width
const WHEEL_R = 345;
const AXLE = 1430; // half wheelbase
const ARCH_R = 395;

/** Side profile, rear (-u) to front (+u), with the wheel arches cut out. Low, fastback sports saloon. */
function bodyProfile() {
	const s = new THREE.Shape();
	s.moveTo(-2320, 200);
	s.lineTo(-AXLE - ARCH_R, 200);
	s.absarc(-AXLE, 340, ARCH_R, Math.PI + 0.36, -0.36, true);
	s.lineTo(AXLE - ARCH_R, 200);
	s.absarc(AXLE, 340, ARCH_R, Math.PI + 0.36, -0.36, true);
	s.lineTo(2300, 200);
	s.quadraticCurveTo(2368, 215, 2370, 320);
	s.lineTo(2362, 640);
	s.quadraticCurveTo(2350, 730, 2180, 770); // rounded nose into the bonnet
	s.quadraticCurveTo(1700, 840, 1180, 895); // long, falling bonnet
	s.lineTo(-1820, 905); // beltline
	s.quadraticCurveTo(-2230, 900, -2330, 820); // short boot deck rolling into the tail
	s.quadraticCurveTo(-2392, 760, -2390, 600);
	s.lineTo(-2385, 330);
	s.quadraticCurveTo(-2378, 215, -2320, 200);
	return s;
}

function greenhouseProfile() {
	const s = new THREE.Shape();
	s.moveTo(-1900, 895);
	s.quadraticCurveTo(-1350, 1180, -900, 1300); // fastback rear screen
	s.quadraticCurveTo(-300, 1350, 250, 1330);
	s.quadraticCurveTo(650, 1290, 1200, 890); // raked windscreen
	s.lineTo(-1900, 895);
	return s;
}

/** Extrude a side profile across the car's width and turn it into the car frame. */
function sideExtrude(shape, width, bevel, curveSegments = 24) {
	const geo = new THREE.ExtrudeGeometry(shape, {
		depth: width - 2 * bevel,
		bevelEnabled: true,
		bevelThickness: bevel,
		bevelSize: bevel,
		bevelOffset: -bevel,
		bevelSegments: 5,
		curveSegments,
	});
	geo.translate(0, 0, -(width - 2 * bevel) / 2);
	geo.rotateY(Math.PI / 2); // profile u -> -z (front at -z), extrusion -> x
	geo.computeVertexNormals();
	return geo;
}

function wheel(materials) {
	const g = new THREE.Group();
	const tyre = new THREE.Mesh(new THREE.CylinderGeometry(WHEEL_R, WHEEL_R, 250, 40, 1), materials.tyre);
	tyre.rotation.z = Math.PI / 2;
	const rim = new THREE.Mesh(new THREE.CylinderGeometry(WHEEL_R * 0.66, WHEEL_R * 0.66, 254, 40, 1), materials.rim);
	rim.rotation.z = Math.PI / 2;
	const hub = new THREE.Mesh(new THREE.CylinderGeometry(WHEEL_R * 0.2, WHEEL_R * 0.2, 258, 20, 1), materials.trim);
	hub.rotation.z = Math.PI / 2;
	// Five spokes so the wheel reads as a wheel, not a disc.
	for (let i = 0; i < 5; i++) {
		const spoke = new THREE.Mesh(new THREE.BoxGeometry(258, WHEEL_R * 0.5, 34), materials.trim);
		spoke.rotation.x = (i / 5) * Math.PI * 2;
		spoke.position.set(0, Math.cos(spoke.rotation.x) * WHEEL_R * 0.36, Math.sin(spoke.rotation.x) * WHEEL_R * 0.36);
		g.add(spoke);
	}
	g.add(tyre, rim, hub);
	return g;
}

/** A generic, unbranded four-door sports saloon, built from simple shapes. */
function buildSportsSaloon({ badgeUrl, onChange }) {
	const paint = new THREE.MeshPhysicalMaterial({ color: PAINTS[0].colour, metalness: 0.55, roughness: 0.32, clearcoat: 1, clearcoatRoughness: 0.04 });
	const materials = {
		glass: new THREE.MeshPhysicalMaterial({ color: '#0b0d10', metalness: 0.1, roughness: 0.05, clearcoat: 1, clearcoatRoughness: 0.02 }),
		trim: new THREE.MeshPhysicalMaterial({ color: '#0d0e10', metalness: 0.3, roughness: 0.35, clearcoat: 0.6 }),
		tyre: new THREE.MeshStandardMaterial({ color: '#141516', roughness: 0.92 }),
		rim: new THREE.MeshPhysicalMaterial({ color: '#c9ccd0', metalness: 1, roughness: 0.22 }),
		tail: new THREE.MeshStandardMaterial({ color: '#5a0508', emissive: '#d0101c', emissiveIntensity: 0.9, roughness: 0.3 }),
		head: new THREE.MeshStandardMaterial({ color: '#f4f7fb', emissive: '#e8f1ff', emissiveIntensity: 0.7, roughness: 0.2 }),
	};

	const car = new THREE.Group();
	car.add(new THREE.Mesh(sideExtrude(bodyProfile(), W, 70), paint));
	car.add(new THREE.Mesh(sideExtrude(greenhouseProfile(), W - 170, 90), materials.glass));

	// Wheels.
	for (const z of [-AXLE, AXLE]) {
		for (const side of [-1, 1]) {
			const w = wheel(materials);
			w.position.set(side * (W / 2 - 150), WHEEL_R, z);
			car.add(w);
		}
	}

	// Rear: light bar across the boot, black diffuser.
	const tail = new THREE.Mesh(new THREE.BoxGeometry(W - 300, 34, 24), materials.tail);
	tail.position.set(0, 735, 2382);
	tail.rotation.x = -0.45;
	const diffuser = new THREE.Mesh(new THREE.BoxGeometry(W - 420, 120, 30), materials.trim);
	diffuser.position.set(0, 265, 2382);

	// Front: lower grille behind the plate, slim headlights.
	const grille = new THREE.Mesh(new THREE.BoxGeometry(1180, 300, 16), materials.trim);
	grille.position.set(0, 445, -2362);
	const heads = [-1, 1].map((side) => {
		const h = new THREE.Mesh(new THREE.BoxGeometry(420, 44, 24), materials.head);
		h.position.set(side * 620, 690, -2330);
		h.rotation.x = 0.6;
		return h;
	});
	car.add(tail, diffuser, grille, ...heads);

	// The shop's badge, front and rear (none if the shop hasn't set one).
	if (badgeUrl) {
		const tex = loadBadgeTexture(badgeUrl);
		const rearBadge = pbBadge(130, tex, onChange);
		rearBadge.position.set(0, 625, 2393);
		const frontBadge = pbBadge(130, tex, onChange);
		frontBadge.position.set(0, 640, -2367);
		frontBadge.rotation.y = Math.PI;
		car.add(rearBadge, frontBadge);
	}

	car.traverse((o) => {
		if (o.isMesh) o.castShadow = o.receiveShadow = false;
	});

	return {
		group: car,
		paint: [paint],
		mounts: {
			rear: new THREE.Vector3(0, 470, 2390),
			front: new THREE.Vector3(0, 445, -2371),
		},
	};
}

/* ------------------------------------------------------------- GLB models */

let gltfLoader;

/** Load a purchased model, scale it to mm, sit it on the ground facing the right way, and find its mounts. */
async function loadGlbCar(url, { badgeUrl, onChange }) {
	if (!gltfLoader) {
		const { GLTFLoader } = await import('three/addons/loaders/GLTFLoader.js');
		gltfLoader = new GLTFLoader();
	}
	const gltf = await gltfLoader.loadAsync(url);
	const car = new THREE.Group();
	car.add(gltf.scene);

	// Models come in metres (or cm); our scene is mm.
	let box = new THREE.Box3().setFromObject(gltf.scene);
	const size = box.getSize(new THREE.Vector3());
	const longest = Math.max(size.x, size.z);
	const scale = longest < 20 ? 1000 : longest < 2000 ? 10 : 1;
	gltf.scene.scale.multiplyScalar(scale);
	// Long axis along z.
	if (size.x > size.z) gltf.scene.rotation.y = Math.PI / 2;
	gltf.scene.updateMatrixWorld(true);
	box = new THREE.Box3().setFromObject(gltf.scene);
	const centre = box.getCenter(new THREE.Vector3());
	gltf.scene.position.sub(new THREE.Vector3(centre.x, box.min.y, centre.z));
	car.updateMatrixWorld(true);
	box = new THREE.Box3().setFromObject(car);

	const paint = [];
	const mounts = {};
	const badges = [];
	car.traverse((o) => {
		const name = (o.name || '').toLowerCase();
		if (name === 'plate_rear' || name === 'plate_front') mounts[name.slice(6)] = o.getWorldPosition(new THREE.Vector3());
		if (name.startsWith('badge_')) badges.push(o);
		if (o.isMesh) {
			o.castShadow = o.receiveShadow = false;
			const mats = Array.isArray(o.material) ? o.material : [o.material];
			mats.forEach((m) => /paint|body|carpaint/i.test(m.name) && !paint.includes(m) && paint.push(m));
		}
	});

	// No mount empties: plates at typical bumper heights, just proud of the bodywork.
	mounts.rear ??= new THREE.Vector3(0, 520, box.max.z + 2);
	mounts.front ??= new THREE.Vector3(0, 450, box.min.z - 2);

	if (badgeUrl) {
		const tex = loadBadgeTexture(badgeUrl);
		badges.forEach((empty) => empty.add(pbBadge(130, tex, onChange)));
	}

	return { group: car, paint, mounts };
}

/** Build or load a car. Resolves to { group, paint, mounts }. */
export async function createCar(id, { assetsUrl = '', badgeUrl = '', onChange = () => {} } = {}) {
	const def = getCar(id);
	const opts = { badgeUrl, onChange };
	const car = def.url ? await loadGlbCar(`${assetsUrl}${def.url}`, opts) : def.build(opts);
	car.id = def.id;
	car.group.add(carShadow());
	car.box = new THREE.Box3().setFromObject(car.group);
	return car;
}

/** Soft baked shadow under the car. */
function carShadow() {
	const c = document.createElement('canvas');
	c.width = 256;
	c.height = 512;
	const ctx = c.getContext('2d');
	const g = ctx.createRadialGradient(128, 256, 0, 128, 256, 128);
	g.addColorStop(0, 'rgba(0,0,0,0.75)');
	g.addColorStop(0.7, 'rgba(0,0,0,0.45)');
	g.addColorStop(1, 'rgba(0,0,0,0)');
	ctx.setTransform(1, 0, 0, 2, 0, -256); // stretch the circle along the car
	ctx.fillStyle = g;
	ctx.fillRect(0, 0, 256, 512);
	const tex = new THREE.CanvasTexture(c);
	const mesh = new THREE.Mesh(
		new THREE.PlaneGeometry(W * 1.3, L * 1.12),
		new THREE.MeshBasicMaterial({ map: tex, transparent: true, depthWrite: false }),
	);
	mesh.rotation.x = -Math.PI / 2;
	mesh.position.y = 2;
	mesh.renderOrder = -1;
	mesh.userData.isShadow = true;
	return mesh;
}

export function disposeCar(car) {
	car?.group.traverse((o) => {
		if (!o.isMesh) return;
		o.geometry.dispose();
		(Array.isArray(o.material) ? o.material : [o.material]).forEach((m) => {
			if (m.map && m.map !== sharedBadge) m.map.dispose();
			m.dispose();
		});
	});
}
