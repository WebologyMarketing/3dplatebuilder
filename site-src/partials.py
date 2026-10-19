"""Shared page chrome for the [BRAND NAME] site generator."""
import json

FONTS = '<link rel="preconnect" href="https://fonts.googleapis.com">\n<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>\n<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400..800&amp;family=Geist+Mono:wght@500&amp;family=Archivo:wght@600..800&amp;display=swap">'

IMPORTMAP = '''<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<!-- Three.js r170, pinned exactly as the builder kit uses it (see plugin/README.md). -->
<script type="importmap">
{
	"imports": {
		"three": "https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.min.js",
		"three/addons/controls/OrbitControls.js": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/controls/OrbitControls.js",
		"three/addons/environments/RoomEnvironment.js": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/environments/RoomEnvironment.js",
		"three/addons/loaders/GLTFLoader.js": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/loaders/GLTFLoader.js",
		"three/addons/utils/BufferGeometryUtils.js": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/utils/BufferGeometryUtils.js"
	}
}
</script>'''

NAV = [
    ('website.html', 'What you get'),
    ('features.html', 'The 3D builder'),
    ('demo.html', 'Try it'),
    ('pricing.html', 'Pricing'),
    ('setup.html', 'How it works'),
]

BRAND_MARK = '''<svg class="brand-mark" width="40" height="26" viewBox="0 0 40 26" aria-hidden="true"><rect x="1" y="1" width="38" height="24" rx="4" fill="#F7D333" stroke="#fff" stroke-width="1.5"/><path d="M8 18V8m5 10V8m5 10V8m7 10V8m5 10V8" stroke="#111418" stroke-width="3" stroke-linecap="square"/></svg>'''


def header(current):
    links = []
    for href, label in NAV:
        cur = ' aria-current="page"' if href == current else ''
        links.append(f'<li><a href="{href}"{cur}>{label}</a></li>')
    cur = ' aria-current="page"' if current == 'start.html' else ''
    promo = '' if current == 'start.html' else '<div class="promo"><p>Sign up online in about five minutes. No calls, no demos to book. <a href="start.html">Start your shop <span aria-hidden="true">→</span></a></p></div>\n'
    return f'''<a class="skip" href="#main">Skip to content</a>
{promo}<header class="site-header">
	<div class="wrap">
		<a class="brand" href="index.html">{BRAND_MARK}<span>[BRAND NAME]</span></a>
		<button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="site-nav">Menu</button>
		<nav id="site-nav" class="site-nav" aria-label="Main">
			<ul>
				{''.join(links)}
				<li><a class="btn btn-primary" href="start.html"{cur}>Start your shop <span class="arr" aria-hidden="true">→</span></a></li>
			</ul>
		</nav>
	</div>
</header>'''


FOOTER = '''<footer class="site-footer">
	<div class="wrap">
		<div class="footer-grid">
			<div>
				<a class="brand" href="index.html">''' + BRAND_MARK + '''<span>[BRAND NAME]</span></a>
				<p style="margin-top:14px">Number plate shops with a 3D plate builder built in, for UK registered number plate suppliers.</p>
			</div>
			<div>
				<h2>Product</h2>
				<ul>
					<li><a href="website.html">What you get</a></li>
					<li><a href="features.html">The 3D builder</a></li>
					<li><a href="demo.html">Try the builder</a></li>
					<li><a href="pricing.html">Pricing</a></li>
					<li><a href="setup.html">How it works</a></li>
				</ul>
			</div>
			<div>
				<h2>Talk to us</h2>
				<ul>
					<li><a href="start.html">Start your shop</a></li>
					<li><a href="contact.html">Ask a question</a></li>
					<li><a href="mailto:[EMAIL]">[EMAIL]</a></li>
				</ul>
			</div>
			<div>
				<h2>Legal</h2>
				<ul>
					<li><a href="privacy.html">Privacy</a></li>
					<li><a href="terms.html">Terms</a></li>
					<li><a href="cookies.html">Cookies</a></li>
				</ul>
			</div>
		</div>
		<div class="footer-legal">
			<p>[COMPANY LEGAL NAME] trading as [BRAND NAME]. Registered in England and Wales, company number [COMPANY NUMBER]. Registered office: [REGISTERED ADDRESS].</p>
			<p>The builder helps with BS AU 145e character layout and legal spacing. It is not approved or certified by the DVLA. Each plate maker stays responsible for the plates they make, their RNPS registration and checking customers' documents.</p>
		</div>
	</div>
</footer>'''


