"""What you get: the website, how far we customise it, everything it does, the rendering."""
from blocks import render_showcase, process_strip

ALL, PRO, COMPLETE = 'All plans', 'Pro and Complete', 'Complete'

PAGES = [
    ('Home page', 'Your reg box, plate styles with prices, the car view and how to order.', ALL),
    ('Design your plates', 'The full 3D builder on its own page, linked from your menu.', ALL),
    ('Valid documents', 'What customers need to show for road-legal plates.', ALL),
    ('About and contact', 'Your story, address, opening times and how to reach you.', ALL),
    ('Legal pages', 'Privacy, terms, delivery and returns, for you to check.', ALL),
    ('Product pages with the builder', 'Every plate product opens in 3D, with a switch back to the classic form.', PRO),
    ('A page for each plate type', '4D, gel, show plates, motorbike and more, each with its own builder start point.', PRO),
    ('Pages for the towns you serve', 'Local pages so people nearby find you on Google.', COMPLETE),
    ('Launch articles', 'Articles written for plate buyers, to bring in searches.', COMPLETE),
]

LEVELS = [
    ('Starter', 'Our plate-shop design, in your brand',
     ['Your logo, colours and wording', 'Our proven plate-shop layout', 'Up to [STARTER PAGES] pages and [STARTER PRODUCTS] products']),
    ('Pro', 'A home page designed for your shop',
     ['Everything in Starter', 'A home page designed around your shop', 'Landing pages for each plate type', 'Your product photos edited and laid out', 'Up to [PRO PAGES] pages and [PRO PRODUCTS] products']),
    ('Complete', 'A fully custom website',
     ['Everything in Pro', 'Designed from scratch for your shop', 'Up to [COMPLETE PAGES] pages, unlimited products', 'Your current site moved across', '[COMPLETE MONTHLY HOURS] hours of changes every month']),
]

FUNCTIONS = [
    ('Selling', [
        ('Real-time 3D builder: styles, sizes, front and rear', ALL),
        ('Basket lines that spell out every detail', ALL),
        ('Your prices, edited on one screen', ALL),
        ('Badges, borders and different front and rear sizes', ALL),
        ('The "on a car" view', PRO),
        ('Builder on every product page', PRO),
    ]),
    ('Paying', [
        ('Card payments through your own Stripe account', ALL),
        ('Checkout inside the builder', COMPLETE),
        ('Apple Pay and Google Pay', COMPLETE),
        ('Collect in store and in-store fitting', COMPLETE),
    ]),
    ('Staying legal', [
        ('BS AU 145e spacing in Charles Wright characters', ALL),
        ('Your supplier name and postcode on every plate', ALL),
        ('Road legal or show plate, on every order', ALL),
        ('Proof of ID and entitlement at checkout', COMPLETE),
        ('Orders held as Awaiting documents until checked', COMPLETE),
    ]),
    ('Being found', [
        ('Google Analytics and Search Console set up', ALL),
        ('Search basics on every page', PRO),
        ('Your Google reviews on the site', PRO),
        ('Local town pages and launch articles', COMPLETE),
    ]),
    ('Looked after', [
        ('Hosting, security certificate and daily backups', ALL),
        ('WordPress, WooCommerce and builder updates', ALL),
        ('Support by email', ALL),
        ('Monthly report on visits and orders', COMPLETE),
        ('Support tickets from your shop\'s admin', 'Coming soon'),
    ]),
]


def tag(t):
    cls = {'All plans': 'tag-all', 'Pro and Complete': 'tag-pro', 'Complete': 'tag-complete'}.get(t, 'tag-soon')
    return f'<span class="plan-tag {cls}">{t}</span>'


def colour_preview():
    """A tiny shop mock-up the visitor can recolour, to show how far branding goes."""
    swatches = [('#f7d333', '#111418', 'Yellow'), ('#e4002b', '#14171c', 'Red'), ('#1f6feb', '#0b1d3a', 'Blue'), ('#18a957', '#0f2a1d', 'Green'), ('#ff7a00', '#1a1208', 'Orange')]
    btns = ''.join(f'<button type="button" class="swatch" style="--sw:{a}" data-accent="{a}" data-dark="{d}" aria-pressed="{str(i == 0).lower()}"><span class="sr-only">{n}</span></button>' for i, (a, d, n) in enumerate(swatches))
    return f'''<div class="brand-demo" data-brand-demo style="--b-accent:#f7d333;--b-dark:#111418">
	<div class="browser">
		<div class="browser-bar" aria-hidden="true"><span class="dots"><i></i><i></i><i></i></span><span class="url">yourshop.co.uk</span></div>
		<div class="mini-shop" aria-hidden="true">
			<div class="ms-top"><span class="ms-logo">YOUR SHOP</span><span class="ms-nav"><i></i><i></i><i></i><i></i></span><span class="ms-basket">Basket</span></div>
			<div class="ms-strip">Road-legal plates · Collect today · UK delivery</div>
			<div class="ms-hero">
				<div class="ms-copy"><b>Number plates, made while you wait</b><span class="ms-reg">3D BLDR</span><span class="ms-btn">Design in 3D</span></div>
				<img src="assets/img/hero-rear.webp" width="881" height="500" alt="" loading="lazy" decoding="async">
			</div>
			<div class="ms-cards"><i></i><i></i><i></i></div>
		</div>
	</div>
	<div class="brand-controls">
		<p><b>Try your colours</b></p>
		<div class="swatches" role="group" aria-label="Brand colour">{btns}</div>
		<label class="own-colour">Or pick your own <input type="color" value="#f7d333" data-own-colour></label>
	</div>
</div>
<script>
	(() => {{
		const box = document.querySelector('[data-brand-demo]');
		if (!box) return;
		const set = (a, d) => {{ box.style.setProperty('--b-accent', a); if (d) box.style.setProperty('--b-dark', d); }};
		box.querySelectorAll('.swatch').forEach((b) => b.addEventListener('click', () => {{
			box.querySelectorAll('.swatch').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
			set(b.dataset.accent, b.dataset.dark);
		}}));
		box.querySelector('[data-own-colour]').addEventListener('input', (e) => {{
			box.querySelectorAll('.swatch').forEach((x) => x.setAttribute('aria-pressed', 'false'));
			set(e.target.value);
		}});
	}})();
</script>'''


