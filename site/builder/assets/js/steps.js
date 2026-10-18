/**
 * Phones: the builder fills the screen like an app ([plate_builder mobile="app"], the default),
 * or opens full screen from a "Design your plates" button (mobile="launch", used on product pages),
 * or stays in the page (mobile="inline"). The option sections become a row of cards under the preview.
 * Customers swipe (native scroll-snap) or use Back / Next; the buy bar's button
 * reads "Next: <step>" until the last card, where it becomes Continue to checkout.
 * Wider screens keep the stacked layout and none of this applies.
 */

const MOBILE = '(max-width: 760px)';

export function mountSteps(root, config = {}) {
	const track = root.querySelector('[data-pbb-track]');
	const nav = root.querySelector('[data-pbb-stepnav]');
	const nextBtn = root.querySelector('[data-pbb-next]');
	if (!track || !nav) return;

	const steps = [...track.querySelectorAll(':scope > .pbb-step')];
	const names = steps.map((s) => s.dataset.pbbStep);
	const mq = window.matchMedia(MOBILE);
	const mode = config.mobile || 'app';
	let launched = false;
	const isApp = () => mq.matches && (mode === 'app' || (mode === 'launch' && launched));
	let current = 0;

	nav.innerHTML = `
		<button type="button" class="pbb-step-back" data-pbb-back aria-label="Previous step">
			<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg>
			<span>Back</span>
		</button>
		<div class="pbb-step-title" aria-live="polite"><span>Design your plates</span><strong data-pbb-step-name></strong></div>
		<div class="pbb-dots">${names.map((n, i) => `<button type="button" data-pbb-dot="${i}" aria-label="${n}"></button>`).join('')}</div>`;
	const backBtn = nav.querySelector('[data-pbb-back]');
	const nameEl = nav.querySelector('[data-pbb-step-name]');
	const dots = [...nav.querySelectorAll('[data-pbb-dot]')];

	const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	// Cards sit a gap apart, so position by each card's offset rather than the track width.
	const offsetOf = (i) => steps[i].offsetLeft - steps[0].offsetLeft;
	const nearest = () => {
		let best = 0;
		steps.forEach((_, i) => {
			if (Math.abs(offsetOf(i) - track.scrollLeft) < Math.abs(offsetOf(best) - track.scrollLeft)) best = i;
		});
		return best;
	};
	const go = (i) => {
		i = Math.max(0, Math.min(steps.length - 1, i));
		track.scrollTo({ left: offsetOf(i), behavior: reduced() ? 'auto' : 'smooth' });
		setCurrent(i);
	};

	// The track is as tall as the card on show, so short steps don't leave a gap.
	// In app mode the track fills the space between preview and buy bar, and each card scrolls.
	const fitHeight = () => {
		track.style.height = mq.matches && !isApp() ? `${steps[current].offsetHeight}px` : '';
	};

	function setCurrent(i) {
		if (i !== current) root.dispatchEvent(new CustomEvent('pbb-stepchange', { detail: { step: i, name: names[i] } }));
		current = i;
		const last = i === steps.length - 1;
		root.classList.toggle('is-last-step', last);
		// No "Step 2 of 5": the progress bar shows how far along they are without counting steps.
		nameEl.textContent = names[i];
		backBtn.disabled = i === 0;
		dots.forEach((d, j) => {
			d.setAttribute('aria-current', j === i ? 'step' : 'false');
			d.classList.toggle('is-done', j < i);
		});
		if (nextBtn) nextBtn.textContent = last ? '' : `Next: ${names[i + 1]}`;
		// Off-screen cards are out of the tab order while swiping is in use.
		steps.forEach((s, j) => (s.inert = mq.matches && j !== i));
		fitHeight();
	}

	let frame = 0;
	track.addEventListener('scroll', () => {
		cancelAnimationFrame(frame);
		frame = requestAnimationFrame(() => {
			const i = nearest();
			if (i !== current) setCurrent(i);
		});
	}, { passive: true });

	backBtn.addEventListener('click', () => go(current - 1));
	nextBtn?.addEventListener('click', () => {
		// Leaving the registration card closes the phone keyboard so the plate is in view.
		if (current === 0) document.activeElement?.blur();
		go(current + 1);
	});
	dots.forEach((d, i) => d.addEventListener('click', () => go(i)));

	new ResizeObserver(fitHeight).observe(track);
	steps.forEach((s) => new ResizeObserver(() => s === steps[current] && fitHeight()).observe(s));

	// Keep the pinned preview below the theme's sticky header, and the buy bar above
	// its fixed bottom toolbar (e.g. Shop / Cart / Get in touch), whatever their size.
	const chromeAt = (y) => {
		for (let el = document.elementFromPoint(window.innerWidth / 2, y); el && el !== document.body; el = el.parentElement) {
			if (root.contains(el)) return null;
			const pos = getComputedStyle(el).position;
			if (pos === 'fixed' || pos === 'sticky') return el.getBoundingClientRect();
		}
		return null;
	};
	const header = () => {
		if (!mq.matches || isApp()) return;
		const top = chromeAt(1);
		const bottom = chromeAt(window.innerHeight - 2);
		root.style.setProperty('--pbb-sticky-top', `${Math.round(Math.max(0, top?.bottom ?? 0))}px`);
		root.style.setProperty('--pbb-sticky-bottom', `${Math.round(Math.max(0, bottom ? window.innerHeight - bottom.top : 0))}px`);
	};
	let headerFrame = 0;
	window.addEventListener('scroll', () => {
		cancelAnimationFrame(headerFrame);
		headerFrame = requestAnimationFrame(header);
	}, { passive: true });

	/* App mode: lift the builder out of the theme (a transformed parent would break position: fixed)
	   and size it to the visible screen, so the phone keyboard never covers the buy bar. */
	const home = document.createComment('pbb-builder');
	const exit = root.querySelector('[data-pbb-exit]');
	const fitScreen = () => {
		const vv = window.visualViewport;
		root.style.setProperty('--pbb-app-h', `${Math.round(vv ? vv.height : window.innerHeight)}px`);
		root.style.setProperty('--pbb-app-top', `${Math.round(vv ? vv.offsetTop : 0)}px`);
	};
	window.visualViewport?.addEventListener('resize', fitScreen);
	window.visualViewport?.addEventListener('scroll', fitScreen);
	window.addEventListener('resize', fitScreen);

	// Launch mode: the button opens the builder and adds a history entry, so the phone's
	// back button (and the app bar's arrow) closes it again instead of leaving the page.
	root.classList.toggle('is-launch', mode === 'launch');
	const launch = () => {
		launched = true;
		history.pushState({ pbbApp: true }, '');
		apply();
		// Teaser: they've already typed their reg in the page, so start on the next step.
		const reg = root.querySelector('[data-pbb-input="registration"]');
		if (config.teaser && reg?.value.trim() && current === 0) go(1);
	};
	root.querySelector('[data-pbb-launch]')?.addEventListener('click', launch);
	// Teaser: the 3D plate in the page is a big "open the builder" button too.
	root.querySelector('.pbb-stage')?.addEventListener('click', () => {
		if (config.teaser && mq.matches && !isApp()) launch();
	});
	// Closing: back to the page. Teasers show the reg box in the page again, so return to that step.
	const closeApp = () => {
		launched = false;
		apply();
		if (config.teaser) {
			track.scrollLeft = 0;
			setCurrent(0);
		}
	};
	window.addEventListener('popstate', () => {
		if (launched && !history.state?.pbbApp) closeApp();
	});

	// Back arrow: return to the page they came from on this site, otherwise go home.
	exit?.addEventListener('click', (e) => {
		if (mode === 'launch') {
			e.preventDefault();
			if (history.state?.pbbApp) history.back();
			else closeApp();
			return;
		}
		let sameSite = false;
		try {
			sameSite = !!document.referrer && new URL(document.referrer).origin === location.origin;
		} catch (err) { /* no referrer */ }
		if (sameSite && history.length > 1) {
			e.preventDefault();
			history.back();
		}
	});

	const setApp = (on) => {
		if (on === root.classList.contains('is-app')) return;
		if (on) {
			root.before(home);
			document.body.append(root);
			fitScreen();
		} else if (home.parentNode) {
			home.replaceWith(root);
		}
		root.classList.toggle('is-app', on);
		document.documentElement.classList.toggle('pbb-app-open', on);
	};

	const apply = () => {
		setApp(isApp());
		root.classList.toggle('is-steps', mq.matches);
		track.scrollLeft = mq.matches ? offsetOf(current) : 0;
		setCurrent(current);
		header();
	};
	mq.addEventListener('change', apply);
	apply();
}