def sticky_cta(filename):
    """Phones: a slim bar with the main action, shown once the visitor scrolls past the first screen."""
    if filename in ('start.html', 'contact.html', 'phone.html', 'desktop.html'):
        return ''
    return '''<div class="sticky-cta" data-sticky-cta hidden>
	<a class="btn btn-primary" href="start.html">Start your shop</a>
	<a class="btn btn-ghost" href="demo.html">Try the builder</a>
</div>'''


def page(filename, title, description, body, builder=False, extra_head=''):
    head_extra = IMPORTMAP if builder else ''
    scripts = '<script src="assets/js/site.js" defer></script>'
    if builder:
        scripts += '\n<script type="module" src="assets/js/demo.js"></script>'
    return f'''<!doctype html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{title}</title>
<meta name="claude:viewport-fit-sides" content="cover">
<meta name="description" content="{description}">
<meta name="theme-color" content="#ffffff">
<script>document.documentElement.classList.add('js')</script>
<link rel="canonical" href="https://[DOMAIN]/{'' if filename == 'index.html' else filename}">
<meta property="og:title" content="{title}">
<meta property="og:description" content="{description}">
<meta property="og:type" content="website">
<meta property="og:url" content="https://[DOMAIN]/{'' if filename == 'index.html' else filename}">
<meta property="og:image" content="https://[DOMAIN]/assets/img/og-image.jpg">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
{FONTS}
<link rel="stylesheet" href="assets/css/site.css">
{head_extra}
{extra_head}
</head>
<body>
{header(filename)}
<main id="main">
{body}
</main>
{sticky_cta(filename)}
{FOOTER}
{scripts}
</body>
</html>
'''


