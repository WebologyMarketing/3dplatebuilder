"""Home, demo, features. Copy in UK English."""
from partials import builder_markup, spec_svg, phone_preview, laptop_preview

DN = lambda t: f'<span class="draft-note">{t}</span>'

# ------------------------------------------------------------------ shared bits

def plan_summary():
    def card(name, who, price, setup, note, items, inherit=None, featured=False, pid=''):
        head = f'<li class="inherit">Everything in {inherit}, plus:</li>' if inherit else ''
        lis = head + ''.join(f'<li>{i}</li>' for i in items)
        cls = ' is-featured' if featured else ''
        btn = 'btn-primary' if featured else 'btn-ghost'
        return f"""<article class="plan{cls}">
		<h3>{name}</h3>
		<p class="for">{who}</p>
		<p class="price"><strong>{price} a month</strong><span>+ {setup} set-up {DN(note)}</span></p>
		<ul>{lis}</ul>
		<a class="btn {btn}" href="pricing.html#{pid}">See what's in {name}</a>
	</article>"""
    starter = card('Starter', 'For workshops that want the 3D builder and a proper shop, without the extras.', '[£ STARTER MONTHLY]', '[£ STARTER SET-UP]', 'Suggested: £79–£99 a month, £395–£595 set-up',
                   ['3D builder with all seven character styles', 'Every plate size, with legal spacing as they type', 'A ready-made plate-shop website in your colours, hosted and updated'], pid='starter')
    pro = card('Pro', 'For shops that sell mostly online and want the full showroom.', '[£ PRO MONTHLY]', '[£ PRO SET-UP]', 'Suggested: £149–£199 a month, £795–£1,195 set-up',
               ['Badges, borders and different front and rear sizes', '"On a car" view, phone app layout, builder on every product page', 'A custom home page, plate-type landing pages and search basics'], inherit='Starter', pid='pro')
    complete = card('Complete', 'For busy shops that want customers to design, pay and send documents in one go.', '[£ COMPLETE MONTHLY]', '[£ COMPLETE SET-UP]', 'Suggested: £279–£349 a month, £1,495–£2,495 set-up',
                    ['Checkout in the builder with Apple Pay and Google Pay', 'Proof of ID and entitlement checks, collect in store and fitting', 'A fully custom site, local pages, your site moved across and monthly changes'], inherit='Pro', featured=True, pid='complete')
    return f"""<div class="plans">
	{starter}
	{pro}
	{complete}
</div>
<div class="outright">
	<div class="stack">
		<h3>Already have a WooCommerce shop?</h3>
		<p class="muted">Add the builder to the site you already have, at the same three levels. We check your site, install it, set your prices and test it with you. Or buy a Complete website outright and pay yearly for hosting and updates.</p>
	</div>
	<div class="stack">
		<p class="price"><strong>Builder only from [£ STARTER BUILDER-ONLY MONTHLY] a month</strong></p>
		<p class="muted">{DN('Suggested: £39–£49 a month')}</p>
		<div class="actions"><a class="btn btn-ghost" href="pricing.html#builder-only">See builder-only prices</a><a class="btn btn-ghost" href="pricing.html#outright">Buying outright</a></div>
	</div>
</div>"""


def cta_band(heading='See it with your own plates', text='Request a demo and we\'ll set the builder up with your styles, sizes and prices, then email you a private link to try it. No sales calls.'):
    return f'''<section class="plate-band" aria-labelledby="cta-title">
	<div class="wrap">
		<div>
			<h2 id="cta-title">{heading}</h2>
			<p>{text}</p>
		</div>
		<div class="actions">
			<a class="btn btn-dark" href="contact.html">Request a demo</a>
			<a class="btn btn-dark" href="demo.html">Try the live demo</a>
		</div>
	</div>
</section>'''


# ------------------------------------------------------------------ home

