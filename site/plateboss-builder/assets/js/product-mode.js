/**
 * Shop product pages: switch between the 3D builder and the classic Product Add-Ons form.
 * The registration carries across both ways, and the choice is remembered on this device.
 * Classic mode is a body class (`pbb-classic`); builder.css shows and hides each side.
 */

const STORE = 'pbb-mode';

const remembered = () => {
	try {
		return localStorage.getItem(STORE);
	} catch (e) {
		return null;
	}
};
const remember = (mode) => {
	try {
		localStorage.setItem(STORE, mode);
	} catch (e) {
		/* private mode: just don't remember */
	}
};

// The add-ons form's registration box is its first text field.
const classicReg = () => document.querySelector('form.cart .pewc-item-text input[type="text"], form.cart .pewc-group-text input[type="text"]');

export function mountProductMode(root) {
	const switcher = document.querySelector('[data-pbb-modes]');
	if (!switcher) return;
	const builderReg = root.querySelector('[data-pbb-input="registration"]');

	const set = (mode, { carry = true, save = true } = {}) => {
		const classic = mode === 'classic';
		if (carry) {
			const from = classic ? builderReg : classicReg();
			const to = classic ? classicReg() : builderReg;
			if (from?.value && to && to.value.toUpperCase() !== from.value.toUpperCase()) {
				to.value = from.value.toUpperCase();
				['input', 'change', 'keyup'].forEach((type) => to.dispatchEvent(new Event(type, { bubbles: true })));
			}
		}
		document.body.classList.toggle('pbb-classic', classic);
		// The site's plate-style reg box sizes itself on load and resize; it was hidden until now
		// (0 px tall), so have it measure again once the form is showing.
		if (classic) requestAnimationFrame(() => window.dispatchEvent(new Event('resize')));
		switcher.querySelectorAll('[data-pbb-mode]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.pbbMode === mode)));
		if (save) remember(mode);
	};

	switcher.addEventListener('click', (e) => {
		const btn = e.target.closest('[data-pbb-mode]');
		if (btn) set(btn.dataset.pbbMode);
	});
	set(remembered() === 'classic' ? 'classic' : '3d', { carry: false, save: false });
}