def builder_markup(mobile='launch', url_options=False, reg='', poster='assets/img/builder-desktop.webp', poster_alt='The 3D plate builder showing a front plate', url='yourplates.co.uk/design-your-plates', exit_href='index.html'):
    """The builder's own markup (from builder-kit/dev/index.html), white-labelled.
    demo.js writes data-pbb-config before the builder boots."""
    attrs = f'data-demo data-demo-mobile="{mobile}"'
    if url_options:
        attrs += ' data-demo-url'
    if reg:
        attrs += f' data-demo-reg="{reg}"'
    return f'''<div class="shop-window" data-demo-frame>
	<div class="shop-window-bar" aria-hidden="true"><span class="dots"><i></i><i></i><i></i></span><span class="url">{url}</span></div>
	<div class="shop-window-body">
		<img class="demo-poster" src="{poster}" alt="{poster_alt}" width="1280" height="900" loading="lazy" decoding="async">
		<img class="demo-teaser" src="assets/img/style-gel-4d-3.webp" alt="" width="640" height="480" loading="lazy" decoding="async">
		<p class="demo-loading" role="status">Loading the 3D builder…</p>
		<div class="pbb-builder" {attrs} data-pbb-config="{{}}">
	<div class="pbb-launcher">
		<button type="button" class="pbb-launch" data-pbb-launch>
			<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M12 2.5l8.5 4.75v9.5L12 21.5l-8.5-4.75v-9.5zM12 12l8.5-4.75M12 12v9.5M12 12L3.5 7.25"/></svg>
			<span><strong>Design your plates in 3D</strong><small>Enter your reg, pick your options and see them before you buy.</small></span>
		</button>
	</div>

	<header class="pbb-appbar">
		<a class="pbb-app-btn" href="{exit_href}" data-pbb-exit>
			<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" d="M15 5l-7 7 7 7"/></svg>
			<span class="pbb-sr-only">Leave the builder</span>
		</a>
		<img class="pbb-app-logo" src="demo-assets/img/your-plates-logo.svg" alt="Your Plates Ltd" width="220" height="40">
		<a class="pbb-app-btn pbb-app-cart" href="#">
			<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 8h12l-1 12H7L6 8zM9 8V6a3 3 0 0 1 6 0v2"/></svg>
			<span class="pbb-sr-only">Basket</span>
			<span class="pbb-app-count" data-pbb-cart-count>0</span>
		</a>
	</header>

	<div class="pbb-stage">
		<div class="pbb-viewport" data-pbb-viewport aria-label="3D number plate preview" role="img"></div>
		<div class="pbb-loader" aria-hidden="true"><span></span></div>
		<p class="pbb-hint" data-pbb-touch-hint="Drag to turn · Pinch or double-tap to zoom" data-pbb-wheel-hint="Click the plate or hold %s to zoom">Drag to turn · Double-click to zoom</p>
		<div class="pbb-toolbar" role="toolbar" aria-label="Preview controls">
			<div class="pbb-segmented" role="group" aria-label="Plate side">
				<button type="button" data-pbb-side="front" aria-pressed="true">Front</button>
				<button type="button" data-pbb-side="rear" aria-pressed="false">Rear</button>
			</div>
			<button type="button" class="pbb-view-btn" data-pbb-view aria-pressed="false" title="See it on a car">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M3 15.5V13l2-4.5A2 2 0 0 1 6.8 7.3h10.4A2 2 0 0 1 19 8.5L21 13v2.5a1 1 0 0 1-1 1h-1M5 16.5H4a1 1 0 0 1-1-1M9 16.5h6M3.5 12.5h17"/><circle cx="7" cy="16.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17" cy="16.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
				<span>Car</span>
			</button>
			<button type="button" class="pbb-icon-btn" data-pbb-action="reset" title="Reset view">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/></svg>
				<span class="pbb-sr-only">Reset view</span>
			</button>
		</div>
		<div class="pbb-carbar" data-pbb-carbar hidden>
			<label class="pbb-sr-only" for="pbb-car-1">Car</label>
			<select id="pbb-car-1" class="pbb-car-select" data-pbb-car></select>
			<div class="pbb-paints" role="radiogroup" aria-label="Paint colour" data-pbb-paints></div>
		</div>
		<p class="pbb-fallback">Your browser could not start the 3D preview. Please update your browser or enable hardware acceleration.</p>
	</div>

	<div class="pbb-steps" data-pbb-steps>
		<div class="pbb-stepnav" data-pbb-stepnav></div>
		<div class="pbb-track" data-pbb-track>
			<div class="pbb-step pbb-step-start" data-pbb-step="Your reg &amp; style">
				<div class="pbb-panel">
					<label class="pbb-field" for="pbb-reg-1"><span class="pbb-label">Your registration</span></label>
					<input id="pbb-reg-1" class="pbb-reg-input" type="text" inputmode="text" autocomplete="off" autocapitalize="characters" autocorrect="off" spellcheck="false" maxlength="8" placeholder="YOUR REG" aria-describedby="pbb-reg-status-1" data-pbb-input="registration">
					<p class="pbb-status" id="pbb-reg-status-1" data-pbb-output="status" aria-live="polite"></p>
				</div>
				<div class="pbb-section">
					<div class="pbb-section-head">
						<span class="pbb-label" id="pbb-style-label-1">Character style</span>
						<span class="pbb-legal" data-pbb-output="legal"></span>
					</div>
					<div class="pbb-styles" role="radiogroup" aria-labelledby="pbb-style-label-1" data-pbb-styles></div>
					<p class="pbb-style-desc" data-pbb-output="style-desc" aria-live="polite"></p>
				</div>
				<div class="pbb-section" data-pbb-accent-section hidden>
					<span class="pbb-label" id="pbb-accent-label-1">Effect colour</span>
					<div class="pbb-swatches" role="radiogroup" aria-labelledby="pbb-accent-label-1" data-pbb-accents></div>
				</div>
			</div>
			<div class="pbb-step" data-pbb-step="Plate size">
				<div class="pbb-section">
					<span class="pbb-label" id="pbb-size-label-1">Plate size</span>
					<div class="pbb-chips pbb-plates" role="radiogroup" aria-label="Plates" data-pbb-plates></div>
					<div class="pbb-options" role="radiogroup" aria-labelledby="pbb-size-label-1" data-pbb-sizes></div>
				</div>
			</div>
			<div class="pbb-step" data-pbb-step="Badge">
				<div class="pbb-section">
					<span class="pbb-label" id="pbb-badge-label-1">Badge</span>
					<div class="pbb-options pbb-badges" role="radiogroup" aria-labelledby="pbb-badge-label-1" data-pbb-badges></div>
					<p class="pbb-style-desc" data-pbb-output="badge-note" aria-live="polite"></p>
				</div>
			</div>
			<div class="pbb-step" data-pbb-step="Border">
				<div class="pbb-section">
					<span class="pbb-label" id="pbb-border-label-1">Border</span>
					<div class="pbb-chips" role="radiogroup" aria-labelledby="pbb-border-label-1" data-pbb-borders></div>
				</div>
			</div>
			<div class="pbb-step" data-pbb-step="Add-ons">
				<div class="pbb-section pbb-basket">
					<span class="pbb-label">Add-ons</span>
					<div class="pbb-extras" data-pbb-extras></div>
					<div class="pbb-summary" data-pbb-summary aria-live="polite"></div>
				</div>
			</div>
		</div>
	</div>

	<div class="pbb-buybar">
		<div class="pbb-total"><span>Total</span><strong data-pbb-total></strong></div>
		<button type="button" class="pbb-next" data-pbb-next>Next</button>
		<button type="button" class="pbb-add" data-pbb-add disabled>Continue to checkout</button>
	</div>
	<p class="pbb-cart-msg" data-pbb-cart-msg aria-live="polite"></p>
		</div>
		<p class="demo-failed">The 3D builder couldn't start here. It needs a recent browser with WebGL switched on. Try it on another device, or <a href="website.html">see what you get</a> with pictures of the builder.</p>
	</div>
</div>'''


