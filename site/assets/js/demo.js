/**
 * Live demo wrapper for the plate builder.
 *
 * White-labels the builder through its config only (plateboss-builder/ is a copy of plugin/assets,
 * synced by tools/sync-demo-builder.sh):
 *  - neutral demo supplier "Your Plates Ltd", postcode "AB1 2CD", no logo;
 *  - the car badge comes from demo-assets/ (carBadge); plain plate backs (backing: null);
 *  - URL options ?reg= ?style= ?size= ?rear= ?badge= ?border= ?side= ?view=car ?mobile=
 *    pre-set the builder, so the Features page can link straight into a view.
 * It loads the builder only when it scrolls into view, and replaces "Continue to checkout"
 * with a panel showing what the plate maker's shop would receive.
 */

const BUILDER = new URL('../../plateboss-builder/assets/', import.meta.url).href;
const DEMO_ASSETS = new URL('../../demo-assets/', import.meta.url).href;

/* Example prices (a typical UK price list). A real shop sets its own in WooCommerce → Plate Builder. */
const PRICING = {
	style_pair: { printed: 29.99, 'gel-3d': 49.99, 'acrylic-4d-3': 49.99, 'acrylic-4d-6': 64.99, 'gel-4d-3': 64.99, 'gel-4d-6': 79.99, retro: 69.99 },
	style_single: { printed: 19.99, 'gel-3d': 29.99, 'acrylic-4d-3': 29.99, 'acrylic-4d-6': 39.99, 'gel-4d-3': 39.99, 'gel-4d-6': 44.99, retro: 39.99 },
	size: { standard: 0, short: 10, hex: 10, square: 10, oversized: 10, 'japanese-import': 10, 'us-import': 10 },
	flat: { motorbike: 19.99, trailer: 19.99, 'trailer-commercial': 19.99 },
	badge: { uk: 5, 'uk-gel': 10, 'uk-gel-blue': 10, 'uk-4d': 15, ev: 5, 'ev-gel': 10, 'ev-uk-gel': 10 },
	border: { 'black-1mm': 10, 'black-2mm': 10, 'black-3mm': 10 },
	services: { fitting: 30 },
	accessories: { sticky_pads: 2.5, xl_pads: 6.99, screw_kit: 2.5, frame_holders: 18, frameless_holders: 20 },
};

const ACCESSORIES = {
	sticky_pads: '8 adhesive pads (1 mm)',
	xl_pads: 'XL pad set (8 × 1 mm + 3 × 5 mm)',
	screw_kit: 'Screws + caps (4 screws, 2 yellow & 2 white caps)',
	frameless_holders: 'Frameless holders (set)',
};

const LABELS = {
	accessories: ACCESSORIES,
	styles: { printed: 'Printed', 'gel-3d': '3D Gel', 'acrylic-4d-3': '4D 3mm', 'acrylic-4d-6': '4D 6mm', 'gel-4d-3': '4D Gel 3mm', 'gel-4d-6': '4D Gel 6mm', retro: 'Retro' },
	sizes: { standard: 'Standard', short: 'Short', hex: 'Hex', square: '4x4', oversized: 'Oversized', motorbike: 'Motorbike', trailer: 'Trailer', 'trailer-commercial': 'Commercial trailer (white)', 'japanese-import': 'Japanese import', 'us-import': 'US import' },
	badges: { none: 'None', uk: 'UK badge (printed)', 'uk-gel': 'UK gel badge, white', 'uk-gel-blue': 'UK gel badge, blue', 'uk-4d': 'UK 4D badge (raised)', ev: 'EV strip (printed)', 'ev-gel': 'EV gel badge', 'ev-uk-gel': 'EV gel badge with UK' },
	borders: { none: 'None', 'black-1mm': 'Black, 1 mm', 'black-2mm': 'Black, 2 mm', 'black-3mm': 'Black, 3 mm' },
};

export const DEMO_CONFIG = {
	supplier: 'Your Plates Ltd',
	postcode: 'AB1 2CD',
	logo: '', // no logo: the legal footer prints the supplier name as text
	standard: 'BS AU 145e',
	side: 'front',
	registration: '',
	fontUrl: `${BUILDER}fonts/charles-wright-bold.json`,
	fonts: { motorcycle: `${BUILDER}fonts/charles-wright-singapore.json` },
	assetsUrl: DEMO_ASSETS,
	pricing: PRICING,
	accessories: ACCESSORIES,
	fittingNote: 'Collect from the shop and we fit them on the spot.',
	carBadge: `${DEMO_ASSETS}img/pb-car-badge.svg`,
	backing: null, // plain sheeting on the back of the plate
	accessoryGroups: [['sticky_pads', 'xl_pads'], ['frameless_holders']],
	currency: 'GBP',
	labels: LABELS,
	mobile: 'app',
	// No cart endpoint: this is a demo, so nothing is sent to a shop.
};

