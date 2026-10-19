"""Home page (2026-10, concept A): a dark hero panel with the builder animating on an iPhone,
five reasons as a bento grid, the rendering panel, the live builder to try, the website, steps,
plans and questions. Copy in UK English; nothing here may promise what isn't built."""
from partials import phone_preview, laptop_preview
from blocks import USPS, process_strip
from pages_main import plan_summary, cta_band, shops_strip, faq_items

# The styles the demo phone and the rendering panel cycle through:
# (key, label, image, demo price, line for the rendering panel, chip icon CSS)
STYLES = [
    ('printed', 'Printed', 'style-printed.webp', '£29.99', 'The everyday road-legal plate: crisp, flat characters under a soft sheen.', 'width:18px;height:2px;border-radius:1px'),
    ('gel-3d', '3D Gel', 'style-gel-3d.webp', '£49.99', 'Domed resin characters that catch the light from every angle.', 'width:16px;height:6px;border-radius:6px 6px 1px 1px'),
    ('gel-4d-3', '4D Gel', 'style-gel-4d-3.webp', '£64.99', 'Laser-cut 3 mm characters with a clear gel top. Sharp edges, glossy face.', 'width:16px;height:7px;border-radius:3px 3px 1px 1px'),
    ('acrylic-4d-6', '4D 6 mm', 'style-acrylic-4d-6.webp', '£64.99', 'Deep acrylic characters at their true 6 mm thickness, casting real shadows.', 'width:16px;height:9px;border-radius:1px'),
    ('retro', 'Retro', 'style-retro.webp', '£69.99', 'Pressed, chamfered characters, like a classic plate.', 'width:0;height:0;background:none!important;border-left:9px solid transparent;border-right:9px solid transparent;border-bottom:7px solid #111418'),
]

TICK = '<span class="tick" aria-hidden="true"><svg width="12" height="12" viewBox="0 0 12 12"><path d="M2 6.5 5 9l5-6" fill="none" stroke="#111418" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>'


def tick(delay):
    return TICK.replace('<span class="tick"', f'<span class="tick" style="--d:{delay}s"')


def demo_phone():
    """An iPhone showing the builder at work: the reg types itself, then the styles cycle.
    A recording-like demo (site.js drives it); the live builder is further down the page."""
    imgs = ''.join(f'<img class="dphone-img{" is-on" if i == 0 else ""}" src="assets/img/{img}" width="640" height="480" alt="" data-dp-img>' for i, (_, _, img, _, _, _) in enumerate(STYLES))
    chips = ''.join(f'<span class="dphone-chip" data-dp-chip><i style="{icon}"></i>{label}</span>' for _, label, _, _, _, icon in STYLES)
    prices = '|'.join(p for _, _, _, p, _, _ in STYLES)
    return f'''<div class="dphone is-typing" data-demo-phone data-prices="{prices}" role="img" aria-label="The 3D plate builder on an iPhone: the registration 3D BLDR is typed in and the plate is shown in each style, from printed to 4D gel and retro">
	<div class="dphone-screen" aria-hidden="true">
		<div class="dphone-island"></div>
		<div class="dphone-top">
			<div class="dphone-bar">
				<svg width="20" height="20" viewBox="0 0 20 20"><path d="M12.5 4 6.5 10l6 6" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<span class="dphone-shop"><span>YP</span>Your Plates Ltd</span>
				<span class="dphone-basket"><svg width="22" height="22" viewBox="0 0 22 22"><path d="M5 8h12l-1 10H6L5 8Zm3 0V6a3 3 0 0 1 6 0v2" fill="none" stroke="#fff" stroke-width="1.8" stroke-linejoin="round"/></svg><b>0</b></span>
			</div>
			<div class="dphone-view"><div class="dphone-sway">{imgs}<div class="dphone-sheen"></div></div></div>
			<div class="dphone-seg"><span>Front</span><span class="on">Rear</span><span>Car</span></div>
		</div>
		<div class="dphone-sheet">
			<div class="dphone-progress"><i></i><i></i><i></i><i></i><i></i></div>
			<p class="dphone-kicker">DESIGN YOUR PLATES</p>
			<p class="dphone-title">Your reg &amp; style</p>
			<div class="dphone-reg"><span data-dp-reg></span><span class="dphone-caret"></span></div>
			<div class="dphone-legal"><span>Road legal</span></div>
			<div class="dphone-chips">{chips}</div>
			<div class="dphone-foot">
				<div><small>TOTAL</small><span class="dphone-price" data-dp-price>{STYLES[0][3]}</span></div>
				<span class="dphone-next">Next: Plate size <span>→</span></span>
			</div>
		</div>
	</div>
</div>'''