def spec_svg(font_path, reg='AB12 CDE', plate='#F7D333', title='Rear plate, 520 by 111 millimetres, with the legal character spacing marked'):
    """A dimensioned drawing of a standard plate, built from the builder's own glyph outlines (mm)."""
    font = json.load(open(font_path))['glyphs']
    W, H, CH, GAP, GROUP = 520, 111, 79, 11, 33
    xs = []
    x = 0
    gap = 0
    for c in reg:
        if c == ' ':
            gap = GROUP
            continue
        x += gap
        xs.append((c, x, font[c]['w']))
        x += font[c]['w']
        gap = GAP
    ink = x
    ox = (W - ink) / 2
    oy = (H - CH) / 2  # top of characters (SVG y down)
    glyphs = ''.join(
        f'<path d="{font[c]["d"]}" transform="translate({ox + gx:.2f} {oy + CH:.2f}) scale(1 -1)"/>' for c, gx, w in xs)
    a0, a1 = xs[0], xs[1]           # A, B: character gap
    g0, g1 = xs[3], xs[4]           # 2, C: group gap
    s = lambda v: f'{v:.1f}'

    def hdim(x1, x2, y, label, ext_from=None, side=None):
        out = f'<path class="dim" d="M{s(x1)} {s(y)}H{s(x2)}M{s(x1)} {s(y-3)}v6M{s(x2)} {s(y-3)}v6"/>'
        if ext_from is not None:
            out += f'<path class="dim" stroke-dasharray="2 2" d="M{s(x1)} {s(ext_from)}V{s(y)}M{s(x2)} {s(ext_from)}V{s(y)}"/>'
        if side == 'left':
            out += f'<text class="dim-text" x="{s(x1-5)}" y="{s(y+4)}" text-anchor="end" font-size="12">{label}</text>'
        else:
            out += f'<text class="dim-text" x="{s((x1+x2)/2)}" y="{s(y-4)}" text-anchor="middle" font-size="12">{label}</text>'
        return out

    def vdim(x, y1, y2, label, anchor='start', dx=4):
        out = f'<path class="dim" d="M{s(x)} {s(y1)}V{s(y2)}M{s(x-3)} {s(y1)}h6M{s(x-3)} {s(y2)}h6"/>'
        out += f'<text class="dim-text" x="{s(x+dx)}" y="{s((y1+y2)/2+3)}" text-anchor="{anchor}" font-size="12">{label}</text>'
        return out

    char_top, char_bot = oy, oy + CH
    dims = ''
    dims += hdim(0, W, -14, '520 mm', ext_from=0)
    dims += vdim(W + 14, 0, H, '111 mm')
    dims += vdim(-14, char_top, char_bot, '79 mm', anchor='end', dx=-5)
    dims += f'<path class="dim" stroke-dasharray="2 2" d="M-14 {s(char_top)}H{s(ox)}M-14 {s(char_bot)}H{s(ox)}"/>'
    dims += hdim(ox + a0[1] + a0[2], ox + a1[1], H + 18, '11 mm', ext_from=char_bot, side='left')
    dims += hdim(ox + g0[1] + g0[2], ox + g1[1], H + 18, '33 mm', ext_from=char_bot)
    dims += hdim(ox + a1[1], ox + a1[1] + a1[2], H + 44, '50 mm', ext_from=char_bot)
    footer = (f'<text x="{W/2:.0f}" y="{H-4:.0f}" text-anchor="middle" font-family="Arial, sans-serif" font-size="6" font-weight="700" fill="#111418">YOUR PLATES LTD  AB1 2CD</text>'
              f'<text x="{W-14:.0f}" y="{H-4:.0f}" text-anchor="end" font-family="Arial, sans-serif" font-size="5.5" font-weight="700" fill="#111418">BS AU 145e</text>')
    return (f'<svg viewBox="-78 -40 690 210" role="img" aria-labelledby="spec-title"><title id="spec-title">{title}</title>'
            f'<rect x="0" y="0" width="{W}" height="{H}" rx="6" fill="{plate}"/>'
            f'<g fill="#111418">{glyphs}</g>{footer}{dims}</svg>')


