/**
 * Character styles, defined as stacks of layers so new styles are data, not code.
 *
 * Layer fields (all mm):
 *   kind     'print' = flat ink on the face | 'solid' = raised geometry
 *   profile  solid only: 'square' (laser-cut edge) | 'dome' (gel resin) | 'pyramid' (chamfered ridge)
 *   height   how far a solid stands off its base
 *   z        lift above the face (4D Gel stacks its gel dome on top of the acrylic)
 *   inset    how far a dome/pyramid profile pulls in from the outline at the top
 *   grow     expand the outline outwards
 *   shift    [x, y] offset
 *   finish   material: 'ink' | 'gel' | 'acrylic' | 'accent'
 *
 * Every style keeps the character outline at legal size. Under BS AU 145e (2021) characters
 * must be a single solid black; raised 3D/4D is allowed. The engine still supports coloured
 * 'accent' layers (with the ACCENTS below), but the builder offers no coloured styles yet.
 */

export const STYLES = [
	{
		id: 'printed',
		label: 'Printed',
		description: 'Classic flat print, sealed under the acrylic.',
		roadLegal: true,
		layers: [{ kind: 'print', finish: 'ink' }],
	},
	{
		id: 'gel-3d',
		label: '3D Gel',
		description: 'Raised, domed resin with a high-gloss finish.',
		roadLegal: true,
		layers: [{ kind: 'solid', profile: 'dome', height: 2.2, inset: 4, finish: 'gel' }],
	},
	{
		id: 'acrylic-4d-3',
		label: '4D 3mm',
		description: 'Laser-cut acrylic characters, 3 mm deep with crisp edges.',
		roadLegal: true,
		layers: [{ kind: 'solid', profile: 'square', height: 3, finish: 'acrylic' }],
	},
	{
		id: 'acrylic-4d-6',
		label: '4D 6mm',
		description: 'Two layers of 3 mm laser-cut acrylic, 6 mm deep for maximum depth.',
		roadLegal: true,
		layers: [{ kind: 'solid', profile: 'square', height: 6, finish: 'acrylic' }],
	},
	{
		id: 'gel-4d-3',
		label: '4D Gel 3mm',
		description: '3 mm laser-cut acrylic characters topped with domed 3D gel.',
		roadLegal: true,
		layers: [
			{ kind: 'solid', profile: 'square', height: 3, finish: 'acrylic' },
			{ kind: 'solid', profile: 'dome', height: 1.8, inset: 4, z: 3, finish: 'gel' },
		],
	},
	{
		id: 'gel-4d-6',
		label: '4D Gel 6mm',
		description: 'Two layers of 3 mm acrylic (6 mm) topped with domed 3D gel.',
		roadLegal: true,
		layers: [
			{ kind: 'solid', profile: 'square', height: 6, finish: 'acrylic' },
			{ kind: 'solid', profile: 'dome', height: 1.8, inset: 4, z: 6, finish: 'gel' },
		],
	},
	{
		id: 'retro',
		label: 'Retro',
		description: 'Chamfered pyramid profile, like classic pressed-metal plates.',
		roadLegal: true, // all black
		layers: [{ kind: 'solid', profile: 'pyramid', height: 5, inset: 6, wall: 0.8, finish: 'acrylic' }],
	},
];

export const ACCENTS = [
	{ id: 'red', label: 'Red', colour: '#D3202A' },
	{ id: 'blue', label: 'Blue', colour: '#1D4ED8' },
	{ id: 'green', label: 'Green', colour: '#159447' },
	{ id: 'grey', label: 'Grey', colour: '#6B7079' },
	{ id: 'silver', label: 'Silver', colour: '#D2D6DB', metal: true },
	{ id: 'gold', label: 'Gold', colour: '#D4A93C', metal: true },
];

export const getStyle = (id) => STYLES.find((s) => s.id === id) ?? STYLES[0];
export const getAccent = (id) => ACCENTS.find((a) => a.id === id) ?? ACCENTS[0];