def home(font):
    return f'''
<section class="hero studio">
	<div class="wrap hero-grid">
		<div class="hero-copy">
			<h1>Let your customers design their plates in 3D</h1>
			<p class="lede">A complete number plate shop with a real-time 3D builder built in. Your customers type their reg, see it spaced legally, pick 4D, gel or retro, and check out with Apple Pay. On their phone, in a couple of minutes.</p>
			<div class="actions">
				<a class="btn btn-primary" href="#try">Try the live builder</a>
				<a class="btn btn-ghost" href="contact.html">Request a demo</a>
			</div>
			<p class="hero-proof"><strong>Built by, and running at, PlateBoss.co.uk.</strong> A UK plate maker that built it for its own shop.</p>
		</div>
		<figure class="hero-art">
			<img src="assets/img/hero-rear.webp" width="881" height="500" alt="A yellow rear plate, AB12 CDE, in 4D gel characters, rendered by the builder" fetchpriority="high">
			<img class="plate-front" src="assets/img/hero-front.webp" width="1112" height="318" alt="The matching white front plate in printed characters" loading="lazy" decoding="async">
		</figure>
	</div>
</section>

<section class="section studio" id="try" aria-labelledby="try-title">
	<div class="wrap phone-split">
		{phone_preview(views=True)}
		<div class="stack phone-copy">
			<h2 id="try-title">Have a go. This is the real builder</h2>
			<p class="lede muted">The same builder that takes orders at PlateBoss.co.uk, set up for a demo supplier, Your Plates Ltd. It's running live at iPhone 18 Pro Max size, because the phone is where plenty of plate buyers design and pay. Type a reg, swipe through the steps, turn the plate.</p>
			<ul class="feature-list">
				<li><b>The plate stays in view</b><span>Pinned at the top while customers choose, so every change shows straight away.</span></li>
				<li><b>One step at a time</b><span>Reg and style, plate size, badge, border, add-ons. Swipe or tap Next, with a progress bar along the top.</span></li>
				<li><b>The total is always there</b><span>The price and the next button sit at the bottom, and the keyboard never covers them.</span></li>
				<li><b>Back works like an app</b><span>The arrow and the phone's own back gesture both close the builder.</span></li>
			</ul>
			<div class="actions">
				<button type="button" class="btn btn-ghost" data-reveal="desktop-view" aria-expanded="false" aria-controls="desktop-view">See it on a computer</button>
				<a class="btn btn-ghost" href="demo.html">Open the full demo</a>
			</div>
			<p class="small muted">Example prices. Your shop uses your own price list.</p>
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

<section class="section" aria-labelledby="why-title">
	<div class="wrap">
		<div class="intro">
			<h2 id="why-title">What it does for your shop</h2>
			<p>Most plate shops still sell from a flat picture and a list of drop-downs. Customers guess, ring up to check, or order the wrong thing. The builder shows them the plate they're buying.</p>
		</div>
		<ul class="benefits">
			<li>
				<h3>More people finish their order</h3>
				<p>Customers turn the plate, zoom in on the 4D edge and see the badge before they pay, so they buy with confidence instead of messaging you first.</p>
			</li>
			<li>
				<h3>Fewer wrong orders</h3>
				<p>Every basket line spells it out: registration, front and rear sizes in mm, style, badge, border, extras and road legal or show plate. Your workshop makes exactly what the customer saw.</p>
			</li>
			<li>
				<h3>Legal spacing done for them</h3>
				<p>Type AB12CDE and it becomes AB12 CDE, in 79 mm Charles Wright characters with 11 mm and 33 mm gaps. If a reg won't fit a size, the builder says so before they order.</p>
			</li>
			<li>
				<h3>Apple Pay and Google Pay</h3>
				<p>Your own WooCommerce checkout opens inside the builder, with Apple Pay and Google Pay through Stripe. Customers pay without losing their design.</p>
			</li>
			<li>
				<h3>Made for phones first</h3>
				<p>On a phone the builder fills the screen: step cards, a progress bar and a buy bar that the keyboard never covers. Plenty of plates are bought on a phone, in a lunch break or a car park, so this is where it counts.</p>
			</li>
			<li>
				<h3>Documents sorted at checkout</h3>
				<p>Road-legal orders ask for proof of ID and entitlement at checkout, or later. Orders wait as Awaiting documents until your staff have checked them.</p>
			</li>
		</ul>
	</div>
</section>

<section class="section studio" aria-labelledby="spec-h">
	<div class="wrap">
		<div class="spec-split">
			<div class="stack">
				<h2 id="spec-h">Legal spacing, in millimetres</h2>
				<p class="lede muted">The builder places characters ink to ink, the way BS AU 145e measures them, and lays them out as the customer types. Current, prefix and suffix regs are spaced automatically; dateless and cherished regs stay exactly as typed.</p>
			</div>
			<div class="stack">
				<ul class="spec-list">
					<li><b>79 mm</b><span>Character height, Charles Wright 2001 typeface</span></li>
					<li><b>50 mm</b><span>Character width (14 mm for 1 and I), 14 mm stroke</span></li>
					<li><b>11 mm</b><span>Gap between characters, and the minimum margin</span></li>
					<li><b>33 mm</b><span>Gap between the two groups</span></li>
					<li><b>Footer</b><span>Your supplier name, postcode and the BS AU 145e mark on every plate</span></li>
				</ul>
				<p class="small muted">The builder helps with BS AU 145e layout. You stay responsible for the plates you make, your RNPS registration and checking your customers' documents.</p>
			</div>
		</div>
		<figure class="spec-drawing spec-wide">
			<div class="spec-scroll" tabindex="0" role="region" aria-label="Dimensioned plate drawing">{spec_svg(font)}</div>
			<figcaption>A standard 520 × 111 mm rear plate, drawn from the builder's own character outlines.</figcaption>
		</figure>
	</div>
</section>

<section class="section" aria-labelledby="how-title">
	<div class="wrap">
		<div class="intro">
			<h2 id="how-title">How it works</h2>
			<p>You don't need to know WordPress. We build the shop, set up the builder with your products and prices, and look after it.</p>
		</div>
		<ol class="steps">
			<li><h3>Request a demo</h3><p>We set the builder up with your kind of plates and email you a private link, so you can try it in your own time.</p></li>
			<li><h3>We build your shop</h3><p>We copy your products and prices, and add your logo, supplier name and postcode to the plates.</p></li>
			<li><h3>Connect Stripe</h3><p>Payments go straight to your own Stripe account, with Apple Pay and Google Pay switched on.</p></li>
			<li><h3>Go live</h3><p>We test real orders end to end, point your domain at the new shop and you start taking orders.</p></li>
		</ol>
		<p style="margin-top:32px"><a class="try-link" href="setup.html">See the full set-up, step by step</a></p>
	</div>
</section>

<section class="section" aria-labelledby="plans-title">
	<div class="wrap">
		<div class="intro">
			<h2 id="plans-title">A website with the builder, or the builder on your site</h2>
			<p>Three monthly plans with a one-off set-up fee. Each one adds more of the builder and more website: from a ready-made plate shop on Starter to a fully custom site with local pages and monthly changes on Complete.</p>
		</div>
		{plan_summary()}
	</div>
</section>

<section class="section studio" aria-labelledby="proof-title">
	<div class="wrap proof">
		<div class="stack">
			<h2 id="proof-title">Built by a plate maker. Running at PlateBoss.co.uk</h2>
			<p class="lede muted">PlateBoss is a UK plate maker in Hounslow. We built the builder for our own shop, and it's live and taking orders there now. [BRAND NAME] is how other plate makers get the same thing.</p>
			<p><a class="try-link" href="https://plateboss.co.uk" rel="noopener">See it working at PlateBoss.co.uk</a></p>
		</div>
		<div class="stack">
			<h3 class="proof-sub">Live at PlateBoss.co.uk today</h3>
			<ul class="feature-list">
				<li><b>The 3D builder on every plate product page</b><span>With the Design in 3D / Classic switch, a Design Your Plates page and the phone app layout.</span></li>
				<li><b>Checkout inside the builder</b><span>Delivery or collection, card, Apple Pay, Google Pay and Klarna, without leaving the design.</span></li>
				<li><b>Proof of ID and entitlement checks</b><span>Upload at checkout or send later, Awaiting documents status and staff checks on every road-legal order.</span></li>
				<li><b>All seven styles and every plate size</b><span>Printed to 4D gel 6 mm and retro, from standard to motorbike, trailer and import plates.</span></li>
			</ul>
	</div>
</section>

<section class="section" aria-labelledby="faq-title">
	<div class="wrap">
		<h2 id="faq-title">Questions plate makers ask</h2>
		<div class="faq">
			{faq_items()}
		</div>
	</div>
</section>

{cta_band()}
'''


