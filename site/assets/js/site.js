/* Site behaviour: phone menu and the Request a demo form. No libraries. */

/* Phone menu */
const toggle = document.querySelector('[data-nav-toggle]');
const nav = document.getElementById('site-nav');
if (toggle && nav) {
	toggle.addEventListener('click', () => {
		const open = toggle.getAttribute('aria-expanded') !== 'true';
		toggle.setAttribute('aria-expanded', String(open));
		nav.classList.toggle('is-open', open);
	});
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
			toggle.setAttribute('aria-expanded', 'false');
			nav.classList.remove('is-open');
			toggle.focus();
		}
	});
}

/* Request a demo form: posts to the endpoint in the form's action. Until https://3dplatebuilder.com/contact.php
   is filled in, or if the post fails, it opens the visitor's email app instead (mailto fallback). */
const form = document.querySelector('[data-demo-form]');
if (form) {
	const status = form.querySelector('[data-form-status]');
	const button = form.querySelector('button[type="submit"]');
	const email = form.dataset.email;

	const mailto = (data) => {
		const lines = [
			`Name: ${data.get('name')}`,
			`Business: ${data.get('business')}`,
			`Website: ${data.get('website') || '-'}`,
			`Current platform: ${data.get('platform')}`,
			`Plate orders a month: ${data.get('orders')}`,
			`Email: ${data.get('email')}`,
			'',
			data.get('message') || '',
		];
		const subject = `Demo request: ${data.get('business')}`;
		return `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(lines.join('\n'))}`;
	};

	const say = (text, state) => {
		status.textContent = text;
		status.dataset.state = state;
	};

	form.addEventListener('submit', async (e) => {
		e.preventDefault();
		if (!form.reportValidity()) return;
		const data = new FormData(form);
		if (data.get('company_url')) return; // honeypot: bots fill the hidden field

		const endpoint = form.getAttribute('action') || '';
		const configured = /^https?:\/\//.test(endpoint);
		if (!configured) {
			say(`Opening your email app. If nothing happens, email us at ${email}.`, 'info');
			window.location.href = mailto(data);
			return;
		}

		button.disabled = true;
		say('Sending…', 'info');
		try {
			const res = await fetch(endpoint, { method: 'POST', body: data, headers: { Accept: 'application/json' } });
			if (!res.ok) throw new Error(String(res.status));
			form.reset();
			say("Thanks, we've got your request. We'll email you within one working day with your demo.", 'ok');
		} catch (err) {
			say(`We couldn't send the form. Opening your email app instead; or email us at ${email}.`, 'error');
			window.location.href = mailto(data);
		} finally {
			button.disabled = false;
		}
	});
}

/* Live device previews (the iPhone and the MacBook): scale each frame to fit, load its page
   (phone.html / desktop.html) when it scrolls into view on computers and tablets, and swap the
   still for the live builder once that page says it's ready. */
const devices = [...document.querySelectorAll('[data-phone], [data-device]')];
const bigScreen = () => window.matchMedia('(min-width: 761px)').matches;

function fitDevice(fit) {
	const w = Number(fit.dataset.w) || 468;
	const h = Number(fit.dataset.h) || 984;
	const room = fit.parentElement.clientWidth || w;
	const maxH = Math.min(window.innerHeight * 0.84, h);
	const s = Math.max(0.15, Math.min(1, room / w, maxH / h));
	fit.style.setProperty('--s', s.toFixed(4));
}

function deviceSrc(frame, query) {
	const base = frame.dataset.src;
	const q = query ?? (frame.hasAttribute('data-phone-inherit') ? location.search.slice(1) : '');
	return q ? `${base}${base.includes('?') ? '&' : '?'}${q}` : base;
}

function loadDevice(fit, query) {
	if (!bigScreen()) return;
	const frame = fit.querySelector('.phone-frame');
	fit.classList.remove('is-live');
	fit.classList.add('is-loading');
	frame.src = deviceSrc(frame, query);
	fit.dataset.loaded = '1';
}