# ------------------------------------------------------------------ live phone preview

PHONE_VIEWS = [
    ('reg=3D%20BLDR&style=gel-4d-3&side=rear', '4D gel, rear'),
    ('reg=RETRO&style=retro', 'Retro'),
    ('reg=3D%20BLDR&style=gel-3d&badge=uk-4d', 'UK 4D badge'),
    ('reg=3D%20BLDR&size=motorbike', 'Motorbike'),
    ('reg=3D%20BLDR&style=gel-4d-6&view=car', 'On a car'),
]


def phone_preview(src='phone.html', inherit=False, caption='The phone layout at iPhone 18 Pro Max size (440 × 956 points). Tap and swipe it like the real thing.', views=False):
    """A working iPhone-sized frame on computers: phone.html runs inside it at 440 x 860 (screen
    minus status bar and home indicator). Phones see a still of the same screen instead."""
    inherit_attr = ' data-phone-inherit' if inherit else ''
    chips = ''
    if views:
        chips = '<ul class="quick-views phone-views" aria-label="Show a design on the phone">' + ''.join(
            f'<li><button type="button" data-phone-view="{q}" aria-pressed="false">{label}</button></li>' for q, label in PHONE_VIEWS) + '</ul>'
    return f'''<div class="phone-block">
	<div class="phone-fit" data-phone>
		<div class="phone" role="group" aria-label="Phone preview">
			<span class="phone-btn phone-btn-action" aria-hidden="true"></span>
			<span class="phone-btn phone-btn-vol-up" aria-hidden="true"></span>
			<span class="phone-btn phone-btn-vol-down" aria-hidden="true"></span>
			<span class="phone-btn phone-btn-power" aria-hidden="true"></span>
			<span class="phone-btn phone-btn-camera" aria-hidden="true"></span>
			<div class="phone-screen">
				<div class="phone-status" aria-hidden="true">
					<span class="phone-time">9:41</span>
					<span class="phone-island"></span>
					<span class="phone-icons">
						<svg viewBox="0 0 18 12" width="18" height="12"><rect x="0" y="8" width="3" height="4" rx="1"/><rect x="5" y="5.5" width="3" height="6.5" rx="1"/><rect x="10" y="3" width="3" height="9" rx="1"/><rect x="15" y="0" width="3" height="12" rx="1"/></svg>
						<svg viewBox="0 0 16 12" width="16" height="12"><path d="M8 11.5 5.6 9a3.4 3.4 0 0 1 4.8 0zM3.5 6.9a6.4 6.4 0 0 1 9 0l-1.4 1.4a4.4 4.4 0 0 0-6.2 0zM1.3 4.7a9.5 9.5 0 0 1 13.4 0l-1.4 1.4a7.5 7.5 0 0 0-10.6 0z"/></svg>
						<svg viewBox="0 0 28 13" width="28" height="13"><rect x=".5" y=".5" width="24" height="12" rx="3.5" fill="none" stroke="currentColor" opacity=".45"/><rect x="2.5" y="2.5" width="17" height="8" rx="2"/><path d="M26 4.5v4a2 2 0 0 0 0-4z" opacity=".45"/></svg>
					</span>
				</div>
				<div class="phone-viewport">
					<img class="phone-poster" src="assets/img/phone-poster.webp" width="880" height="1720" alt="The builder on a phone: the plate at the top, the registration and style picker underneath, and the total with a Next button at the bottom" loading="lazy" decoding="async">
					<iframe class="phone-frame" title="The 3D plate builder on a phone (interactive)" data-src="{src}"{inherit_attr} width="440" height="860" tabindex="0"></iframe>
					<p class="phone-loading" role="status">Loading the phone builder…</p>
				</div>
				<div class="phone-home" aria-hidden="true"><span></span></div>
			</div>
		</div>
	</div>
	<p class="phone-caption">{caption}</p>
	{chips}
	<p class="phone-on-phone"><a class="btn btn-primary" href="phone.html">Open the builder full screen</a></p>
</div>'''