def faq_items():
    items = [
        ("Is the builder DVLA approved?",
         "<p>No. The software has no DVLA approval or certification, and we don't claim it does. It lays characters out to BS AU 145e measurements and puts your supplier name, postcode and the BS AU 145e mark in the footer, which saves you checking every layout by hand.</p><p>You stay responsible for the plates you make, your registration as a Registered Number Plate Supplier and checking your customers' documents.</p>"),
        ("Do I need to know WordPress?",
         "<p>No. We build the shop, host it and keep it updated. You change prices on one screen (WooCommerce → Plate Builder) and handle orders like any WooCommerce shop.</p>"),
        ("I already have a website. Can you move it over?",
         "<p>Yes. It's included in Complete and an add-on on Starter and Pro: we copy your products, prices and pages across, and keep your domain. Tell us what you're on now when you request a demo.</p>"),
        ("I already run WooCommerce. Can I just add the builder?",
         "<p>Yes, that's Builder only. It's the same three plans without the website. We check your theme, checkout and hosting first, then install the builder, set your prices and test real orders with you. You keep your own hosting and look after WordPress updates; we keep the builder updated. It needs WordPress 6.5 or later.</p>"),
        ("What's the difference between the plans' websites?",
         "<p>Starter is a ready-made plate-shop design in your logo and colours, with the core pages. Pro adds a home page designed around your shop, landing pages for each plate type, edited product photos and search basics. Complete is fully custom, with local pages for the towns you serve, your current site moved across, launch articles and hours of changes every month.</p>"),
        ("Does it work with Shopify, Wix or Squarespace?",
         "<p>Not today. The builder runs on WordPress and WooCommerce.</p>"),
        ("Can I use my own prices and styles?",
         "<p>Your prices, yes: you set them yourself, and the builder's live total always matches them. Seven character styles come built in (printed, 3D gel, 4D 3 mm and 6 mm, 4D gel 3 mm and 6 mm, and retro). A style you make that isn't on the list can be added as a custom style.</p>"),
        ("Who gets the money, and what are the fees?",
         "<p>Card, Apple Pay and Google Pay payments go through your own Stripe account, so the money goes to you and Stripe charges its fees to you directly.</p>"),
        ("What happens to the documents customers upload?",
         "<p>They're kept in a private folder that the web server never serves, and only staff who can see orders can download them, through a signed link. By default, abandoned uploads are deleted after 2 days and order documents 30 days after your staff verify them. You can change that period; check it against your own data protection and RNPS record-keeping advice.</p>"),
        ("How do I get help once I'm live?",
         "<p>By email, at [EMAIL]. We don't run phone lines: everything is handled in writing, so there's always a record of what was asked and done. Support tickets from your shop's admin are coming soon.</p>"),
        ("How long is the contract?",
         "<p>Monthly plans have a minimum term of [MINIMUM TERM], then [NOTICE PERIOD] notice. Your domain, products, customers and orders stay yours.</p>"),
    ]
    return '\n'.join(f'<details><summary>{q}</summary><div>{a}</div></details>' for q, a in items)


