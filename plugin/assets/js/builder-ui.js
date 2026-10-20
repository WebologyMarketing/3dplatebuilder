import { ACCENTS, STYLES } from './plate-styles.js';
import { PLATE_SIZES, styleAvailable } from './plate-sizes.js';
import { BADGES, BORDERS, UNION_FLAG, badgeAvailable, getBorder } from './plate-badges.js';

/**
 * Option pickers rendered from the style data, so the UI always matches
 * what the 3D engine supports. Icons are side-on cross-sections of each profile.
 */

const PLATE = '<path d="M2 16.5H38" stroke="currentColor" stroke-opacity=".3" stroke-width="1.5"/>';
const INK = 'fill="currentColor"';
const ACC = 'fill="var(--pbb-accent-swatch, #c8ccd1)"';

const PROFILE_ICONS = {
	'printed': `<rect x="10" y="14" width="20" height="2" ${INK}/>`,
	'gel-3d': `<path d="M10 16C10 9.5 13 9 20 9s10 .5 10 7Z" ${INK}/>`,
	'acrylic-4d-3': `<rect x="10" y="11" width="20" height="5" ${INK}/>`,
	'acrylic-4d-6': `<rect x="10" y="6" width="20" height="10" ${INK}/>`,
	'gel-4d-3': `<rect x="10" y="11" width="20" height="5" ${INK}/><path d="M10 11C10 6.5 13 6 20 6s10 .5 10 5Z" ${INK} opacity=".55"/>`,
	'gel-4d-6': `<rect x="10" y="6" width="20" height="10" ${INK}/><path d="M10 6C10 1.5 13 1 20 1s10 .5 10 5Z" ${INK} opacity=".55"/>`,
	'retro': `<path d="M10 16v-1.5L19 8h2l9 6.5V16Z" ${INK}/>`,
};

const icon = (id) => `<svg class="pbb-style-icon" viewBox="0 0 40 20" aria-hidden="true">${PLATE}${PROFILE_ICONS[id] ?? ''}</svg>`;

/** Radio-group keyboard support: arrows move and select, like native radios. */
// What this shop sells (config.offer from the server: id lists per group). Null = everything.
// Printed, Standard and "None" are always offered: the builder falls back to them.
let offer = null;
export function setOffer(o) { offer = o && typeof o === 'object' ? o : null; }
const ALWAYS = ['printed', 'standard', 'none'];
const offered = (group) => (item) => ALWAYS.includes(item.id) || !Array.isArray(offer?.[group]) || offer[group].includes(item.id);

function arrowNavigation(group, onPick) {
	group.addEventListener('keydown', (e) => {
		const dir = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[e.key];
		if (!dir) return;
		const items = [...group.querySelectorAll('[role="radio"]')].filter((el) => !el.hidden && !el.disabled);
		const i = items.indexOf(document.activeElement);
		if (i < 0) return;
		e.preventDefault();
		const next = items[(i + dir + items.length) % items.length];
		next.focus();
		onPick(next);
	});
}

function syncRadios(group, attr, value) {
	group?.querySelectorAll('[role="radio"]').forEach((el) => {
		const on = el.dataset[attr] === value;
		el.setAttribute('aria-checked', String(on));
		el.tabIndex = on ? 0 : -1;
	});
}