def phone_page(builder_html):
    """phone.html: only the builder, in its phone app layout. Loaded inside the phone frame."""
    return f'''<!doctype html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Phone builder | [BRAND NAME]</title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="builder/assets/css/builder.css?v=1.10.2" data-pbb-css>
{IMPORTMAP}
<style>
	html, body {{ margin: 0; height: 100%; background: #f4f5f6; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }}
	/* Same order panel styles as the main site, sized for the phone. */
	.demo-order {{ width: calc(100vw - 20px); max-height: calc(100dvh - 20px); padding: 0; border: 0; border-radius: 16px; background: #fff; color: #111418; overflow: auto; box-shadow: 0 20px 50px rgba(0,0,0,.4); }}
	.demo-order::backdrop {{ background: rgba(0,0,0,.6); }}
	.demo-order h2 {{ margin: 0; font-size: 22px; line-height: 1.1; text-transform: uppercase; font-weight: 800; }}
	.demo-order p {{ margin: 0; }}
	.demo-order-head {{ display: grid; gap: 6px; padding: 18px; background: #F7D333; font-size: 14px; }}
	.demo-order-kicker {{ font-weight: 800; font-size: 13px; }}
	.demo-order-lines {{ margin: 0; padding: 8px 18px; }}
	.demo-order-lines div {{ display: grid; grid-template-columns: 7.5em 1fr; gap: 10px; padding: 7px 0; border-bottom: 1px solid #e5e7ea; font-size: 14px; }}
	.demo-order-lines dt {{ color: #5b6168; }}
	.demo-order-lines dd {{ margin: 0; font-weight: 650; }}
	.demo-order-price {{ margin: 0 18px; padding: 10px 14px; border-radius: 10px; background: #f3f4f6; display: grid; gap: 4px; font-size: 14px; }}
	.demo-order-price div {{ display: flex; justify-content: space-between; gap: 12px; }}
	.demo-order-total {{ padding-top: 6px; border-top: 1px solid #d9dce0; font-weight: 800; }}
	.demo-order-note {{ margin: 12px 18px 0 !important; font-size: 13px; color: #3d434a; }}
	.demo-order-json {{ margin: 10px 18px 0; font-size: 13px; }}
	.demo-order-json pre {{ max-height: 180px; overflow: auto; padding: 10px; border-radius: 8px; background: #111418; color: #e8e8e3; font-size: 11px; }}
	.demo-order-actions {{ display: flex; flex-wrap: wrap; gap: 8px; padding: 16px 18px 18px; }}
	.demo-order .btn {{ display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 10px 16px; border-radius: 8px; border: 2px solid transparent; font: inherit; font-weight: 750; font-size: 15px; text-decoration: none; cursor: pointer; }}
	.demo-order .btn-primary {{ background: #F7D333; color: #111418; }}
	.demo-order .btn-ghost {{ background: #fff; color: #111418; border-color: #c5c9ce; }}
</style>
</head>
<body>
{builder_html}
<script>
	// Tell the page around the phone frame when the builder is ready (or can't start), so it can
	// swap the still image for the live phone. Only ever talks to this site.
	(() => {{
		if (window.parent === window) return;
		const started = Date.now();
		const tell = (state) => window.parent.postMessage({{ device: state }}, location.origin);
		const t = setInterval(() => {{
			const b = document.querySelector('.pbb-builder');
			if (b?.classList.contains('is-ready')) {{ clearInterval(t); tell('ready'); }}
			else if (b?.classList.contains('pbb-builder--no-webgl') || Date.now() - started > 30000) {{ clearInterval(t); tell('failed'); }}
		}}, 150);
		// The app bar's back arrow would leave the parent page: start the demo again instead.
		document.addEventListener('click', (e) => {{
			const exit = e.target.closest('[data-pbb-exit], .pbb-app-cart');
			if (!exit) return;
			e.preventDefault();
			e.stopPropagation();
			if (exit.hasAttribute('data-pbb-exit')) location.replace(location.href);
		}}, true);
	}})();
</script>
<script type="module" src="assets/js/demo.js"></script>
</body>
</html>
'''