# ------------------------------------------------------------------ demo page

QUICK = [
    ('demo.html', 'Front plate, printed'),
    ('demo.html?reg=AB12CDE&style=gel-4d-3&side=rear', '4D gel, rear'),
    ('demo.html?reg=RETRO&style=retro', 'Retro pressed'),
    ('demo.html?reg=AB12CDE&style=acrylic-4d-3&size=hex', 'Hex, cut to fit'),
    ('demo.html?reg=AB12CDE&style=gel-3d&badge=uk-4d', 'UK 4D badge'),
    ('demo.html?reg=AB12CDE&style=acrylic-4d-6&border=black-2mm&badge=ev-gel', 'EV badge and border'),
    ('demo.html?reg=AB12CDE&size=standard&rear=oversized&side=rear', 'Oversized rear plate'),
    ('demo.html?reg=AB12CDE&size=motorbike', 'Motorbike'),
    ('demo.html?reg=AB12CDE&style=gel-4d-6&view=car', 'On a car'),
]


def demo():
    chips = '\n'.join(f'<li><a href="{h}">{l}</a></li>' for h, l in QUICK)
    return f'''
<section class="page-head page-head-compact" aria-labelledby="demo-title">
	<div class="wrap">
		<div class="intro">
			<h1 id="demo-title">Live demo</h1>
			<p>The full builder, set up for a made-up supplier: Your Plates Ltd, AB1 2CD. Their name and postcode print in the plate footer, as yours would. Try it on the phone below, or switch to the computer view. When you get to Continue to checkout, you'll see the order your shop would receive. Nothing is sent anywhere.</p>
		</div>
		<ul class="quick-views" aria-label="Start from a ready-made design">
			{chips}
		</ul>
	</div>
</section>

<section class="demo-stage" aria-label="3D plate builder">
	<div class="wrap phone-split">
		{phone_preview(inherit=True, caption='The phone layout at iPhone 18 Pro Max size (440 × 956 points). The designs above open here.')}
		<div class="stack">
			<div class="demo-notes demo-notes-stack">
				<div>
					<h3>What's real here</h3>
					<p>Everything you can see and touch: the 3D plate, styles, sizes, badges, borders, the car view, extras, the live price and the phone layout. It's the same code that runs on PlateBoss.co.uk.</p>
				</div>
				<div>
					<h3>What's different on your shop</h3>
					<p>Continue to checkout puts the design in your basket and opens your own checkout inside the builder: delivery or collection, documents, card, Apple Pay and Google Pay. Your name, postcode, logo and prices replace the demo ones.</p>
				</div>
				<div>
					<h3>Customers on a computer</h3>
					<p>On a bigger screen the builder sits in your page, with the 3D plate pinned at the top while customers scroll through the options.</p>
				</div>
			</div>
			<div class="actions">
				<button type="button" class="btn btn-primary" data-reveal="desktop-view" aria-expanded="false" aria-controls="desktop-view">See it on a computer</button>
			</div>
			<p class="small muted">Example prices. On your shop, the total comes from your own price list.</p>
		</div>
	</div>
	<div class="wrap desktop-reveal" id="desktop-view" hidden>
		<div class="desktop-reveal-head">
			<h3>On a computer</h3>
			<button type="button" class="btn btn-ghost" data-reveal-close="desktop-view">Back to the phone</button>
		</div>
		{laptop_preview(inherit=True, caption='The computer layout on a 14-inch MacBook Pro screen (1512 × 982, the default size). The designs above open here too.')}
	</div>
</section>

{cta_band('Like what you see?', "Request a demo and we'll set the builder up with your own styles and prices, so you can see exactly what your customers would get.")}
'''