/* URL options, checked against the builder's own option ids. */
const ALLOWED = {
	style: Object.keys(LABELS.styles),
	size: Object.keys(LABELS.sizes),
	rear: Object.keys(LABELS.sizes),
	badge: Object.keys(LABELS.badges),
	border: Object.keys(LABELS.borders),
	side: ['front', 'rear'],
	mobile: ['app', 'launch', 'inline'],
};

function urlOptions() {
	const q = new URLSearchParams(location.search);
	const out = {};
	for (const [key, ok] of Object.entries(ALLOWED)) {
		const v = q.get(key);
		if (v && ok.includes(v)) out[key === 'rear' ? 'rearSize' : key] = v;
	}
	const reg = q.get('reg');
	if (reg) out.registration = reg.toUpperCase().replace(/[^A-Z0-9 ]/g, '').slice(0, 8);
	return out;
}

function configure(el, useUrl) {
	const config = { ...DEMO_CONFIG };
	if (el.dataset.demoMobile) config.mobile = el.dataset.demoMobile;
	if (el.dataset.demoReg) config.registration = el.dataset.demoReg;
	if (useUrl) Object.assign(config, urlOptions());
	el.dataset.pbbConfig = JSON.stringify(config);
	el.classList.toggle('is-launch', config.mobile === 'launch');
	return config;
}

/* ------------------------------------------------------------------ loading */

function loadStyles() {
	if (document.querySelector('link[data-pbb-css]')) return Promise.resolve();
	return new Promise((resolve) => {
		const link = document.createElement('link');
		link.rel = 'stylesheet';
		link.href = `${BUILDER}css/builder.css`;
		link.dataset.pbbCss = '';
		link.onload = link.onerror = () => resolve();
		document.head.append(link);
	});
}

async function boot(builders) {
	await loadStyles();
	await import(`${BUILDER}js/main.js`);
	builders.forEach(afterBoot);
}

function afterBoot(root) {
	const frame = root.closest('[data-demo-frame]');
	frame?.classList.add('is-loaded');

	// ?view=car opens the "On a car" view once the plate is ready.
	if (new URLSearchParams(location.search).get('view') === 'car' && root.hasAttribute('data-demo-url')) {
		const go = () => root.querySelector('[data-pbb-view]')?.click();
		if (root.classList.contains('is-ready')) go();
		else root.pbbViewport?.addEventListener('ready', go, { once: true });
	}
}

/* ------------------------------------------------------------------ demo order panel */

const money = new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' });
const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