# ------------------------------------------------------------------ live laptop preview

def laptop_preview(src='desktop.html', inherit=False, caption='The computer layout on a 14-inch MacBook Pro screen (1512 × 982, the default size). Scroll and click it like the real thing.'):
    """A 14-inch MacBook Pro (Space Black) at its default 1512 x 982 'looks like' size. desktop.html,
    a demo shop page with the builder, runs inside the browser window at 1512 x 898."""
    inherit_attr = ' data-phone-inherit' if inherit else ''
    return f'''<div class="laptop-block">
	<div class="laptop-fit" data-device data-w="1640" data-h="1052">
		<div class="laptop" role="group" aria-label="Laptop preview">
			<div class="laptop-lid">
				<div class="laptop-screen">
					<div class="mac-menubar" aria-hidden="true">
						<span class="mac-menu"><b>Safari</b><span>File</span><span>Edit</span><span>View</span><span>History</span><span>Bookmarks</span><span>Window</span><span>Help</span></span>
						<span class="mac-notch"></span>
						<span class="mac-clock">Sat 9:41</span>
					</div>
					<div class="mac-toolbar" aria-hidden="true">
						<span class="mac-lights"><i></i><i></i><i></i></span>
						<span class="mac-nav">
							<svg viewBox="0 0 24 24" width="22" height="22"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<svg viewBox="0 0 24 24" width="22" height="22" opacity=".35"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</span>
						<span class="mac-url"><svg viewBox="0 0 24 24" width="14" height="14"><rect x="5" y="11" width="14" height="9" rx="2" fill="currentColor"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="2.2"/></svg>yourplates.co.uk/design-your-plates</span>
						<span class="mac-tools"></span>
					</div>
					<div class="laptop-viewport">
						<img class="phone-poster" src="assets/img/desktop-poster.webp" width="1512" height="898" alt="A plate shop's Design your plates page on a laptop, with the 3D plate at the top and the options underneath" loading="lazy" decoding="async">
						<iframe class="phone-frame" title="The 3D plate builder on a computer (interactive)" data-src="{src}"{inherit_attr} width="1512" height="898" tabindex="0"></iframe>
						<p class="phone-loading" role="status">Loading the computer view…</p>
					</div>
				</div>
			</div>
			<div class="laptop-base" aria-hidden="true"><span></span></div>
		</div>
	</div>
	<p class="phone-caption laptop-caption">{caption}</p>
</div>'''