def bento():
    u = USPS
    return f'''<div class="bento">
	<article class="bento-card bento-wide dark reveal">
		<div class="bento-copy">
			<div><p class="bento-num">01</p><h3 style="margin-top:10px">{u[0][0]}</h3></div>
			<p>{u[0][1]}</p>
		</div>
		<div class="bento-media"><img src="assets/img/detail-4d-gel.webp" width="800" height="600" alt="Close-up of 4D gel characters, rendered by the builder" loading="lazy" decoding="async"></div>
	</article>
	<article class="bento-card reveal" style="--d:.08s">
		<p class="bento-num">02</p>
		<h3>{u[1][0]}</h3>
		<p>We design it, load your products and prices and set up every page. You never need to touch WordPress.</p>
		<div class="bento-shot"><img src="assets/img/shop-home.webp" width="1200" height="2172" alt="A demo plate shop home page, scrolling" loading="lazy" decoding="async"></div>
	</article>
	<article class="bento-card reveal">
		<p class="bento-num">03</p>
		<h3>{u[2][0]}</h3>
		<p>BS AU 145e spacing as they type, and your supplier name and postcode in every plate footer.</p>
		<svg class="bento-plate" viewBox="0 0 320 110" role="img" aria-label="A plate with spacing guides: 79 mm character height and a 33 mm gap between the two groups">
			<rect x="4" y="14" width="312" height="82" rx="8" fill="#F7D333" stroke="#16191C" stroke-width="2"/>
			<text x="160" y="76" text-anchor="middle" font-family="Archivo, sans-serif" font-weight="800" font-size="50" letter-spacing="2" fill="#16191C">3D BLDR</text>
			<line class="guide" x1="22" y1="30" x2="22" y2="80" stroke="#C4161C" stroke-width="2"/>
			<line class="guide" x1="16" y1="30" x2="28" y2="30" stroke="#C4161C" stroke-width="2"/>
			<line class="guide" x1="16" y1="80" x2="28" y2="80" stroke="#C4161C" stroke-width="2"/>
			<line class="guide" x1="126" y1="104" x2="160" y2="104" stroke="#C4161C" stroke-width="2"/>
			<text x="22" y="10" text-anchor="middle" font-family="Geist Mono, monospace" font-size="9" fill="#16191C">79 mm</text>
			<text x="143" y="10" text-anchor="middle" font-family="Geist Mono, monospace" font-size="9" fill="#16191C">33 mm gap</text>
			<text x="160" y="92" text-anchor="middle" font-family="Geist Mono, monospace" font-size="5.5" fill="#16191C">YOUR PLATES LTD  AB1 2CD</text>
		</svg>
	</article>
	<article class="bento-card reveal" style="--d:.08s">
		<p class="bento-num">04</p>
		<h3>{u[3][0]}</h3>
		<p>The plate stays in view, one step at a time, with the price always showing.</p>
		<div class="bento-chips" aria-hidden="true">
			<span style="--d:0s">Printed</span><span style="--d:-4.8s">3D Gel</span><span style="--d:-3.6s">4D Gel</span><span style="--d:-2.4s">4D 6 mm</span><span style="--d:-1.2s">Retro</span><span>Next →</span>
		</div>
	</article>
	<article class="bento-card reveal" style="--d:.16s">
		<p class="bento-num">05</p>
		<h3>{u[4][0]}</h3>
		<p>Hosting, security certificate, daily backups and updates are included. Support is by email.</p>
		<ul class="bento-status">
			<li><span class="live-dot" aria-hidden="true"></span>Security certificate<span>On</span></li>
			<li><span class="live-dot" aria-hidden="true" style="--d:.7s"></span>Backups<span>Daily</span></li>
			<li><span class="live-dot" aria-hidden="true" style="--d:1.4s"></span>Updates<span>Done for you</span></li>
		</ul>
	</article>
</div>'''