export function mountStylePicker(root, viewport) {
	const styleGroup = root.querySelector('[data-pbb-styles]');
	const accentGroup = root.querySelector('[data-pbb-accents]');
	const accentSection = root.querySelector('[data-pbb-accent-section]');
	const desc = root.querySelector('[data-pbb-output="style-desc"]');
	if (!styleGroup) return;

	styleGroup.innerHTML = STYLES.filter(offered('styles')).map((s) => `
		<button type="button" role="radio" class="pbb-style" data-style="${s.id}" aria-checked="false"
			aria-label="${s.label}${s.roadLegal ? '' : ' (show plate)'}">
			${icon(s.id)}
			<span class="pbb-style-name">${s.label}</span>
		</button>`).join('');

	if (accentGroup) {
		accentGroup.innerHTML = ACCENTS.map((a) => `
			<button type="button" role="radio" class="pbb-swatch${a.metal ? ' is-metal' : ''}" data-accent="${a.id}"
				style="--swatch:${a.colour}" aria-checked="false" aria-label="${a.label}" title="${a.label}"></button>`).join('');
	}

	// A customer's pick (not a preset) also swings the camera round to show the style's profile.
	const pickStyle = (el) => {
		if (el.hidden) return;
		viewport.setStyle(el.dataset.style);
		viewport.showcaseStyle();
	};
	const pickAccent = (el) => viewport.setAccent(el.dataset.accent);

	styleGroup.addEventListener('click', (e) => {
		const el = e.target.closest('[data-style]');
		if (el) pickStyle(el);
	});
	accentGroup?.addEventListener('click', (e) => {
		const el = e.target.closest('[data-accent]');
		if (el) pickAccent(el);
	});
	arrowNavigation(styleGroup, pickStyle);
	if (accentGroup) arrowNavigation(accentGroup, pickAccent);

	const render = ({ style, accent }) => {
		// Some sizes are printed only for now: hide the rest (either plate's size counts).
		const sizes = [viewport.sizeFor('front'), viewport.sizeFor('rear')];
		const size = sizes.find((s) => s.styles) ?? viewport.size;
		styleGroup.querySelectorAll('[data-style]').forEach((el) => {
			el.hidden = !sizes.every((s) => styleAvailable(s, el.dataset.style));
		});
		syncRadios(styleGroup, 'style', style.id);
		syncRadios(accentGroup, 'accent', accent);
		root.style.setProperty('--pbb-accent-swatch', ACCENTS.find((a) => a.id === accent)?.colour ?? '#c8ccd1');
		if (accentSection) accentSection.hidden = !style.accent;
		if (desc) {
			desc.textContent = size.styles
				? `${style.description} ${size.label} plates are printed only for now.`
				: style.description;
		}
	};

	viewport.addEventListener('stylechange', (e) => render(e.detail));
	viewport.addEventListener('configchange', () => render({ style: viewport.style, accent: viewport.accent }));
	render({ style: viewport.style, accent: viewport.accent });
}

/** "On a car" toggle, car picker and paint swatches. */
export function mountCarControls(root, viewport) {
	const toggle = root.querySelector('[data-pbb-view]');
	const bar = root.querySelector('[data-pbb-carbar]');
	const select = root.querySelector('[data-pbb-car]');
	const paints = root.querySelector('[data-pbb-paints]');
	if (!toggle) return;

	if (select) {
		select.innerHTML = viewport.cars.map((c) => `<option value="${c.id}">${c.label}</option>`).join('');
		select.addEventListener('change', () => viewport.setCar(select.value));
		// One car so far: nothing to choose, so show its name as a label instead.
		select.disabled = viewport.cars.length < 2;
	}
	if (paints) {
		paints.innerHTML = viewport.paints.map((p) => `
			<button type="button" role="radio" class="pbb-paint" data-paint="${p.id}" style="--paint:${p.colour}"
				aria-checked="false" aria-label="${p.label}" title="${p.label}"></button>`).join('');
		paints.addEventListener('click', (e) => {
			const el = e.target.closest('[data-paint]');
			if (el) viewport.setPaint(el.dataset.paint);
		});
		arrowNavigation(paints, (el) => viewport.setPaint(el.dataset.paint));
	}
	toggle.addEventListener('click', () => viewport.setView(viewport.view === 'car' ? 'plate' : 'car'));

	const render = () => {
		const car = viewport.view === 'car';
		root.classList.toggle('is-car-view', car);
		toggle.setAttribute('aria-pressed', String(car));
		toggle.hidden = viewport.size.onCar === false;
		if (bar) bar.hidden = !car;
		if (select) select.value = viewport.carId;
		syncRadios(paints, 'paint', viewport.paintId);
	};
	viewport.addEventListener('viewchange', render);
	viewport.addEventListener('configchange', render);
	viewport.addEventListener('carloading', (e) => root.classList.toggle('is-car-loading', e.detail.loading));
	render();
}