# ------------------------------------------------------------------ features

STYLES = [
    ('printed', 'Printed', 'Flat ink under the acrylic.'),
    ('gel-3d', '3D Gel', 'A 2.2 mm domed resin over each character.'),
    ('acrylic-4d-3', '4D 3mm', 'Laser-cut acrylic with a polished edge.'),
    ('acrylic-4d-6', '4D 6mm', 'Two 3 mm layers, 6 mm deep.'),
    ('gel-4d-3', '4D Gel 3mm', '3 mm acrylic with a gel dome on top.'),
    ('gel-4d-6', '4D Gel 6mm', '6 mm acrylic with a gel dome on top.'),
    ('retro', 'Retro', 'A 5 mm pressed chamfer, like classic plates.'),
]


def tier(name):
    return f'<span class="tier-tag">{name}</span>'


def features():
    style_items = '\n'.join(
        f'<li><a href="demo.html?reg=AB12CDE&amp;style={sid}&amp;side=rear"><img src="assets/img/style-{sid}.webp" width="640" height="480" alt="{label} characters on a rear plate" loading="lazy" decoding="async"><span>{label}</span></a><span class="small muted">{desc}</span></li>'
        for sid, label, desc in STYLES)
    return f'''
<section class="page-head" aria-labelledby="f-title">
	<div class="wrap">
		<div class="intro">
			<h1 id="f-title">Features</h1>
			<p>Everything the builder does today, in plain English. Each feature is tagged with the plan it comes in. Anything we haven't built yet is listed at the bottom as coming soon.</p>
		</div>
	</div>
</section>

<section class="section-tight">
	<div class="wrap">

		<div class="feature-group" id="preview">
			<div class="copy">
				{tier('Starter')}
				<h2>A real 3D plate</h2>
				<p>Not a picture with text on top. Each plate is modelled in 3D, at real size, with studio lighting that shows off gel domes and 4D edges.</p>
				<ul class="feature-list">
					<li><b>Turn, zoom and pan</b><span>Drag with a finger or mouse, pinch or scroll to zoom, double-tap to zoom in. Customers can turn the plate right round.</span></li>
					<li><b>Front and rear</b><span>White front and yellow rear plates, one tap apart.</span></li>
					<li><b>Close-ups on cue</b><span>Pick a character style and the camera swings in at an angle to show its profile.</span></li>
					<li><b>Light on phones</b><span>It only draws while something moves, so an idle builder doesn't drain the battery.</span></li>
				</ul>
				<a class="try-link" href="demo.html">Try it in the live demo</a>
			</div>
			<figure class="shot shot-transparent">
				<img src="assets/img/hero-rear.webp" width="881" height="500" alt="A rear plate in 4D gel characters, turned at an angle" loading="lazy" decoding="async">
				<figcaption>4D Gel 3mm on a rear plate, straight from the builder.</figcaption>
			</figure>
		</div>

		<div class="feature-group flip" id="legal">
			<div class="copy">
				{tier('Starter')}
				<h2>Legal layout as they type</h2>
				<p>The builder applies UK registration spacing and the Charles Wright characters while the customer types, and tells them if something won't work.</p>
				<ul class="feature-list">
					<li><b>Automatic spacing</b><span>Current (AB12 CDE), prefix (A123 BCD) and suffix (ABC 123D) regs are spaced for them. Dateless and cherished regs stay as typed.</span></li>
					<li><b>Fit checks</b><span>"This registration doesn't fit a motorbike plate with a badge" appears before they order, not after you've cut it.</span></li>
					<li><b>Road legal or show plate</b><span>A status badge shows which they're getting.</span></li>
					<li><b>Your footer on every plate</b><span>Supplier name, postcode and the BS AU 145e mark, as the standard requires.</span></li>
				</ul>
				<p class="small">The builder helps with BS AU 145e layout. You stay responsible for compliance and for checking customers' documents.</p>
			</div>
			<figure class="shot">
				<img src="assets/img/builder-desktop.webp" width="1280" height="900" alt="The builder on a computer: the plate preview with the registration box, status message and style picker below" loading="lazy" decoding="async">
				<figcaption>The registration box, the legal status and the style picker.</figcaption>
			</figure>
		</div>

		<div class="feature-group" id="styles">
			<div class="copy">
				{tier('Starter')}
				<h2>Seven character styles</h2>
				<p>Each style is modelled in 3D, layer by layer, at its true depth. Every one keeps the character footprint at its legal size, and raised styles cast real shadows on the plate.</p>
				<p class="small muted">Tap a style to open it in the live demo.</p>
			</div>
			<ul class="style-grid" aria-label="Character styles">
				{style_items}
			</ul>
		</div>

		<div class="feature-group flip" id="sizes">
			<div class="copy">
				{tier('Starter')}
				<h2>Every plate size</h2>
				<ul class="feature-list">
					<li><b>Front and rear</b><span>Standard 520 × 111 mm, short (cut to fit the reg), hex, Japanese import and US import.</span></li>
					<li><b>Rear only</b><span>Oversized 533 × 152 mm and 4x4 279 × 203 mm.</span></li>
					<li><b>One-plate vehicles</b><span>Motorbike 229 × 178 mm, trailer, and white commercial trailer, with 64 mm characters where the size needs them.</span></li>
					<li><b>Different front and rear sizes {tier('Pro')}</b><span>A short front with an oversized rear, for example. Each plate prices its own size, and the order shows both in mm.</span></li>
				</ul>
				<p class="small muted">Motorbike, trailer and import sizes are printed only for now.</p>
				<a class="try-link" href="demo.html?reg=AB12CDE&amp;size=standard&amp;rear=oversized&amp;side=rear">Try a different rear size</a>
			</div>
			<figure class="shot">
				<img src="assets/img/size-hex.webp" width="1280" height="720" alt="A hex plate with pointed ends, cut to fit the registration" loading="lazy" decoding="async">
				<figcaption>A hex plate, cut to the length the registration needs.</figcaption>
			</figure>
		</div>

		<div class="feature-group" id="badges">
			<div class="copy">
				{tier('Pro')}
				<h2>Badges and borders</h2>
				<ul class="feature-list">
					<li><b>UK badges</b><span>Printed, gel in white or blue, or a raised 4D tile.</span></li>
					<li><b>EV badges</b><span>The green EV strip printed or in gel, and EV with UK.</span></li>
					<li><b>Borders</b><span>Black, 1, 2 or 3 mm, printed inside the plate edge.</span></li>
					<li><b>Smart about sizes</b><span>The camera zooms onto the badge as it's picked, and a badge that doesn't suit a new size clears itself.</span></li>
				</ul>
				<a class="try-link" href="demo.html?reg=AB12CDE&amp;style=gel-3d&amp;badge=uk-4d">Try the 4D badge</a>
			</div>
			<figure class="shot">
				<img src="assets/img/badge-uk-4d.webp" width="1280" height="720" alt="Close-up of a raised UK 4D badge on the left of a plate" loading="lazy" decoding="async">
				<figcaption>The UK 4D badge: blue artwork on a raised 3 mm acrylic tile.</figcaption>
			</figure>
		</div>

		<div class="feature-group flip" id="car">
			<div class="copy">
				{tier('Pro')}
				<h2>See it on a car</h2>
				<p>One tap puts both plates on a car, with six paint colours to choose from. Customers can turn the car right round.</p>
				<p class="small muted">The demo uses the built-in sports saloon. More car models are coming.</p>
				<a class="try-link" href="demo.html?reg=AB12CDE&amp;style=gel-4d-6&amp;view=car">Try the car view</a>
			</div>
			<figure class="shot">
				<img src="assets/img/car.webp" width="1280" height="720" alt="A grey car with the customer's plates fitted, in the builder's car view" loading="lazy" decoding="async">
				<figcaption>The "on a car" view.</figcaption>
			</figure>
		</div>

		<div class="feature-group" id="phone">
			<div class="copy">
				{tier('Pro')}
				<h2>An app-like builder on phones</h2>
				<p>On phones the builder fills the screen like an app. The plate stays pinned at the top and the options come as cards underneath.</p>
				<ul class="feature-list">
					<li><b>Step cards</b><span>Reg and style, plate size, badge, border, add-ons. Swipe or tap Next.</span></li>
					<li><b>Progress bar and sticky buy bar</b><span>The total and the next button stay at the bottom, and the keyboard never covers them.</span></li>
					<li><b>Back works</b><span>The app bar's arrow and the phone's own back button both close the builder.</span></li>
				</ul>
				<p class="small muted">On Starter, the builder sits in the page on phones instead.</p>
				<p class="small">Try it in the phone on this page: it's the real builder, running at iPhone 18 Pro Max size.</p>
			</div>
			{phone_preview(views=True)}
		</div>

		<div class="feature-group flip" id="prices">
			<div class="copy">
				{tier('Starter')}
				<h2>Prices and basket that match your shop</h2>
				<ul class="feature-list">
					<li><b>Live total</b><span>Updates as they choose, from your own price list.</span></li>
					<li><b>Price editor</b><span>Set pair and single prices per style, size surcharges, badges, borders, extras and fitting on one screen in WooCommerce.</span></li>
					<li><b>Extras</b><span>Adhesive pads, screws and caps, and frameless holders, one of each kind per order.</span></li>
					<li><b>Checked on the server</b><span>The basket price is always worked out again from the saved design, so nobody can change it in the browser.</span></li>
					<li><b>Clear basket lines</b><span>Registration, plates, size, style, badge, border, extras and road legal or show plate, in the basket, at checkout and in emails.</span></li>
					<li><b>Your real products {tier('Pro')}</b><span>Each design is added as your matching product, such as your EV 4D product, so reports and Google show real products.</span></li>
				</ul>
			</div>
			<div class="stack">
				<figure class="spec-drawing">
					<img src="assets/img/builder-summary.webp" width="1144" height="547" alt="The add-ons step: extras with prices and the order summary" loading="lazy" decoding="async" style="border-radius:12px">
					<figcaption>The add-ons step with the price breakdown.</figcaption>
				</figure>
			</div>
		</div>

		<div class="feature-group" id="site">
			<div class="copy">
				<h2>On every page of your shop</h2>
				<ul class="feature-list">
					<li><b>Design Your Plates page and menu item {tier('Starter')}</b><span>A page with the full builder, linked from your main menu.</span></li>
					<li><b>Links with the reg filled in {tier('Starter')}</b><span>Add ?reg=AB12CDE to a link from an ad or a "search your reg" box and the builder opens with it.</span></li>
					<li><b>Builder on product pages {tier('Pro')}</b><span>Your plate products show the builder where the old options form was, starting on that product's style, size and badge. A Design in 3D / Classic switch lets customers choose.</span></li>
					<li><b>Floating button {tier('Pro')}</b><span>A "Design in 3D" button in the corner of other pages.</span></li>
					<li><b>Landing-page and category promos {tier('Complete')}</b><span>A "Design your plates in 3D" section on your home and landing pages, and promos on category pages.</span></li>
				</ul>
			</div>
			<div class="copy">
				{tier('Complete')}
				<h2>Checkout inside the builder</h2>
				<p>After add-ons, the builder turns into your real WooCommerce checkout, so every payment method, delivery rule and check works exactly as it does now.</p>
				<ul class="feature-list">
					<li><b>Apple Pay and Google Pay</b><span>Through Stripe, shown under Proceed to checkout and Place order.</span></li>
					<li><b>Delivery or collect in store</b><span>Your WooCommerce shipping options, including collection.</span></li>
					<li><b>Klarna</b><span>Hands off to Klarna if you offer it through your payment set-up.</span></li>
					<li><b>Order confirmation</b><span>Customers see their confirmation without leaving the builder.</span></li>
				</ul>
			</div>
		</div>

		<div class="feature-group flip" id="documents">
			<div class="copy">
				{tier('Complete')}
				<h2>Proof of ID and entitlement</h2>
				<p>As a registered supplier you must see one proof of ID and one proof of entitlement before handing over road-legal plates. The builder builds that into checkout. Show plates skip it.</p>
				<ul class="feature-list">
					<li><b>Upload now or send later</b><span>A photo or PDF of each document at checkout, or send them by email, WhatsApp, post or at collection.</span></li>
					<li><b>Real file checks</b><span>JPG, PNG, WebP, HEIC or PDF up to 10 MB, checked by their contents, not just the file name.</span></li>
					<li><b>Awaiting documents status</b><span>Paid orders that need documents wait here instead of Processing, and still count as paid in your reports.</span></li>
					<li><b>Staff check panel</b><span>Staff open the documents through private links, record what they saw and move the order on. Who checked it, and when, is saved on the order.</span></li>
					<li><b>Private and tidy</b><span>Files are never in the media library, and they're deleted automatically after a set time.</span></li>
				</ul>
			</div>
			<div class="copy">
				{tier('Complete')}
				<h2>In-store fitting</h2>
				<p>Fitting is an add-on in the builder. Choosing it sets the order to collection, and the builder shows the fitting charge as "added at checkout".</p>
				<p class="small muted">The fitting charge and delivery choice cards at checkout are set up for each shop; a packaged version is coming soon.</p>
			</div>
		</div>

	</div>
</section>

<section class="section" id="soon" aria-labelledby="soon-title">
	<div class="wrap">
		<div class="intro">
			<h2 id="soon-title">Coming soon</h2>
			<p>We only list what the builder does today. These are on the way, and aren't part of any plan yet.</p>
		</div>
		<ul class="benefits">
			<li><h3>Delivery choice cards</h3><p>Ready-made delivery and fitting cards at checkout, set up from your settings.</p></li>
			<li><h3>3D gel and 4D on more sizes</h3><p>Raised styles on motorbike, trailer and import plates, which are printed only today.</p></li>
			<li><h3>More cars</h3><p>More car models in the "on a car" view.</p></li>
			<li><h3>Support tickets</h3><p>Raise and follow support tickets from your shop's admin. Until then, support is by email.</p></li>
			<li><h3>Your account dashboard</h3><p>One place to see your plan, invoices and updates, and to manage more than one shop.</p></li>
			<li><h3>Automatic updates for owned sites</h3><p>Updates that install themselves on sites bought outright. Until then, we install them for you.</p></li>
		</ul>
	</div>
</section>

{cta_band()}
'''
