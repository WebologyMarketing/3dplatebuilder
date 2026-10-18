/**
 * Live price. Mirrors pbb_calculate_price() in includes/catalog.php; the server
 * recalculates the basket price, so this is for display only.
 *
 * Standard sizes: style price for the pair or one plate, plus each plate's own size
 * surcharge (front and rear can be different sizes). Motorbike and trailer plates: one fixed price.
 * Badge, border and extras are charged once (badge at half price on motorbike and trailer plates).
 */
import { getSize } from './plate-sizes.js';
import { getStyle } from './plate-styles.js';
import { getBadge, getBorder } from './plate-badges.js';

const num = (v) => (Number.isFinite(Number(v)) ? Number(v) : 0);
const round2 = (v) => Math.round(v * 100) / 100;

/**
 * @param {object} config   viewport.getConfig() plus { plates, accessories }
 * @param {object} pricing  price list from the builder config
 * @param {object} labels   catalogue labels from the server (styles, sizes, badges, borders, accessories)
 * @returns {{ lines: {label:string, amount:number}[], total:number }}
 */
export function calculatePrice(config, pricing, labels = {}) {
	const p = pricing ?? {};
	const size = getSize(config.size);
	const style = getStyle(config.style);
	const badge = getBadge(config.badge);
	const border = getBorder(config.border);
	const name = (group, id, fallback) => labels[group]?.[id] ?? fallback;
	const flat = p.flat && size.id in p.flat;
	const plates = config.plates === 'pair' ? 2 : 1;
	const lines = [];

	if (flat) {
		lines.push({ label: `${name('sizes', size.id, size.label)} plate`, amount: num(p.flat[size.id]) });
	} else {
		const styleName = name('styles', style.id, style.label);
		lines.push(plates === 2
			? { label: `${styleName} plates, front & rear`, amount: num(p.style_pair?.[style.id]) }
			: { label: `${styleName} plate, ${config.plates} only`, amount: num(p.style_single?.[style.id]) });
		const rear = getSize(config.rearSize ?? size.id);
		const front$ = config.plates === 'rear' ? 0 : num(p.size?.[size.id]);
		const rear$ = config.plates === 'front' ? 0 : num(p.size?.[rear.id]);
		if (plates === 2 && rear.id === size.id) {
			if (front$ + rear$) lines.push({ label: `${name('sizes', size.id, size.label)} size × 2`, amount: front$ + rear$ });
		} else {
			if (front$) lines.push({ label: `Front: ${name('sizes', size.id, size.label)} size`, amount: front$ });
			if (rear$) lines.push({ label: `Rear: ${name('sizes', rear.id, rear.label)} size`, amount: rear$ });
		}
	}
	if (badge.id !== 'none') {
		const amount = num(p.badge?.[badge.id]);
		lines.push({ label: name('badges', badge.id, badge.label), amount: flat ? amount / 2 : amount });
	}
	if (border.width) {
		lines.push({ label: `${name('borders', border.id, border.label)} border`, amount: num(p.border?.[border.id]) });
	}
	for (const a of config.accessories ?? []) {
		lines.push({ label: name('accessories', a, a), amount: num(p.accessories?.[a]) });
	}
	// Paid at checkout (a checkout fee, not part of the plate's basket price).
	if (config.fitting) lines.push({ label: 'In-store fitting (added at checkout)', amount: num(p.services?.fitting ?? 30), atCheckout: true });

	const total = round2(Math.max(0, lines.reduce((t, l) => t + l.amount, 0)));
	return { lines: lines.map((l) => ({ ...l, amount: round2(l.amount) })), total };
}
