/**
 * Checkout inside the builder. After Add-ons the design goes in the basket and the builder turns
 * into a secure checkout: the shop's own checkout page (delivery or collection, your details,
 * documents, payment), slimmed down to sit in the builder (?pbb_embed=1, see includes/checkout-embed.php).
 * Using the real checkout keeps every payment method, delivery rule and check exactly as it is.
 *
 * Phones (app view): the checkout fills the sheet under a short preview and scrolls on its own.
 * Computers: it replaces the options under the preview and grows to its full height.
 */

export function mountCheckout(root, viewport) {
	const panel = document.createElement('section');
	panel.className = 'pbb-checkout';
	panel.hidden = true;
	panel.setAttribute('aria-label', 'Secure checkout');
	panel.innerHTML = `
		<div class="pbb-checkout-head">
			<button type="button" class="pbb-checkout-back" data-pbb-checkout-back>
				<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg>
				Edit design
			</button>
			<p class="pbb-checkout-kick">
				<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2" fill="none" stroke="currentColor" stroke-width="2.4"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="2.4"/></svg>
				Secure checkout
			</p>
			<p class="pbb-checkout-sum" data-pbb-checkout-sum></p>
		</div>
		<div class="pbb-checkout-body">
			<p class="pbb-checkout-loading" data-pbb-checkout-loading>Loading secure checkout…</p>
			<iframe class="pbb-checkout-frame" title="Secure checkout" allow="payment *" data-pbb-checkout-frame></iframe>
		</div>`;
	const anchor = root.querySelector('.pbb-cart-msg') ?? root.lastElementChild;
	anchor.before(panel);

	const frame = panel.querySelector('[data-pbb-checkout-frame]');
	const loading = panel.querySelector('[data-pbb-checkout-loading]');
	const sum = panel.querySelector('[data-pbb-checkout-sum]');

	frame.addEventListener('load', () => {
		loading.hidden = true;
		frame.classList.add('is-loaded');
	});

	// The embedded page reports its height (computers grow the frame to fit, no inner scrollbar).
	window.addEventListener('message', (e) => {
		if (e.origin !== location.origin || e.source !== frame.contentWindow || !e.data || e.data.pbb !== 'checkout-height') return;
		frame.style.setProperty('--pbb-frame-h', `${Math.ceil(e.data.height)}px`);
	});

	const close = () => {
		root.classList.remove('is-checkout');
		panel.hidden = true;
		root.dispatchEvent(new CustomEvent('pbb-checkoutchange', { detail: { open: false } }));
		viewport.resetView();
	};
	panel.querySelector('[data-pbb-checkout-back]').addEventListener('click', close);

	return {
		open(checkoutUrl, summary = '') {
			const url = new URL(checkoutUrl, location.href);
			url.searchParams.set('pbb_embed', '1');
			sum.textContent = summary;
			loading.hidden = false;
			frame.classList.remove('is-loaded');
			frame.src = url.toString(); // a fresh load each time, so the basket and totals are current
			panel.hidden = false;
			root.classList.add('is-checkout');
			root.dispatchEvent(new CustomEvent('pbb-checkoutchange', { detail: { open: true } }));
			viewport.resetView();
			// Computers: bring the checkout into view under the sticky preview.
			if (!root.classList.contains('is-app')) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
		},
		close,
	};
}