def desktop_page(builder_html):
    """desktop.html: a demo plate shop's 'Design your plates' page. Loaded inside the laptop frame."""
    page = phone_page(builder_html)
    shop_css = '''
	html, body { height: auto; }
	body { background: #fff; }
	.shop-head { background: #111418; color: #fff; }
	.shop-head .in, .shop-main, .shop-foot .in { max-width: 1160px; margin: 0 auto; padding: 0 32px; }
	.shop-head .in { display: flex; align-items: center; justify-content: space-between; height: 72px; }
	.shop-head img { height: 34px; width: auto; display: block; }
	.shop-head nav { display: flex; gap: 28px; font-size: 15px; font-weight: 600; }
	.shop-head nav a { color: #fff; text-decoration: none; opacity: .8; }
	.shop-head nav a[aria-current] { opacity: 1; box-shadow: inset 0 -2px 0 #F7D333; padding-bottom: 4px; }
	.shop-strip { background: #F7D333; color: #111418; text-align: center; font-size: 14px; font-weight: 700; padding: 8px; }
	.shop-main { padding-top: 36px; padding-bottom: 56px; }
	.shop-main h1 { margin: 0 0 6px; font-size: 34px; letter-spacing: -.01em; color: #111418; }
	.shop-main > p { margin: 0 0 24px; color: #5b6168; font-size: 16px; }
	.shop-foot { border-top: 1px solid #e5e7ea; color: #6b7178; font-size: 13px; }
	.shop-foot .in { padding-top: 22px; padding-bottom: 22px; }
'''
    page = page.replace('<title>Phone builder | [BRAND NAME]</title>', '<title>Desktop builder | [BRAND NAME]</title>')
    page = page.replace('</style>', shop_css + '</style>', 1)
    page = page.replace('\nhtml, body {', '\nhtml, body {')
    shop = f'''<header class="shop-head"><div class="in">
	<img src="demo-assets/img/your-plates-logo.svg" alt="Your Plates Ltd" width="220" height="40">
	<nav aria-label="Demo shop"><a href="#">Home</a><a href="#">Number plates</a><a href="#" aria-current="page">Design your plates</a><a href="#">Help</a><a href="#">Basket (0)</a></nav>
</div></header>
<p class="shop-strip">Road-legal plates made to BS AU 145e · Free UK delivery over £40 (demo shop)</p>
<main class="shop-main">
	<h1>Design your plates in 3D</h1>
	<p>Type your reg, choose your style and see your plates before you buy.</p>
	{builder_html}
</main>
<footer class="shop-foot"><div class="in">Your Plates Ltd · AB1 2CD · A demo shop for [BRAND NAME]</div></footer>'''
    page = page.replace(builder_html, shop, 1)
    # Inside the laptop, the demo shop's own links go nowhere.
    page = page.replace("const exit = e.target.closest('[data-pbb-exit], .pbb-app-cart');",
                        "const exit = e.target.closest('[data-pbb-exit], .pbb-app-cart, .shop-head a');")
    return page
