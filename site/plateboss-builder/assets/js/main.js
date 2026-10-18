import { PlateViewport } from './plate-viewport.js';
import { sanitiseInput, MAX_CHARACTERS } from './plate-layout.js';
import { mountCarControls, mountLegalBadge, mountPlateOptions, mountStylePicker } from './builder-ui.js';
import { mountBasket } from './basket.js';
import { mountSteps } from './steps.js';
import { mountProductMode } from './product-mode.js';
import { fixedSide } from './plate-sizes.js';

/**
 * Boots every [plateboss_builder] on the page and wires the controls.
 * Instances are exposed on window.PlateBoss for the later steps (styles, cart).
 */

const instances = [];

function init(root) {
	if (root.pbbViewport) return;

	let config = {};
	try {
		config = JSON.parse(root.dataset.pbbConfig || '{}');
	} catch (e) {
		console.warn('[PlateBoss] Invalid builder config', e);
	}

	let viewport;
	try {
		viewport = new PlateViewport(root.querySelector('[data-pbb-viewport]'), config);
	} catch (e) {
		console.error('[PlateBoss] WebGL unavailable', e);
		root.classList.add('pbb-builder--no-webgl');
		return;
	}
	root.pbbViewport = viewport;
	instances.push(viewport);

	// Touch screens zoom by pinching, not scrolling.
	const hint = root.querySelector('[data-pbb-touch-hint]');
	if (hint && window.matchMedia('(pointer: coarse)').matches) hint.textContent = hint.dataset.pbbTouchHint;

	// Desktop: scrolling over the preview scrolls the page; say how to zoom instead.
	let nudge = 0;
	viewport.addEventListener('wheelblocked', () => {
		if (!hint) return;
		const mac = /Mac|iPhone|iPad/.test(navigator.platform);
		hint.textContent = hint.dataset.pbbWheelHint.replace('%s', mac ? '⌘' : 'Ctrl');
		root.classList.add('is-nudging');
		clearTimeout(nudge);
		nudge = setTimeout(() => root.classList.remove('is-nudging'), 1600);
	});

	/* Plate side + view */
	const sideButtons = root.querySelectorAll('[data-pbb-side]');
	const syncSide = () => {
		root.dataset.side = viewport.side;
		sideButtons.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.pbbSide === viewport.side)));
	};

	root.addEventListener('click', (e) => {
		const sideBtn = e.target.closest('[data-pbb-side]');
		if (sideBtn) viewport.setSide(sideBtn.dataset.pbbSide);

		const action = e.target.closest('[data-pbb-action]')?.dataset.pbbAction;
		if (action === 'reset') viewport.resetView();
	});

	viewport.addEventListener('sidechange', syncSide);
	viewport.addEventListener('ready', () => root.classList.add('is-ready'), { once: true });
	viewport.addEventListener('interactionstart', () => root.classList.add('has-interacted'), { once: true });
	syncSide();

	/* Registration input */
	const input = root.querySelector('[data-pbb-input="registration"]');
	const status = root.querySelector('[data-pbb-output="status"]');

	if (input) {
		input.value = sanitiseInput(config.registration ?? '');
		input.addEventListener('input', () => {
			const clean = sanitiseInput(input.value);
			if (clean !== input.value) {
				// Keep the caret where the customer is typing after we clean the value.
				const caret = sanitiseInput(input.value.slice(0, input.selectionStart ?? input.value.length)).length;
				input.value = clean;
				input.setSelectionRange(caret, caret);
			}
			viewport.setRegistration(clean);
			// Once they stop typing, swing to the close-up so the characters show in profile.
			clearTimeout(typedTimer);
			typedTimer = setTimeout(() => {
				if (clean.length >= 2 && !viewport.interacting && viewport.showcased !== 'style') viewport.showcaseStyle();
			}, 900);
		});
		let typedTimer = 0;
		viewport.setRegistration(input.value);
	}

	mountStylePicker(root, viewport);
	mountPlateOptions(root, viewport);
	mountLegalBadge(root, viewport);
	mountBasket(root, viewport, config);
	mountSteps(root, config);
	if (config.productMode) mountProductMode(root);
	// Leaving a step after a style close-up: back to the full plate.
	root.addEventListener('pbb-stepchange', () => viewport.showcased && viewport.resetView());
	mountCarControls(root, viewport);

	viewport.addEventListener('layoutchange', ({ detail }) => {
		root.classList.toggle('is-empty', detail.placeholder);
		if (!status) return;

		if (detail.placeholder) {
			status.textContent = `Up to ${MAX_CHARACTERS} letters and numbers. We'll add the legal spacing for you.`;
			status.dataset.state = 'idle';
			return;
		}
		const { plate, size, fits, layout } = detail;
		const name = size.label.toLowerCase();
		const badge = viewport.options.badge !== 'none';
		status.dataset.state = fits ? 'ok' : 'error';
		if (fits) {
			status.textContent = size.width === 'auto'
				? `Legal spacing applied. ${size.label} plate cut to ${plate.width} × ${plate.height} mm.`
				: `Legal spacing applied on a ${plate.width} × ${plate.height} mm ${name} plate.`;
		} else if (badge) {
			status.textContent = `This registration doesn't fit a ${name} plate with a badge. Remove the badge or choose a longer plate.`;
		} else {
			status.textContent = `This registration doesn't fit a ${name} plate with legal margins (needs ${layout.needWidth} mm).`;
		}
		// Different front and rear sizes: the plate off screen has to fit too.
		if (fits && !detail.otherFits) {
			status.dataset.state = 'error';
			status.textContent = `This registration doesn't fit your ${detail.otherSide} plate (${detail.otherSize.label.toLowerCase()}). Choose another ${detail.otherSide} size.`;
		}
	});

	// Some sizes have one face only: motorbikes and trailers are yellow, commercial trailers white.
	viewport.addEventListener('configchange', () => {
		const fixed = fixedSide(viewport.size);
		sideButtons.forEach((b) => (b.disabled = Boolean(fixed) && b.dataset.pbbSide !== fixed));
		syncSide();
	});
}

document.querySelectorAll('.pbb-builder').forEach(init);

/**
 * The sticky preview on computers sits below the site's own sticky header, whose height changes
 * as it shrinks on scroll: measure whatever is fixed or sticky at the top of the window.
 */
function trackStickyHeader() {
	if (!document.querySelector('.pbb-builder')) return;
	let queued = false;
	const measure = () => {
		queued = false;
		let bottom = 0;
		for (let el = document.elementFromPoint(window.innerWidth / 2, 2); el && el !== document.body; el = el.parentElement) {
			if (el.closest('.pbb-builder')) break;
			const pos = getComputedStyle(el).position;
			if (pos === 'fixed' || pos === 'sticky') bottom = Math.max(bottom, el.getBoundingClientRect().bottom);
		}
		document.documentElement.style.setProperty('--pbb-sticky-top', `${Math.round(Math.max(0, bottom))}px`);
	};
	const queue = () => {
		if (!queued) (queued = true), requestAnimationFrame(measure);
	};
	window.addEventListener('scroll', queue, { passive: true });
	window.addEventListener('resize', queue);
	measure();
}
trackStickyHeader();

window.PlateBoss = Object.assign(window.PlateBoss || {}, { instances, init });
