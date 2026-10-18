/**
 * Block checkout: show the documents panel in WooCommerce's order-meta slot.
 * Uses the window globals WordPress and WooCommerce Blocks provide, so no build step.
 *
 * The slot renders its fills later and elsewhere in the page (and can re-render them),
 * so the panel mounts through a callback ref: whenever WooCommerce puts the box in the
 * page, the panel fills it; when the box goes, the panel is torn down.
 */
(function () {
	'use strict';
	const { registerPlugin } = window.wp.plugins;
	const { createElement: el, useCallback, useRef } = window.wp.element;
	const { ExperimentalOrderMeta } = window.wc.blocksCheckout;

	// The slot passes its own props (cart, extensions…) to its children: take them here
	// so they don't end up as attributes on the HTML element.
	function DocumentsBox() {
		const ui = useRef(null);
		const attach = useCallback((node) => {
			if (ui.current) {
				ui.current.destroy();
				ui.current = null;
			}
			if (node) ui.current = window.PlateBuilderDocuments.mount(node);
		}, []);
		return el('div', { ref: attach, className: 'pbb-docs-block' });
	}

	function DocumentsPanel() {
		return el(ExperimentalOrderMeta, null, el(DocumentsBox));
	}

	registerPlugin('plate-builder-documents', { render: DocumentsPanel, scope: 'woocommerce-checkout' });
})();