/** One badge for the whole plate: show plate if any option adds colour around the characters. */
export function mountLegalBadge(root, viewport) {
	const legal = root.querySelector('[data-pbb-output="legal"]');
	if (!legal) return;
	const render = () => {
		const ok = viewport.roadLegal;
		legal.textContent = ok ? 'Road legal' : 'Show plate';
		legal.dataset.state = ok ? 'ok' : 'show';
		const reasons = [];
		if (!viewport.style.roadLegal) reasons.push(`the ${viewport.style.label} style`);
		if (!getBorder(viewport.options.border).roadLegal) reasons.push('a coloured border');
		legal.title = ok
			? 'Meets BS AU 145e when made with your registration and supplier details.'
			: `Not for use on public roads because of ${reasons.join(' and ')}. Road-legal plates can't have colour on, around or under the characters.`;
	};
	viewport.addEventListener('stylechange', render);
	viewport.addEventListener('configchange', render);
	render();
}

/* ---------------------------------------------------------- plate options */

/** Outline icon drawn to the size's real proportions. */
function sizeIcon(size) {
	const w = size.width === 'auto' ? 330 : size.width;
	const k = Math.min(44 / w, 26 / size.height);
	const pw = w * k;
	const ph = size.height * k;
	const x = (48 - pw) / 2;
	const y = (30 - ph) / 2;
	const py = y + ph * (size.point ?? 0.5); // hex points sit above the middle
	const outline = size.shape === 'hex'
		? `<path d="M${x} ${py} ${x + size.chamfer * k} ${y}H${x + pw - size.chamfer * k}L${x + pw} ${py} ${x + pw - size.chamfer * k} ${y + ph}H${x + size.chamfer * k}Z"/>`
		: `<rect x="${x}" y="${y}" width="${pw}" height="${ph}" rx="1.5"/>`;
	const bars = size.lines === 2
		? `<rect x="${x + pw * 0.22}" y="${y + ph * 0.24}" width="${pw * 0.56}" height="${ph * 0.18}"/><rect x="${x + pw * 0.28}" y="${y + ph * 0.58}" width="${pw * 0.44}" height="${ph * 0.18}"/>`
		: `<rect x="${x + pw * 0.16}" y="${y + ph * 0.32}" width="${pw * 0.68}" height="${ph * 0.36}"/>`;
	const cut = size.width === 'auto' ? `<path d="M${x + pw + 1.5} ${y - 2}v${ph + 4}" stroke-dasharray="1.5 1.5"/>` : '';
	return `<svg class="pbb-size-icon" viewBox="0 0 48 30" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="1.2">${outline}${cut}</g><g fill="currentColor" opacity=".55">${bars}</g></svg>`;
}

const sizeNote = (s) => (s.width === 'auto' ? 'Cut to fit' : `${s.width} × ${s.height}`);

/** Mini preview of each badge for the picker. */
function badgeIcon(badge) {
	if (!badge.uk && !badge.ev) return '<span class="pbb-badge-none"></span>';
	const flag = UNION_FLAG.replace(/ width="\d+" height="\d+"/, ' class="pbb-badge-flag" preserveAspectRatio="xMidYMid slice"');
	if (badge.design) {
		// Gel sticker in miniature: flag on its side (UK designs), small flag (EV + UK), plain (EV).
		const art = badge.design === 'ev' ? '' : `${flag}<b class="pbb-badge-uk">UK</b>`;
		return `<span class="pbb-badge-art pbb-gel is-gel-${badge.design}${badge.finish === '4d' ? ' is-4d' : ''}"><span class="pbb-gel-sticker">${art}</span></span>`;
	}
	const uk = badge.uk ? `${flag}<b class="pbb-badge-uk">UK</b>` : '';
	return `<span class="pbb-badge-art${badge.ev ? ' is-ev' : ''}">${uk}</span>`;
}