def render_panel():
    tabs = ''.join(f'<button type="button" aria-pressed="{str(i == 2).lower()}" data-style-tab="{i}">{label}</button>' for i, (_, label, _, _, _, _) in enumerate(STYLES))
    imgs = ''.join(f'<img class="{"is-on" if i == 2 else ""}" src="assets/img/{img}" width="640" height="480" alt="{label} plate, 3D BLDR, rendered by the builder" loading="lazy" decoding="async" data-style-img>' for i, (_, label, img, _, _, _) in enumerate(STYLES))
    notes = '|'.join(f'{label}~{text}' for _, label, _, _, text, _ in STYLES)
    details = [('detail-4d-gel.webp', '4D gel'), ('detail-gel-3d.webp', '3D gel'), ('detail-retro.webp', 'Retro pressed'), ('detail-4d-acrylic.webp', '4D 6 mm, bordered')]
    det = ''.join(f'<li><figure><img src="assets/img/{img}" width="800" height="600" alt="Close-up of {name} characters, rendered by the builder" loading="lazy" decoding="async"><figcaption>{name}</figcaption></figure></li>' for img, name in details)
    cur = STYLES[2]
    return f'''<section class="section studio" id="rendering" aria-labelledby="render-title">
	<div class="wrap">
		<div class="render-panel" data-style-panel data-notes="{notes}">
			<div class="reveal">
				<p class="eyebrow">The rendering</p>
				<h2 id="render-title" style="margin-top:14px">Rendered like the real plate</h2>
				<p class="lede" style="margin-top:18px;color:var(--muted)">Every picture here is the builder itself, not a photo. Pick a style.</p>
				<div class="style-tabs" role="group" aria-label="Plate style">{tabs}</div>
				<div class="style-note" aria-live="polite"><b data-style-name>{cur[1]}</b><p data-style-text>{cur[4]}</p></div>
				<div class="actions" style="margin-top:28px"><a class="btn btn-primary" href="#try">Turn one yourself <span class="arr" aria-hidden="true">→</span></a></div>
			</div>
			<div class="style-stage reveal" style="--d:.1s">{imgs}<span class="stage-label">Live render</span></div>
		</div>
		<ul class="render-details-row">{det}</ul>
	</div>
</section>'''