window.addEventListener('message', (e) => {
	if (e.origin !== location.origin || !e.data?.device) return;
	const fit = devices.find((f) => f.querySelector('.phone-frame').contentWindow === e.source);
	if (!fit) return;
	fit.classList.remove('is-loading');
	fit.classList.toggle('is-live', e.data.device === 'ready');
});

const refitAll = () => devices.forEach(fitDevice);
if (devices.length) {
	refitAll();
	window.addEventListener('resize', refitAll);
	new ResizeObserver(refitAll).observe(document.body);

	const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
		entries.forEach((en) => {
			if (en.isIntersecting && !en.target.dataset.loaded) loadDevice(en.target);
		});
	}, { rootMargin: '200px 0px' }) : null;
	devices.forEach((fit) => (io ? io.observe(fit) : loadDevice(fit)));

	// "Show a design" buttons under the phone.
	document.querySelectorAll('.phone-block').forEach((block) => {
		const fit = block.querySelector('[data-phone]');
		block.querySelectorAll('[data-phone-view]').forEach((btn) => btn.addEventListener('click', () => {
			block.querySelectorAll('[data-phone-view]').forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
			loadDevice(fit, btn.dataset.phoneView);
		}));
	});
}

/* "See it on a computer": the laptop stays hidden (and unloaded) until asked for. #desktop opens it. */
const openers = [...document.querySelectorAll('[data-reveal]')];
function setReveal(id, open, { scroll = true } = {}) {
	const panel = document.getElementById(id);
	if (!panel) return;
	panel.hidden = !open;
	openers.filter((b) => b.dataset.reveal === id).forEach((b) => {
		b.setAttribute('aria-expanded', String(open));
		b.textContent = open ? 'Hide the computer view' : 'See it on a computer';
	});
	refitAll();
	if (open && scroll) panel.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
	// Keep the choice when picking a ready-made design on the demo page.
	document.querySelectorAll('.quick-views a').forEach((a) => {
		a.href = a.getAttribute('href').split('#')[0] + (open ? '#desktop' : '');
	});
	try { history.replaceState(null, '', open ? '#desktop' : location.pathname + location.search); } catch (e) { /* ignore */ }
}
openers.forEach((b) => b.addEventListener('click', () => setReveal(b.dataset.reveal, b.getAttribute('aria-expanded') !== 'true')));
document.querySelectorAll('[data-reveal-close]').forEach((b) => b.addEventListener('click', () => {
	setReveal(b.dataset.revealClose, false);
	document.querySelector(`[data-reveal="${b.dataset.revealClose}"]`)?.focus();
}));
if (location.hash === '#desktop' && document.getElementById('desktop-view')) setReveal('desktop-view', true, { scroll: true });

/* Pricing: "Website + builder" / "Builder only". pricing.html#builder-only opens on builder only. */
const pricing = document.querySelector('[data-pricing]');
if (pricing) {
	const modeButtons = [...pricing.querySelectorAll('[data-pricing-mode]')];
	const setMode = (mode) => {
		pricing.dataset.pricing = mode;
		modeButtons.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.pricingMode === mode)));
		pricing.querySelectorAll('[data-for]').forEach((el) => (el.hidden = el.dataset.for !== mode));
	};
	modeButtons.forEach((b) => b.addEventListener('click', () => setMode(b.dataset.pricingMode)));
	if (location.hash === '#builder-only' || location.hash === '#outright-builder') setMode('builder');
}

/* Phones: show the bottom action bar once the first screen has scrolled away, and hide it
   while the yellow call-to-action band (which has the same buttons) is on screen. */
const sticky = document.querySelector('[data-sticky-cta]');
if (sticky && 'IntersectionObserver' in window) {
	const hero = document.querySelector('main > section');
	const band = document.querySelector('.plate-band');
	let pastHero = false;
	let bandShowing = false;
	const update = () => { sticky.hidden = !pastHero || bandShowing; };
	if (hero) new IntersectionObserver(([en]) => { pastHero = !en.isIntersecting; update(); }).observe(hero);
	if (band) new IntersectionObserver(([en]) => { bandShowing = en.isIntersecting; update(); }).observe(band);
}
