import { calculatePrice } from './pricing.js';
import { mountCheckout } from './checkout.js';

/** Escape text for innerHTML. */
const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

/**
 * Plates (front / rear / pair, on the Plate size step), add-ons (fixings, holders,
 * in-store fitting), live price and Continue to checkout.
 * Posts the configuration to ?wc-ajax=pbb_add_to_cart; the server validates and prices it,
 * then the builder opens the secure checkout in place (checkout.js).
 */

const PLATES = [
	{ id: 'pair', label: 'Front & rear' },
	{ id: 'front', label: 'Front only' },
	{ id: 'rear', label: 'Rear only' },
];

export function mountBasket(root, viewport, config) {
	const platesGroup = root.querySelector('[data-pbb-plates]');
	const extras = root.querySelector('[data-pbb-extras]');
	const summary = root.querySelector('[data-pbb-summary]');
	const totalEl = root.querySelector('[data-pbb-total]');
	const addBtn = root.querySelector('[data-pbb-add]');
	const message = root.querySelector('[data-pbb-cart-msg]');
	if (!addBtn) return;
	const checkout = mountCheckout(root, viewport);
	// The basket line for this design: going back to edit and checking out again replaces it.
	let lineKey = '';

	const money = new Intl.NumberFormat('en-GB', { style: 'currency', currency: config.currency || 'GBP' });
	const accessoryLabels = config.accessories ?? {};
	// `chosen` is the customer's pick; rear-only sizes override it without forgetting it.
	const state = { chosen: 'pair', accessories: new Set(), fitting: false, fits: false, hasReg: false };
	// Motorbike and trailer plates are one rear plate.
	const plates = () => (viewport.size.single ? 'rear' : state.chosen);

	/* Plates */
	platesGroup.innerHTML = PLATES.map((p) => `
		<button type="button" role="radio" class="pbb-chip" data-plates="${p.id}" aria-checked="false">${p.label}</button>`).join('');
	platesGroup.addEventListener('click', (e) => {
		const el = e.target.closest('[data-plates]');
		if (!el || el.disabled) return;
		state.chosen = el.dataset.plates;
		// Show the plate they're buying; a pair keeps whichever side is on screen.
		if (state.chosen !== 'pair') viewport.setSide(state.chosen);
		render();
	});
	// The size step shows Front/Rear tabs only for a pair.
	const announcePlates = () => {
		if (root.dataset.plates === plates()) return;
		root.dataset.plates = plates();
		root.dispatchEvent(new CustomEvent('pbb-plateschange', { detail: { plates: plates() } }));
	};

	/* Extras */
	extras.innerHTML = Object.entries(accessoryLabels).map(([id, label]) => `
		<label class="pbb-check">
			<input type="checkbox" value="${id}">
			<span>${label}</span>
			<span class="pbb-check-price">${money.format(Number(config.pricing?.accessories?.[id] ?? 0))}</span>
		</label>`).join('') + `
		<label class="pbb-check pbb-fitting">
			<input type="checkbox" data-pbb-fitting>
			<span>In-store fitting while you wait<small>${escapeHtml(config.fittingNote ?? 'Collect from the shop and we fit them on the spot.')}</small></span>
			<span class="pbb-check-price">${money.format(Number(config.pricing?.services?.fitting ?? 30))}</span>
		</label>`;
	// One pick per group (pads, holders): ticking one unticks the other, like the server does.
	const groups = config.accessoryGroups ?? [];
	extras.addEventListener('change', (e) => {
		if (e.target.matches('[data-pbb-fitting]')) {
			state.fitting = e.target.checked;
			render();
			return;
		}
		const id = e.target.value;
		if (e.target.checked) {
			for (const other of groups.find((g) => g.includes(id)) ?? []) {
				if (other === id) continue;
				state.accessories.delete(other);
				const box = extras.querySelector(`input[value="${other}"]`);
				if (box) box.checked = false;
			}
			state.accessories.add(id);
		} else {
			state.accessories.delete(id);
		}
		render();
	});

	const fullConfig = () => ({ ...viewport.getConfig(), plates: plates(), accessories: [...state.accessories], fitting: state.fitting });

	function render() {
		// Motorbike and trailer plates are one rear plate.
		const rearOnly = viewport.size.single;
		platesGroup.querySelectorAll('[data-plates]').forEach((el) => {
			el.disabled = rearOnly && el.dataset.plates !== 'rear';
			const on = el.dataset.plates === plates();
			el.setAttribute('aria-checked', String(on));
			el.tabIndex = on ? 0 : -1;
		});

		announcePlates();
		const price = calculatePrice(fullConfig(), config.pricing, config.labels);
		summary.innerHTML = price.lines.map((l) => `
			<div class="pbb-line"><span>${l.label}</span><span>${l.amount < 0 ? '−' : ''}${money.format(Math.abs(l.amount))}</span></div>`).join('');
		totalEl.textContent = money.format(price.total);

		addBtn.disabled = !state.hasReg || !state.fits || addBtn.classList.contains('is-busy');
		addBtn.title = !state.hasReg ? 'Enter your registration first' : !state.fits ? "This registration doesn't fit the chosen plate" : '';
	}

	viewport.addEventListener('layoutchange', ({ detail }) => {
		state.hasReg = !detail.placeholder;
		state.fits = detail.fitsAll ?? detail.fits;
		render();
	});
	viewport.addEventListener('configchange', render);
	viewport.addEventListener('stylechange', render);

	/* Continue to checkout */
	addBtn.addEventListener('click', async () => {
		const payload = fullConfig();
		addBtn.classList.add('is-busy');
		addBtn.disabled = true;
		message.dataset.state = '';
		message.textContent = 'Opening secure checkout…';

		try {
			if (!config.cart?.endpoint) {
				// Dev page without WooCommerce: show what would be sent.
				console.info('[Plate Builder] Would add to basket:', payload);
				await new Promise((r) => setTimeout(r, 400));
				message.textContent = '';
				showAdded(payload, calculatePrice(payload, config.pricing, config.labels).total, '#checkout', '#basket');
				return;
			}

			const body = new URLSearchParams({ config: JSON.stringify(payload), replace: lineKey });
			const res = await fetch(config.cart.endpoint, { method: 'POST', body, credentials: 'same-origin' });
			const json = await res.json().catch(() => null);
			if (!res.ok || !json?.success) throw new Error(json?.data?.message || 'Could not add to basket. Please try again.');

			message.textContent = '';
			lineKey = json.data.key || '';
			const count = root.querySelector('[data-pbb-cart-count]');
			if (count && json.data.count) count.textContent = json.data.count;
			checkout.open(json.data.checkoutUrl, `${payload.registration.replace(/[<>&]/g, '')} · ${money.format(json.data.price)}`);
			// Refresh mini-cart / basket count in the theme header.
			window.jQuery?.(document.body).trigger('wc_fragment_refresh');
			document.body.dispatchEvent(new CustomEvent('wc-blocks_added_to_cart'));
		} catch (err) {
			message.dataset.state = 'error';
			message.textContent = err.message;
		} finally {
			addBtn.classList.remove('is-busy');
			render();
		}
	});

	/*
	 * Added: a pop-up that heads to checkout on its own after a short countdown (almost every
	 * customer buys one set), with "Add another set" for the rare customer who wants more.
	 */
	const AUTO_CHECKOUT_S = 4;
	let added = null;
	function showAdded(payload, price, checkoutUrl, cartUrl) {
		added?.close();
		const box = document.createElement('div');
		box.className = 'pbb-added';
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');
		box.setAttribute('aria-labelledby', 'pbb-added-title');
		box.innerHTML = `
			<div class="pbb-added-card">
				<div class="pbb-added-tick" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="28" height="28"><path fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>
				</div>
				<h3 id="pbb-added-title">Added to basket</h3>
				<p class="pbb-added-reg">${payload.registration.replace(/[<>&]/g, '')} · ${money.format(price)}</p>
				<a class="pbb-added-go" href="${checkoutUrl}">
					Go to checkout
					<span class="pbb-added-bar" style="animation-duration:${AUTO_CHECKOUT_S}s"></span>
				</a>
				<p class="pbb-added-auto" aria-live="polite">Taking you to checkout in <span>${AUTO_CHECKOUT_S}</span>…</p>
				<div class="pbb-added-more">
					<button type="button" data-pbb-added-stay>Add another set</button>
					<a href="${cartUrl}">View basket</a>
				</div>
			</div>`;
		root.append(box);

		let left = AUTO_CHECKOUT_S;
		const secs = box.querySelector('.pbb-added-auto span');
		const timer = setInterval(() => {
			left -= 1;
			if (secs) secs.textContent = Math.max(0, left);
			if (left <= 0) {
				clearInterval(timer);
				window.location.href = checkoutUrl;
			}
		}, 1000);
		const close = () => {
			clearInterval(timer);
			box.remove();
			added = null;
		};
		box.querySelector('[data-pbb-added-stay]').addEventListener('click', close);
		box.addEventListener('keydown', (e) => e.key === 'Escape' && close());
		box.querySelector('.pbb-added-go').focus();
		added = { close };
	}

	render();
}