def home(font):
    return f"""
<section class="home-hero" aria-labelledby="hero-title">
	<div class="wrap">
		<div class="hero-panel dark">
			<div class="hero-glow" aria-hidden="true"></div>
			<div class="hero-copy">
				<p class="eyebrow hero-rise">For UK Registered Number Plate Suppliers</p>
				<h1 id="hero-title" class="hero-rise" style="--d:.06s;margin-top:18px">Your plate shop, with plates that look real in 3D</h1>
				<p class="lede hero-rise" style="--d:.12s">We design, build and host a complete number plate website around a photo-real 3D builder. Customers type their reg, see the real finish and buy, on their phone.</p>
				<ul class="hero-checks hero-rise" style="--d:.18s">
					<li>{tick(.7)}Photo-real 3D, rendered live as they type</li>
					<li>{tick(.9)}A complete shop in your brand, built for you</li>
					<li>{tick(1.1)}Hosted, updated and looked after</li>
				</ul>
				<div class="actions hero-rise" style="--d:.24s">
					<a class="btn btn-primary btn-lg" href="start.html">Start your shop <span class="arr" aria-hidden="true">→</span></a>
					<a class="tlink" href="#try">Try the 3D builder <span class="arr" aria-hidden="true">→</span></a>
				</div>
				<p class="price-anchor hero-rise" style="--d:.3s">From <b>[£ STARTER MONTHLY] a month</b> + [£ STARTER SET-UP] set-up. Prices exclude VAT.</p>
			</div>
			<div class="hero-stage">
				<img class="hero-plate hero-plate-rear" src="assets/img/hero-rear.webp" width="881" height="500" alt="" aria-hidden="true">
				<img class="hero-plate hero-plate-front" src="assets/img/hero-front.webp" width="1112" height="318" alt="" aria-hidden="true">
				<div class="hero-phone">{demo_phone()}</div>
			</div>
		</div>
		<p class="hero-proof-line">Already selling plates on <a href="https://plateboss.co.uk" rel="noopener">PlateBoss.co.uk</a></p>
	</div>
</section>

<section class="section" aria-labelledby="why-title">
	<div class="wrap">
		<div class="reveal" style="text-align:center;max-width:720px;margin:0 auto">
			<p class="eyebrow">The package</p>
			<h2 id="why-title" style="margin-top:14px">Five reasons it sells plates</h2>
			<p class="lede" style="margin:16px auto 0;color:var(--muted)">The builder and the website, designed together and looked after for you.</p>
		</div>
		{bento()}
	</div>
</section>

{render_panel()}

<section class="section" id="try" aria-labelledby="try-title">
	<div class="wrap try-split">
		<div class="try-phone">{phone_preview(views=True)}</div>
		<div class="stack try-head reveal">
			<p class="eyebrow">Try it</p>
			<h2 id="try-title">Go on, design a plate</h2>
			<p class="lede muted">This is the real builder, running live at phone size for a demo shop, Your Plates Ltd. Type a reg, swipe through the steps, turn the plate. Your customers get exactly this, in your colours.</p>
		</div>
		<div class="stack try-rest reveal" style="--d:.1s">
			<ul class="feature-list">
				<li><b>Legal spacing as they type</b><span>AB12CDE becomes AB12 CDE in Charles Wright characters, and the builder says if a reg won't fit a size.</span></li>
				<li><b>Every style, in 3D</b><span>Printed, 3D gel, 4D, 4D gel and retro, with badges, borders and an "on a car" view.</span></li>
				<li><b>Made for phones</b><span>One step at a time, the plate always in view and the price always showing.</span></li>
			</ul>
			<div class="actions">
				<button type="button" class="btn btn-ghost" data-reveal="desktop-view" aria-expanded="false" aria-controls="desktop-view">See it on a computer</button>
			</div>
			<div class="next-card">
				<p><b>Like it?</b> It comes with a complete website built around it.</p>
				<div class="actions">
					<a class="btn btn-primary" href="start.html">Start your shop <span class="arr" aria-hidden="true">→</span></a>
					<a class="btn btn-ghost" href="website.html">See what you get</a>
				</div>
			</div>
		</div>
	</div>
	<div class="wrap desktop-reveal" id="desktop-view" hidden>
		<div class="desktop-reveal-head">
			<h3>On a computer</h3>
			<button type="button" class="btn btn-ghost" data-reveal-close="desktop-view">Back to the phone</button>
		</div>
		{laptop_preview()}
	</div>
</section>

<section class="section" id="website" aria-labelledby="web-title">
	<div class="wrap site-split">
		<figure class="browser reveal" style="margin:0">
			<div class="browser-bar" aria-hidden="true"><span class="dots"><i></i><i></i><i></i></span><span class="url">yourplates.co.uk</span></div>
			<img class="browser-shot" src="assets/img/desktop-poster.webp" width="1512" height="898" alt="A plate shop's Design your plates page on a computer, with the 3D plate at the top and the options underneath" loading="lazy" decoding="async">
		</figure>
		<div class="reveal" style="--d:.1s">
			<p class="eyebrow">What you get</p>
			<h2 id="web-title" style="margin-top:14px">Every page a plate shop needs</h2>
			<p class="lede" style="margin-top:16px;color:var(--muted)">A WordPress and WooCommerce shop, designed in your brand and built round the builder.</p>
			<ul class="check-list">
				<li>{TICK}Home, design, documents, about and legal pages</li>
				<li>{TICK}A page for each plate type on Pro and Complete</li>
				<li>{TICK}Card payments through your own Stripe</li>
				<li>{TICK}ID and entitlement checks at checkout on Complete</li>
			</ul>
			<p style="margin-top:26px"><a class="tlink" href="website.html">See everything you get <span class="arr" aria-hidden="true">→</span></a></p>
		</div>
	</div>
</section>

{shops_strip()}

{process_strip()}

<section class="section home-plans" id="plans" aria-labelledby="plans-title">
	<div class="wrap">
		<div class="reveal" style="text-align:center;max-width:680px;margin:0 auto">
			<h2 id="plans-title">Pick a plan and start</h2>
			<p class="lede" style="margin:14px auto 0;color:var(--muted)">Every plan is a website with the 3D builder built in, hosted and updated, plus a one-off set-up fee. Prices exclude VAT.</p>
		</div>
		{plan_summary()}
	</div>
</section>

<section class="section" aria-labelledby="faq-title">
	<div class="wrap">
		<h2 id="faq-title" class="reveal">Questions plate makers ask</h2>
		<div class="faq">
			{faq_items([0, 2, 3, 1, 7, 10])}
		</div>
	</div>
</section>

{cta_band()}
"""