async function showOrder(root) {
	const viewport = root.pbbViewport;
	if (!viewport) return;
	const [{ calculatePrice }, { getSize }] = await Promise.all([
		import(`${BUILDER}js/pricing.js`),
		import(`${BUILDER}js/plate-sizes.js`),
	]);

	const base = viewport.getConfig();
	const plates = root.dataset.plates || 'pair';
	const accessories = [...root.querySelectorAll('[data-pbb-extras] input[type="checkbox"]:not([data-pbb-fitting]):checked')].map((i) => i.value);
	const fitting = Boolean(root.querySelector('[data-pbb-fitting]')?.checked);
	const config = { ...base, plates, accessories, fitting };
	const price = calculatePrice(config, PRICING, LABELS);

	const front = getSize(base.size);
	const rear = getSize(base.rearSize ?? base.size);
	const dims = (s, w) => `${LABELS.sizes[s.id] ?? s.label} (${Math.round(w)} × ${s.height} mm)`;
	let sizeText;
	if (front.single || plates === 'rear') sizeText = dims(rear, base.rearWidth);
	else if (plates === 'front') sizeText = dims(front, base.width);
	else if (front.id === rear.id && Math.round(base.width) === Math.round(base.rearWidth)) sizeText = dims(front, base.width);
	else sizeText = `Front ${dims(front, base.width)}, Rear ${dims(rear, base.rearWidth)}`;

	const plateText = front.single ? 'Rear only' : { pair: 'Front & rear', front: 'Front only', rear: 'Rear only' }[plates];
	const rows = [
		['Registration', base.registration],
		['Plates', plateText],
		['Size', sizeText],
		['Style', LABELS.styles[base.style] ?? base.style],
		['Badge', LABELS.badges[base.badge] ?? 'None'],
		['Border', LABELS.borders[base.border] ?? 'None'],
		['Extras', accessories.map((a) => ACCESSORIES[a]).join(', ') || 'None'],
		['In-store fitting', fitting ? 'Yes, collect and fit' : 'No'],
		['Status', base.roadLegal ? 'Road legal' : 'Show plate'],
	];

	const dialog = document.createElement('dialog');
	dialog.className = 'demo-order';
	dialog.setAttribute('aria-labelledby', 'demo-order-title');
	dialog.innerHTML = `
		<div class="demo-order-head">
			<p class="demo-order-kicker">This is a demo</p>
			<h2 id="demo-order-title">Here's the order your shop would receive</h2>
			<p>On your live shop, this button opens your own checkout inside the builder: delivery or collection, documents, card, Apple Pay and Google Pay. Nothing has been sent anywhere.</p>
		</div>
		<dl class="demo-order-lines">
			${rows.map(([k, v]) => `<div><dt>${k}</dt><dd>${esc(v)}</dd></div>`).join('')}
		</dl>
		<div class="demo-order-price">
			${price.lines.map((l) => `<div><span>${esc(l.label)}</span><span>${money.format(l.amount)}</span></div>`).join('')}
			<div class="demo-order-total"><span>Order total (example prices)</span><strong>${money.format(price.total)}</strong></div>
		</div>
		<p class="demo-order-note">${base.roadLegal
			? 'Road-legal order: your checkout asks for one proof of ID and one proof of entitlement, and the order waits as <em>Awaiting documents</em> until your staff have checked them.'
			: 'Show plate: no documents needed at checkout.'}</p>
		<details class="demo-order-json">
			<summary>Show the design data saved on the order</summary>
			<pre>${esc(JSON.stringify(config, null, 2))}</pre>
		</details>
		<div class="demo-order-actions">
			<a class="btn btn-primary" href="${new URL('../../contact.html', import.meta.url).href}">Request a demo</a>
			<button type="button" class="btn btn-ghost" data-close>Keep designing</button>
		</div>`;

	// Inside the phone preview (an iframe), links open in the main window.
	if (window.parent !== window) dialog.querySelectorAll('a[href]').forEach((a) => (a.target = '_top'));
	// In the phone app layout the builder sits on <body>; the dialog goes in the top layer either way.
	document.body.append(dialog);
	const close = () => dialog.close();
	dialog.querySelector('[data-close]').addEventListener('click', close);
	dialog.addEventListener('click', (e) => e.target === dialog && close());
	dialog.addEventListener('close', () => {
		dialog.remove();
		root.querySelector('[data-pbb-add]')?.focus();
	});
	dialog.showModal();
	// Focus the close button without scrolling past the "this is a demo" heading on small screens.
	dialog.querySelector('[data-close]').focus({ preventScroll: true });
	dialog.scrollTop = 0;
}

function interceptCheckout(root) {
	// Capture phase on the builder runs before basket.js's own listener on the button.
	root.addEventListener('click', (e) => {
		const btn = e.target.closest('[data-pbb-add]');
		if (!btn || btn.disabled) return;
		e.preventDefault();
		e.stopPropagation();
		showOrder(root);
	}, true);
}

/* ------------------------------------------------------------------ start */

const builders = [...document.querySelectorAll('.pbb-builder[data-demo]')];
builders.forEach((el) => {
	configure(el, el.hasAttribute('data-demo-url'));
	interceptCheckout(el);
});

if (builders.length) {
	let started = false;
	const start = () => {
		if (started) return;
		started = true;
		boot(builders).catch((err) => {
			console.error('[demo] builder failed to load', err);
			builders.forEach((b) => b.closest('[data-demo-frame]')?.classList.add('is-failed'));
		});
	};
	if ('IntersectionObserver' in window) {
		const io = new IntersectionObserver((entries) => {
			if (entries.some((e) => e.isIntersecting)) {
				io.disconnect();
				start();
			}
		}, { rootMargin: '300px 0px' });
		builders.forEach((b) => io.observe(b.closest('[data-demo-frame]') ?? b));
	} else {
		start();
	}
}
