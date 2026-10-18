/**
 * Block checkout: show the documents panel in WooCommerce's order-meta slot.
 * Uses the window globals WordPress and WooCommerce Blocks provide, so no build step.
 */
(function () {
	'use strict';
	const { registerPlugin } = window.wp.plugins;
	const { createElement: el, useEffect, useRef } = window.wp.element;
	const { ExperimentalOrderMeta } = window.wc.blocksCheckout;

	function DocumentsPanel() {
		const ref = useRef(null);
		useEffect(() => {
			const ui = window.PlateBuilderDocuments.mount(ref.current);
			return () => ui.destroy();
		}, []);
		return el(ExperimentalOrderMeta, null, el('div', { ref, className: 'pbb-docs-block' }));
	}

	registerPlugin('plate-builder-documents', { render: DocumentsPanel, scope: 'woocommerce-checkout' });
})();
