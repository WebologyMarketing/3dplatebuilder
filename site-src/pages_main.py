"""Home, demo, features. Copy in UK English."""
from partials import builder_markup, spec_svg, phone_preview, laptop_preview
from blocks import usp_grid, render_showcase, process_strip

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
		<a class="btn {btn}" href="start.html?plan={pid}">Start with {name}</a>
		<a class="plan-more" href="pricing.html#{pid}">Everything in {name}</a>
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
		<h3>Rather pay once?</h3>
		<p class="muted">Buy a Complete shop outright, then pay yearly for hosting, updates and support.</p>
	</div>
	<div class="stack">
		<p class="price"><strong>[£ OUTRIGHT PRICE] one-off</strong></p>
		<p class="muted">then [£ OUTRIGHT HOSTING YEARLY] a year</p>
		<div class="actions"><a class="btn btn-ghost" href="start.html?plan=outright">Start your shop</a><a class="btn btn-ghost" href="pricing.html#outright">How buying outright works</a></div>
	</div>
</div>"""


def cta_band(heading='Start your plate shop', text='Pick a plan and sign up online in a few minutes. We build your shop and you go live in [TIMELINE TOTAL]. No sales calls.'):
    return f'''<section class="plate-band" aria-labelledby="cta-title">
	<div class="wrap">
		<div>
			<h2 id="cta-title">{heading}</h2>
			<p>{text}</p>
		</div>
		<div class="actions">
			<a class="btn btn-dark" href="start.html">Start your shop</a>
			<a class="btn btn-dark-ghost" href="demo.html">Try the 3D builder</a>
		</div>
	</div>
</section>'''


# ------------------------------------------------------------------ home

# Real shops for the "Shops we've built" strip: (image in assets/img/shops/, shop name, web address, one line).
# The section only appears once there's at least one. Screenshots must be the shop owner's, with permission.
SHOPS = [
    ('plateboss-builder.webp', 'PlateBoss.co.uk', 'plateboss.co.uk/design-your-plates',
     'A plate maker in Hounslow, London, and the builder\'s first shop. It runs on every plate product page and its own Design Your Plates page, with checkout in the builder and document checks on every road-legal order.'),
]

WEBSITE_ITEMS = [
    ('Designed in your colours', 'Your logo, colours and wording on every page. A ready-made plate-shop design on Starter, up to fully custom on Complete.'),
    ('A page for every plate', 'Styles, sizes, motorbike, 4x4 and show plates, plus pages for the towns you serve, written so people find you on Google.'),
    ('Checkout that keeps the sale', 'Card, Apple Pay and Google Pay through your own Stripe, inside the builder, so customers pay without losing their design.'),
    ('DVLA documents built in', 'Proof of ID and entitlement at checkout or after. Road-legal orders wait as Awaiting documents until your staff check them.'),
    ('Hosted and looked after', 'Fast hosting, a security certificate, daily backups, and WordPress and builder updates. Nothing for you to install.'),
    ('Changes by email', 'Ask in writing and we make it. Monthly design hours are included on Complete, so your shop keeps up with your range.'),
]


def shops_strip():
    if not SHOPS:
        return ''
    if len(SHOPS) == 1:
        img, name, url, line = SHOPS[0]
        return f"""<section class="section" aria-labelledby="shops-title">
	<div class="wrap shop-feature">
		<figure class="browser">
			<div class="browser-bar" aria-hidden="true"><span class="dots"><i></i><i></i><i></i></span><span class="url">{url}</span></div>
			<img class="browser-shot" src="assets/img/shops/{img}" width="1200" height="626" alt="The 3D builder on {name}, showing a yellow rear plate" loading="lazy" decoding="async">
		</figure>
		<div class="stack">
			<p class="eyebrow">Live today</p>
			<h2 id="shops-title">Already selling plates at {name}</h2>
			<p class="lede muted">{line}</p>
			<p><a class="try-link" href="https://{url}" rel="noopener">See it on {name}</a></p>
		</div>
	</div>
