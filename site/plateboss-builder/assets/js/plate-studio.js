import * as THREE from 'three';

/**
 * Reflections for the plate: a detailing studio lit by honeycomb (hexagon) tube lights, like the
 * ones on paint-shop and detailing ceilings. Only used to build the environment map, so the
 * hexagons show up as reflections in the plate's gloss and the characters as it turns.
 *
 * The plate faces +z, so a front-on view reflects the space behind the viewer: the honeycomb
 * runs across the ceiling and down that wall.
 */

const ROOM = { w: 24, h: 12, d: 24 };
const HEX_R = 1.9; // cell radius (centre to corner): big panels, like a detailing-bay ceiling
const TUBE = 0.085; // tube width, as a fraction of the cell radius
const BRIGHT = 9; // HDR brightness of the tubes

/** Hexagon rings laid out as a honeycomb across a w × h rectangle, in its local x/y plane. */
function honeycomb(w, h, material) {
	const ring = new THREE.RingGeometry(HEX_R * (1 - TUBE), HEX_R, 6, 1, Math.PI / 6);
	const dx = Math.sqrt(3) * HEX_R;
	const dy = 1.5 * HEX_R;
	const cells = [];
	for (let row = 0, y = -h / 2; y <= h / 2; row++, y += dy) {
		for (let x = -w / 2 + (row % 2) * (dx / 2); x <= w / 2; x += dx) cells.push([x, y]);
	}
	const mesh = new THREE.InstancedMesh(ring, material, cells.length);
	const m = new THREE.Matrix4();
	cells.forEach(([x, y], i) => mesh.setMatrixAt(i, m.makeTranslation(x, y, 0)));
	return mesh;
}

export function createDetailingStudio() {
	const scene = new THREE.Scene();

	// Dim grey room: enough fill that the plate never reflects pure black.
	const walls = new THREE.Mesh(
		new THREE.BoxGeometry(ROOM.w, ROOM.h, ROOM.d),
		new THREE.MeshBasicMaterial({ color: new THREE.Color(0.11, 0.115, 0.125), side: THREE.BackSide }),
	);
	scene.add(walls);

	const floor = new THREE.Mesh(
		new THREE.PlaneGeometry(ROOM.w, ROOM.d),
		new THREE.MeshBasicMaterial({ color: new THREE.Color(0.05, 0.05, 0.055) }),
	);
	floor.rotation.x = -Math.PI / 2;
	floor.position.y = -ROOM.h / 2 + 0.01;
	scene.add(floor);

	const tubes = new THREE.MeshBasicMaterial({ color: new THREE.Color(BRIGHT, BRIGHT, BRIGHT * 1.03), side: THREE.DoubleSide });

	// Ceiling, facing down.
	const ceiling = honeycomb(ROOM.w * 0.9, ROOM.d * 0.9, tubes);
	ceiling.rotation.x = Math.PI / 2;
	ceiling.position.y = ROOM.h / 2 - 0.02;
	scene.add(ceiling);

	// The wall behind the viewer, so a front-on view catches the honeycomb too.
	const front = honeycomb(ROOM.w * 0.9, ROOM.h * 0.8, tubes);
	front.rotation.y = Math.PI;
	front.position.z = ROOM.d / 2 - 0.02;
	front.position.y = ROOM.h * 0.08;
	scene.add(front);

	scene.dispose = () => {
		scene.traverse((o) => {
			o.geometry?.dispose();
			o.material?.dispose?.();
		});
	};
	return scene;
}