export function mountPlateOptions(root, viewport) {
	const sizes = root.querySelector('[data-pbb-sizes]');
	const badges = root.querySelector('[data-pbb-badges]');
	const badgeNote = root.querySelector('[data-pbb-output="badge-note"]');
	const borders = root.querySelector('[data-pbb-borders]');

	if (sizes) {
		sizes.innerHTML = PLATE_SIZES.filter(offered('sizes')).map((s) => `
			<button type="button" role="radio" class="pbb-option${s.single ? ' is-vehicle' : ''}" data-size="${s.id}" aria-checked="false"
				aria-label="${s.label}, ${sizeNote(s)}${s.rearOnly ? ', rear plate only' : ''}${s.styles ? ', printed characters only' : ''}">
				${sizeIcon(s)}
				<span class="pbb-option-name">${s.label}</span>
				<span class="pbb-option-note">${sizeNote(s)}<span class="pbb-note-extra">${s.rearOnly ? ' · rear' : ''}${s.styles ? ' · printed' : ''}</span></span>
			</button>`).join('');
		const pick = (el) => viewport.setSize(el.dataset.size);
		sizes.addEventListener('click', (e) => e.target.closest('[data-size]') && pick(e.target.closest('[data-size]')));
		arrowNavigation(sizes, pick);

		// Front and rear can be different sizes: pick the plate, or keep them the same.
		sizes.insertAdjacentHTML('beforebegin', `
			<div class="pbb-size-sides" data-pbb-size-sides>
				<div class="pbb-size-tabs" role="group" aria-label="Plate">
					<button type="button" data-size-side="front" aria-pressed="true">Front plate</button>
					<button type="button" data-size-side="rear" aria-pressed="false">Rear plate</button>
				</div>
				<label class="pbb-size-link"><input type="checkbox" data-size-link checked> Same size front &amp; rear</label>
			</div>`);
		sizes.insertAdjacentHTML('afterend', '<p class="pbb-style-desc pbb-size-summary" data-pbb-size-summary aria-live="polite"></p>');
		const sides = root.querySelector('[data-pbb-size-sides]');
		sides.addEventListener('click', (e) => {
			const btn = e.target.closest('[data-size-side]');
			if (btn) viewport.setSide(btn.dataset.sizeSide);
		});
		sides.querySelector('[data-size-link]').addEventListener('change', (e) => {
			if (!viewport.setLinked(e.target.checked)) {
				e.target.checked = false;
				sizeNoteText = `${viewport.size.label} is a rear plate size. Pick a front size first to use the same size on both.`;
				render();
			}
		});
	}
	let sizeNoteText = '';
	// "Oversized, 4x4, motorbike and trailer plates are under Rear plate", from the rear-only sizes this shop offers.
	const rearOnly = PLATE_SIZES.filter(offered('sizes')).filter((s) => s.rearOnly).map((s) => (s.label === '4x4' ? s.label : s.label.toLowerCase()));
	const rearOnlyNote = rearOnly.length
		? `${(rearOnly.length > 1 ? `${rearOnly.slice(0, -1).join(', ')} and ${rearOnly.at(-1)}` : rearOnly[0]).replace(/^./, (c) => c.toUpperCase())} plates are`
		: '';

	if (badges) {
		badges.innerHTML = BADGES.filter(offered('badges')).map((b) => `
			<button type="button" role="radio" class="pbb-option pbb-badge" data-badge="${b.id}" aria-checked="false">
				${badgeIcon(b)}
				<span class="pbb-option-name">${b.label}</span>
				<span class="pbb-option-note">${{ gel: 'Gel', printed: 'Printed', '4d': '4D' }[b.finish] ?? '&nbsp;'}</span>
			</button>`).join('');
		// Picking a badge swings the plate's badge end towards the camera (not for "None").
		const pick = (el) => {
			if (el.disabled) return;
			viewport.setBadge(el.dataset.badge);
			if (el.dataset.badge !== 'none') viewport.showcaseBadge();
		};
		badges.addEventListener('click', (e) => e.target.closest('[data-badge]') && pick(e.target.closest('[data-badge]')));
		arrowNavigation(badges, pick);
	}

	if (borders) {
		borders.innerHTML = BORDERS.filter(offered('borders')).map((b) => `
			<button type="button" role="radio" class="pbb-chip" data-border="${b.id}" aria-checked="false"
				aria-label="${b.width ? `Black border, ${b.label} thick` : b.label}">${b.label}</button>`).join('');
		const pick = (el) => viewport.setBorder(el.dataset.border);
		borders.addEventListener('click', (e) => e.target.closest('[data-border]') && pick(e.target.closest('[data-border]')));
		arrowNavigation(borders, pick);
	}

	const render = () => {
		const o = viewport.options;
		const size = viewport.size;
		syncRadios(sizes, 'size', size.id);

		// Front / rear size controls (not for one-plate motorbike and trailer sizes).
		const sides = root.querySelector('[data-pbb-size-sides]');
		const summary = root.querySelector('[data-pbb-size-summary]');
		if (sides) {
			const side = viewport.side;
			// Front/Rear tabs and "Same size" are for a pair; one plate shows just its own sizes.
			sides.hidden = size.single || (root.dataset.plates ?? 'pair') !== 'pair';
			sides.querySelectorAll('[data-size-side]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.sizeSide === side)));
			sides.querySelector('[data-size-link]').checked = o.linked;
			// Rear-only sizes (Oversized, 4x4, motorbike and trailer plates) are listed under Rear plate only.
			sizes.querySelectorAll('[data-size]').forEach((el) => {
				const s = PLATE_SIZES.find((x) => x.id === el.dataset.size);
				el.classList.toggle('is-rear-only', side === 'front' && !size.single && s.rearOnly);
			});
		}
		if (summary) {
			const front = viewport.sizeFor('front');
			const rear = viewport.sizeFor('rear');
			summary.textContent = sizeNoteText || (size.single
				? `${size.label}: one rear plate.`
				: front.id === rear.id
					? `Front and rear: ${front.label}.`
					: `Front: ${front.label} · Rear: ${rear.label}${rear.rearOnly ? ` (${rear.label} is rear only)` : ''}.`)
				// The front list leaves out rear-only sizes, so say where they are (pairs only: one front plate can't be those).
				+ (viewport.side === 'front' && !size.single && (root.dataset.plates ?? 'pair') === 'pair'
					? (rearOnlyNote ? (root.classList.contains('is-steps') ? ' More sizes under Rear plate.' : ` ${rearOnlyNote} under Rear plate.`) : '')
					: '');
			sizeNoteText = '';
		}
		syncRadios(borders, 'border', o.border);

		if (badges) {
			let unavailable = 0;
			badges.querySelectorAll('[data-badge]').forEach((el) => {
				const b = BADGES.find((x) => x.id === el.dataset.badge);
				// Available on both plates (front and rear can be different sizes).
				const blocked = [viewport.sizeFor('front'), viewport.sizeFor('rear')].find((s) => !badgeAvailable(b, s.id));
				const ok = !blocked;
				el.disabled = !ok;
				el.setAttribute('aria-label', `${b.label}${b.finish ? `, ${b.finish}` : ''}${ok ? '' : `, not available on ${blocked.label.toLowerCase()} plates`}`);
				if (!ok) unavailable++;
			});
			syncRadios(badges, 'badge', o.badge);
			if (badgeNote) {
				const shown = BADGES.filter(offered('badges'));
				badgeNote.textContent = unavailable === shown.length - 1
					? `Badges aren't available on ${[viewport.sizeFor('front'), viewport.sizeFor('rear')].find((s) => !shown.slice(1).some((b) => badgeAvailable(b, s.id)))?.label.toLowerCase() ?? size.label.toLowerCase()} plates.`
					: o.badge.startsWith('ev') ? 'EV strips are for zero-emission vehicles only.' : '';
			}
		}
	};
	viewport.addEventListener('configchange', render);
	viewport.addEventListener('sidechange', render);
	root.addEventListener('pbb-plateschange', render);
	render();
}