</section>"""
    cards = ''.join(f'<li><figure class="browser browser-sm"><div class="browser-bar" aria-hidden="true"><span class="dots"><i></i><i></i><i></i></span><span class="url">{url}</span></div><img src="assets/img/shops/{img}" alt="The {name} website" loading="lazy" decoding="async"></figure><p><b>{name}</b> {line}</p></li>' for img, name, url, line in SHOPS)
    return f"""<section class="section" aria-labelledby="shops-title">
	<div class="wrap">
		<div class="intro"><h2 id="shops-title">Shops we've built</h2><p>Real plate shops, running the same builder you can try above.</p></div>
		<ul class="shops">{cards}</ul>
	</div>
</section>"""


def faq_items(pick=None):
    items = [
        ("Is the builder DVLA approved?",
         "<p>No. The software has no DVLA approval or certification, and we don't claim it does. It lays characters out to BS AU 145e measurements and puts your supplier name, postcode and the BS AU 145e mark in the footer, which saves you checking every layout by hand.</p><p>You stay responsible for the plates you make, your registration as a Registered Number Plate Supplier and checking your customers' documents.</p>"),
        ("Do I need to know WordPress?",
         "<p>No. We build the shop, host it and keep it updated. You change prices on one screen (WooCommerce → Plate Builder) and handle orders like any WooCommerce shop.</p>"),
        ("I already have a website. Can you move it over?",
         "<p>Yes. It's included in Complete and an add-on on Starter and Pro: we copy your products, prices and pages across, and keep your domain. Tell us your current website when you sign up.</p>"),
        ("I already run WooCommerce. Can I just add the builder?",
         "<p>Not at the moment. The builder comes as part of a website we build, host and look after, so it always works with your checkout, documents and updates. If you already have a WooCommerce shop, we can move your products and pages across to your new site.</p>"),
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
    if pick is not None:
        items = [items[i] for i in pick]
    return '\n'.join(f'<details><summary>{q}</summary><div>{a}</div></details>' for q, a in items)


# ------------------------------------------------------------------ demo page

QUICK = [
    ('demo.html', 'Front plate, printed'),
    ('demo.html?reg=3D%20BLDR&style=gel-4d-3&side=rear', '4D gel, rear'),
    ('demo.html?reg=RETRO&style=retro', 'Retro pressed'),
    ('demo.html?reg=3D%20BLDR&style=acrylic-4d-3&size=hex', 'Hex, cut to fit'),
    ('demo.html?reg=3D%20BLDR&style=gel-3d&badge=uk-4d', 'UK 4D badge'),
    ('demo.html?reg=3D%20BLDR&style=acrylic-4d-6&border=black-2mm&badge=ev-gel', 'EV badge and border'),
    ('demo.html?reg=3D%20BLDR&size=standard&rear=oversized&side=rear', 'Oversized rear plate'),
    ('demo.html?reg=3D%20BLDR&size=motorbike', 'Motorbike'),
    ('demo.html?reg=3D%20BLDR&style=gel-4d-6&view=car', 'On a car'),
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
					<p>Everything you can see and touch: the 3D plate, styles, sizes, badges, borders, the car view, extras, the live price and the phone layout. It's the same code that runs in a live plate shop.</p>
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

{cta_band('Like what you see?', "Get this builder on a plate shop website of your own, in your colours with your prices. Sign up online in a few minutes.")}
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
        f'<li><a href="demo.html?reg=3D%20BLDR&amp;style={sid}&amp;side=rear"><img src="assets/img/style-{sid}.webp" width="640" height="480" alt="{label} characters on a rear plate" loading="lazy" decoding="async"><span>{label}</span></a><span class="small muted">{desc}</span></li>'
        for sid, label, desc in STYLES)
    return f'''
<section class="page-head page-head-art" aria-labelledby="f-title">
	<div class="wrap">
		<img class="head-plate" src="assets/img/hero-rear.webp" width="881" height="500" alt="" aria-hidden="true">
		<div class="intro">
			<p class="eyebrow">The 3D builder</p>
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
				<a class="try-link" href="demo.html?reg=3D%20BLDR&amp;size=standard&amp;rear=oversized&amp;side=rear">Try a different rear size</a>
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
				<a class="try-link" href="demo.html?reg=3D%20BLDR&amp;style=gel-3d&amp;badge=uk-4d">Try the 4D badge</a>
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
				<a class="try-link" href="demo.html?reg=3D%20BLDR&amp;style=gel-4d-6&amp;view=car">Try the car view</a>
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


from pages_website import website  # noqa: E402,F401
from pages_home import home  # noqa: E402,F401
