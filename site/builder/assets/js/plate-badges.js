/**
 * Badges and borders, matching a typical UK plate range:
 *   Printed: UK badge (Union Flag over a blue "UK"), EV strip (green flash down the left edge)
 *   Gel:     42 x 98 mm domed stickers, in four designs:
 *              UK white  flag on its side, black divider, black "UK" on white
 *              UK blue   flag on its side, white "UK" on blue
 *              EV        plain green (zero-emission only)
 *              EV + UK   green, small flag, white "UK"
 *   4D:      UK blue artwork on a raised 3 mm acrylic tile
 *
 * UK badges: Standard, Short, Oversized (rear), Motorbike and Trailer.
 * EV strips: Standard, Short and Trailer.
 *
 * On single-line plates the badge zone runs the full height of the left side.
 * On two-line plates (Motorbike) it sits beside the bottom line only, and the
 * badge is narrower, as UK plate makers produce them.
 */

const UK_SIZES = ['standard', 'short', 'oversized', 'motorbike', 'trailer'];
const EV_SIZES = ['standard', 'short', 'trailer'];

export const BADGES = [
	{ id: 'none', label: 'No badge', sizes: null },
	{ id: 'uk', label: 'UK', finish: 'printed', uk: true, sizes: UK_SIZES },
	{ id: 'uk-gel', label: 'UK white', finish: 'gel', uk: true, design: 'uk-white', sizes: UK_SIZES },
	{ id: 'uk-gel-blue', label: 'UK blue', finish: 'gel', uk: true, design: 'uk-blue', sizes: UK_SIZES },
	{ id: 'uk-4d', label: 'UK 4D', finish: '4d', uk: true, design: 'uk-blue', sizes: UK_SIZES },
	{ id: 'ev', label: 'EV strip', finish: 'printed', ev: true, sizes: EV_SIZES },
	{ id: 'ev-gel', label: 'EV', finish: 'gel', ev: true, design: 'ev', sizes: EV_SIZES },
	{ id: 'ev-uk-gel', label: 'EV + UK', finish: 'gel', ev: true, uk: true, design: 'ev-uk', sizes: EV_SIZES },
];

export const getBadge = (id) => BADGES.find((b) => b.id === id) ?? BADGES[0];
export const badgeAvailable = (badge, sizeId) => !badge.sizes || badge.sizes.includes(sizeId);

/** Badge measurements in mm (legal max width 50 mm). */
export const BADGE = {
	inset: 4, // gap from the plate's left edge
	width: 44, // single-line plates
	widthTwoLine: 30, // motorbike: beside the bottom line only
	flagRatio: 0.58, // the flag as plate makers print it, slightly cropped from 2:1
	identColour: '#1550A8', // blue "UK"
	identOnGreen: '#FFFFFF',
	evWidth: 18, // EV strip on its own
	evColour: '#00A650',
	gelHeight: 1.6, // resin dome over gel badges
	raised: 3, // 4D badges: laser-cut acrylic tile
};

/** Gel badges: domed stickers, 42 x 98 mm as supplied (scaled down to fit smaller spaces). */
export const GEL = {
	width: 42,
	height: 98,
	radius: 5,
	flagShare: 0.66, // UK designs: the flag fills the top two-thirds
	divider: 0.8, // black line above the white "UK" panel
	white: '#FFFFFF',
	black: '#111111',
	blue: '#012169',
	green: '#00A650',
};

export const badgeWidth = (lines) => (lines === 2 ? BADGE.widthTwoLine : BADGE.width);

/** Gel sticker width: full size beside a single line, narrower beside a motorbike's bottom line. */
export const gelWidth = (lines) => Math.min(GEL.width, badgeWidth(lines));

/** Width of the left zone characters must keep clear of. */
export function badgeZone(badge, lines) {
	if (badge.design || badge.uk) return BADGE.inset + badgeWidth(lines);
	return badge.ev ? BADGE.evWidth : 0;
}

/** Borders: black, printed just inside the plate edge, in three thicknesses. */
export const BORDER = { inset: 3, colour: '#111111' };
export const BORDERS = [
	{ id: 'none', label: 'No border', roadLegal: true },
	{ id: 'black-1mm', label: '1 mm', width: 1, colour: BORDER.colour, roadLegal: true },
	{ id: 'black-2mm', label: '2 mm', width: 2, colour: BORDER.colour, roadLegal: true },
	{ id: 'black-3mm', label: '3 mm', width: 3, colour: BORDER.colour, roadLegal: true },
];
export const getBorder = (id) => BORDERS.find((b) => b.id === id) ?? BORDERS[0];

/* ------------------------------------------------------------------ artwork */

export const UNION_FLAG = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="1200" height="600">
<clipPath id="pbb-s"><path d="M0,0v30h60v-30z"/></clipPath>
<clipPath id="pbb-t"><path d="M30,15h30v15zv15h-30zh-30v-15zv-15h30z"/></clipPath>
<g clip-path="url(#pbb-s)"><path d="M0,0v30h60v-30z" fill="#012169"/>
<path d="M0,0L60,30M60,0L0,30" stroke="#fff" stroke-width="6"/>
<path d="M0,0L60,30M60,0L0,30" clip-path="url(#pbb-t)" stroke="#C8102E" stroke-width="4"/>
<path d="M30,0v30M0,15h60" stroke="#fff" stroke-width="10"/>
<path d="M30,0v30M0,15h60" stroke="#C8102E" stroke-width="6"/></g></svg>`;

let flagImage;

/** The Union Flag as an <img>, loaded once. `override` = production artwork URL. */
export function loadFlagImage(override) {
	flagImage ??= new Promise((resolve, reject) => {
		const img = new Image();
		img.crossOrigin = 'anonymous';
		img.onload = () => resolve(img);
		img.onerror = reject;
		img.src = override || URL.createObjectURL(new Blob([UNION_FLAG], { type: 'image/svg+xml' }));
	});
	return flagImage;
}
