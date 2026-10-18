/**
 * Checkout "Your documents" panel (DVLA entitlement checks).
 * Plain script, no build step: the classic checkout mounts it into [data-pbb-documents],
 * the block checkout mounts it through documents-blocks.js.
 *
 * All state lives server-side in the WooCommerce session, so both checkouts behave
 * the same and the server re-checks everything when the order is placed.
 */
(function () {
	'use strict';

	const settings = window.pbbDocuments || {};
	const url = (endpoint) => String(settings.endpoint).replace('%%endpoint%%', endpoint);

	async function call(endpoint, data) {
		const body = data instanceof FormData ? data : new URLSearchParams(data || {});
		if (body instanceof FormData) body.append('nonce', settings.nonce);
		else body.set('nonce', settings.nonce);
		const res = await fetch(url(endpoint), { method: 'POST', body, credentials: 'same-origin' });
		const json = await res.json().catch(() => null);
		if (!res.ok || !json || !json.success) {
			throw new Error((json && json.data && json.data.message) || 'Something went wrong. Please try again.');
		}
		return json.data;
	}

	const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

	function mount(container, opts) {
		if (!container) return { destroy() {} };
		let state = null;
		let busy = {};
		let errors = {};
		let destroyed = false;

		function contactLine() {
			const c = state.contact || {};
			const ways = [`email (${esc(c.email)})`];
			if (c.whatsapp) ways.push(`WhatsApp (${esc(c.whatsapp)})`);
			if (c.address) ways.push('post');
			return `${ways.join(', ')}, or bring them in if you're collecting`;
		}

		function render() {
			if (destroyed) return;
			if (!state || !state.needed) {
				container.hidden = true;
				container.innerHTML = '';
				return;
			}
			container.hidden = false;
			const upload = state.method === 'upload';
			const later = state.method === 'later';
			const name = `pbb-docs-method-${opts.uid}`;

			// Keep document types the customer picked but hasn't uploaded yet: redrawing
			// after one upload would otherwise reset the other dropdown.
			const draft = {};
			container.querySelectorAll('select[data-kind]').forEach((s) => { draft[s.dataset.kind] = s.value; });

			const kinds = Object.entries(state.types).map(([kind, group]) => {
				const file = state.files[kind];
				const chosen = file ? file.type : draft[kind];
				const options = Object.entries(group.options)
					.map(([id, label]) => `<option value="${esc(id)}"${chosen === id ? ' selected' : ''}>${esc(label)}</option>`)
					.join('');
				const status = busy[kind]
					? '<span class="pbb-docs-status">Uploading…</span>'
					: file
						? `<span class="pbb-docs-status is-ok">✓ ${esc(file.name)}</span> <button type="button" class="pbb-docs-remove" data-kind="${esc(kind)}">Remove</button>`
						: '';
				return `
					<fieldset class="pbb-docs-kind">
						<legend>${esc(group.label)}</legend>
						<label class="pbb-docs-field">
							<span>Document type</span>
							<select data-kind="${esc(kind)}" data-role="type">
								<option value="">Choose…</option>${options}
							</select>
						</label>
						<label class="pbb-docs-field">
							<span>Photo or PDF (max ${Math.round(state.maxBytes / 1048576)} MB)</span>
							<input type="file" accept="${esc(state.accept)}" data-kind="${esc(kind)}" data-role="file"${busy[kind] ? ' disabled' : ''}>
						</label>
						<div class="pbb-docs-row">${status}</div>
						${errors[kind] ? `<p class="pbb-docs-error" role="alert">${esc(errors[kind])}</p>` : ''}
					</fieldset>`;
			}).join('');

			container.innerHTML = `
				<div class="pbb-docs">
					<h3>Your documents</h3>
					<p>By law, as a DVLA registered number plate supplier, we must see <strong>one proof of ID</strong> and
					<strong>one proof of entitlement</strong> to the registration (such as your V5C logbook) before we can dispatch
					or hand over road-legal plates. <a href="/valid-documents/" target="_blank" rel="noopener">See valid documents</a>.</p>
					<div class="pbb-docs-choice" role="radiogroup" aria-label="How will you provide your documents?">
						<label><input type="radio" name="${name}" value="upload"${upload ? ' checked' : ''}> Upload now</label>
						<label><input type="radio" name="${name}" value="later"${later ? ' checked' : ''}> Send after ordering</label>
					</div>
					${upload ? kinds : ''}
					${later ? `<p class="pbb-docs-later">No problem. After ordering, send them by ${contactLine()}. We'll hold your order and dispatch as soon as we've checked them.</p>` : ''}
					${errors.method ? `<p class="pbb-docs-error" role="alert">${esc(errors.method)}</p>` : ''}
				</div>`;
		}

		async function load() {
			try {
				state = await call('pbb_documents_state');
			} catch (e) {
				state = null;
			}
			render();
		}

		container.addEventListener('change', async (e) => {
			const t = e.target;
			if (t.type === 'radio') {
				errors = {};
				state.method = t.value; // switch the panel straight away; the server confirms
				render();
				try {
					state = await call('pbb_documents_method', { method: t.value });
				} catch (err) {
					errors.method = err.message;
				}
				render();
				return;
			}
			const kind = t.dataset.kind;
			if (t.dataset.role === 'file' && t.files && t.files[0]) {
				const file = t.files[0];
				const type = container.querySelector(`select[data-kind="${kind}"]`).value;
				errors[kind] = '';
				if (!type) errors[kind] = 'Choose the document type first.';
				else if (file.size > state.maxBytes) errors[kind] = 'That file is over 10 MB. Please use a smaller photo or a PDF.';
				if (errors[kind]) {
					t.value = '';
					render();
					return;
				}
				const fd = new FormData();
				fd.append('kind', kind);
				fd.append('type', type);
				fd.append('file', file);
				busy[kind] = true;
				render();
				try {
					state = await call('pbb_documents_upload', fd);
				} catch (err) {
					errors[kind] = err.message;
				}
				busy[kind] = false;
				render();
			}
		});

		container.addEventListener('click', async (e) => {
			const btn = e.target.closest('.pbb-docs-remove');
			if (!btn) return;
			try {
				state = await call('pbb_documents_remove', { kind: btn.dataset.kind });
			} catch (err) {
				errors[btn.dataset.kind] = err.message;
			}
			render();
		});

		load();
		return {
			refresh: load,
			destroy() {
				destroyed = true;
				container.innerHTML = '';
			},
		};
	}

	let uid = 0;
	window.PlateBossDocuments = { mount: (el) => mount(el, { uid: ++uid }) };

	/**
	 * The PHP placeholder comes from woocommerce_checkout_after_customer_details, which
	 * checkouts built from page-builder widgets (e.g. ShopBuilder) never
	 * run. Without a panel the server would reject every road-legal order, so create the
	 * placeholder ourselves after the billing details. It stays hidden unless documents are needed.
	 */
	function findOrCreateContainer() {
		const existing = document.querySelector('[data-pbb-documents]');
		if (existing) return existing;
		const form = document.querySelector('form.checkout');
		if (!form || document.querySelector('.wp-block-woocommerce-checkout')) return null;

		const el = document.createElement('div');
		el.id = 'pbb-documents';
		el.setAttribute('data-pbb-documents', '');
		el.hidden = true;

		// Insert outside any fragment WooCommerce re-renders (order review / payment).
		const billing = form.querySelector('.woocommerce-billing-fields');
		const payment = form.querySelector('#payment, .woocommerce-checkout-payment');
		const wrap = (node) => (node && (node.closest('.elementor-widget') || node)) || null;
		if (billing) wrap(billing).after(el);
		else if (payment) wrap(payment).before(el);
		else form.prepend(el);
		return el;
	}

	// Classic checkout: mount, and refresh when the basket changes on the checkout page.
	const boot = () => {
		const el = findOrCreateContainer();
		if (!el) return;
		const ui = window.PlateBossDocuments.mount(el);
		if (window.jQuery) window.jQuery(document.body).on('updated_checkout', () => ui.refresh());
	};
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
	else boot();
})();