def website():
    pages = ''.join(f'<li><div><h3>{h}</h3><p>{t}</p></div>{tag(w)}</li>' for h, t, w in PAGES)
    levels = ''.join(
        f'<li class="level"><p class="level-name">{n}</p><h3>{h}</h3><ul>' + ''.join(f'<li>{i}</li>' for i in items) +
        f'</ul><a class="btn btn-ghost" href="start.html?plan={n.lower()}">Start with {n}</a></li>' for n, h, items in LEVELS)
    funcs = ''.join(
        f'<div class="func-group"><h3>{g}</h3><ul>' + ''.join(f'<li><span>{t}</span>{tag(w)}</li>' for t, w in items) + '</ul></div>' for g, items in FUNCTIONS)
    return f"""
<section class="page-head hero-web studio" aria-labelledby="w-title">
	<div class="wrap hero-grid">
		<div class="hero-copy">
			<p class="eyebrow">What you get</p>
			<h1 id="w-title">A plate shop that sells, built and run for you</h1>
			<p class="lede">A complete number plate website, with plates that look real in 3D, designed in your brand, hosted and looked after. Here's exactly what's in it.</p>
			<div class="actions">
				<a class="btn btn-primary btn-lg" href="start.html">Start your shop</a>
				<a class="btn btn-ghost btn-lg" href="pricing.html">See prices</a>
			</div>
			<p class="price-anchor">From <b>[£ STARTER MONTHLY] a month</b> + [£ STARTER SET-UP] set-up. Prices exclude VAT.</p>
		</div>
		<figure class="hero-web-art">
			<div class="browser">
				<div class="browser-bar" aria-hidden="true"><span class="dots"><i></i><i></i><i></i></span><span class="url">yourplates.co.uk</span></div>
				<div class="browser-view"><img src="assets/img/shop-home.webp" width="1200" height="2172" alt="The home page of a demo plate shop we built" fetchpriority="high"></div>
			</div>
			<div class="mini-phone" aria-hidden="true"><img src="assets/img/phone-poster.webp" width="880" height="1720" alt=""></div>
		</figure>
	</div>
</section>

<section class="section" aria-labelledby="usp-title">
	<div class="wrap">
		<div class="intro"><h2 id="usp-title">Five reasons it sells plates</h2></div>
		{bento()}
	</div>
</section>

<section class="section studio" id="pages" aria-labelledby="pages-title">
	<div class="wrap web-split">
		<div class="stack">
			<p class="eyebrow">The website</p>
			<h2 id="pages-title">Every page a plate shop needs</h2>
			<p class="lede muted">Your shop is a WordPress and WooCommerce website, built around the 3D builder. These are the pages you get, and which plans include them.</p>
			<ul class="page-list">{pages}</ul>
		</div>
		<figure class="browser browser-scroll" tabindex="0" aria-label="A demo shop's home page. Hover or focus to scroll it.">
			<div class="browser-bar" aria-hidden="true"><span class="dots"><i></i><i></i><i></i></span><span class="url">yourplates.co.uk</span></div>
			<div class="browser-view"><img src="assets/img/shop-home.webp" width="1200" height="2172" alt="The full home page of the demo shop, Your Plates Ltd" loading="lazy" decoding="async"></div>
			<figcaption>A demo shop. Yours carries your name, colours and prices.</figcaption>
		</figure>
	</div>
</section>

<section class="section" id="custom" aria-labelledby="custom-title">
	<div class="wrap">
		<div class="intro">
			<p class="eyebrow">Customisation</p>
			<h2 id="custom-title">As much of your brand as you want</h2>
			<p>Every shop gets your logo, colours and supplier details, in the builder too: its buttons, checkout and plate footer. How far the design goes depends on your plan.</p>
		</div>
		{colour_preview()}
		<ol class="levels">{levels}</ol>
	</div>
</section>

{render_showcase('w-render-title')}

<section class="section studio" id="features" aria-labelledby="func-title">
	<div class="wrap">
		<div class="intro">
			<p class="eyebrow">Functionality</p>
			<h2 id="func-title">Everything it does</h2>
			<p>From the first turn of the plate to the order landing in your WooCommerce admin. <a href="pricing.html">Compare plans</a> or <a href="features.html">read about the builder in detail</a>.</p>
		</div>
		<div class="funcs">{funcs}</div>
	</div>
</section>

{process_strip('w-process-title')}
"""


def bento():
    from pages_home import bento as _b
    return _b()
