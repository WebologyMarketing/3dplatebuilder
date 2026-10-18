"""Pricing page: website packages (builder + website, growing by tier) and builder only."""
from pages_main import DN, cta_band

Y = '<span class="yes" aria-label="Included">✓</span>'
N = '<span class="no" aria-label="Not included">–</span>'
S = '<span class="soon">Coming soon</span>'


def row(label, s, p, c, o=None, cls=''):
    o = c if o is None else o
    c_attr = f' class="{cls}"' if cls else ''
    return f'<tr{c_attr}><th scope="row">{label}</th><td>{s}</td><td>{p}</td><td>{c}</td><td>{o}</td></tr>'


def group(label, cls=''):
    c_attr = f' class="group {cls}"' if cls else ' class="group"'
    return f'<tr{c_attr}><th scope="rowgroup" colspan="5">{label}</th></tr>'


TIERS = {
    'starter': dict(
        name='Starter',
        who='For workshops that want the 3D builder and a proper shop, without the extras.',
        web=('[£ STARTER MONTHLY]', '[£ STARTER SET-UP]', 'Suggested: £79–£99 a month, £395–£595 set-up'),
        only=('[£ STARTER BUILDER-ONLY MONTHLY]', '[£ STARTER BUILDER-ONLY SET-UP]', 'Suggested: £39–£49 a month, £195–£295 set-up'),
        builder=['Real-time 3D builder: turn, zoom, front and rear', 'Legal spacing and Charles Wright characters as they type',
                 'All seven character styles', 'Every plate size, including motorbike and trailer',
                 'Your supplier name, postcode and BS AU 145e footer', 'Price editor, live prices and extras',
                 'Design Your Plates page and menu item', 'In-page builder on phones'],
        website=['A ready-made plate-shop design in your logo and colours',
                 f'Up to [STARTER PAGES] pages: home, design your plates, valid documents, about, contact {DN("Suggested: 6")}',
                 f'Up to [STARTER PRODUCTS] products copied from your price list {DN("Suggested: 30")}',
                 'Hosting, SSL, backups and your domain connected',
                 'Google Analytics and Search Console set up',
                 'Privacy, terms, delivery and returns page templates for you to check'],
    ),
    'pro': dict(
        name='Pro',
        who='For shops that sell mostly online and want the full showroom.',
        web=('[£ PRO MONTHLY]', '[£ PRO SET-UP]', 'Suggested: £149–£199 a month, £795–£1,195 set-up'),
        only=('[£ PRO BUILDER-ONLY MONTHLY]', '[£ PRO BUILDER-ONLY SET-UP]', 'Suggested: £79–£99 a month, £295–£495 set-up'),
        builder=['UK, EV and 4D badges, printed or gel', 'Black borders, 1, 2 or 3 mm', 'Different front and rear sizes',
                 '"On a car" view', 'Phone app layout', 'Builder on every plate product page',
                 'Orders added as your real products', 'Floating "Design in 3D" button'],
        website=['A home page designed around your shop',
                 f'Up to [PRO PAGES] pages, with a landing page for each plate type: 4D, gel, show, motorbike {DN("Suggested: 15")}',
                 f'Up to [PRO PRODUCTS] products, with your product photos edited and laid out {DN("Suggested: 100")}',
                 'Search basics on every page: titles, descriptions and product details for Google',
                 'Your Google reviews shown on the site, from your Google Business Profile',
                 'Email addresses on your own domain set up'],
    ),
    'complete': dict(
        name='Complete',
        who='For busy shops that want customers to design, pay and send documents in one go.',
        web=('[£ COMPLETE MONTHLY]', '[£ COMPLETE SET-UP]', 'Suggested: £279–£349 a month, £1,495–£2,495 set-up'),
        only=('[£ COMPLETE BUILDER-ONLY MONTHLY]', '[£ COMPLETE BUILDER-ONLY SET-UP]', 'Suggested: £129–£159 a month, £495–£795 set-up'),
        builder=['Checkout inside the builder', 'Apple Pay and Google Pay through Stripe', 'Proof of ID and entitlement upload, or send later',
                 'Awaiting documents status and staff checks', 'Collect in store and in-store fitting', 'Landing-page and category promos'],
        website=['A fully custom design, made for your shop',
                 f'Up to [COMPLETE PAGES] pages, including local pages for the towns you serve {DN("Suggested: 30")}',
                 'Unlimited products, and moving your current site across included',
                 f'[COMPLETE LAUNCH ARTICLES] launch articles written for plate buyers {DN("Suggested: 4")}',
                 f'[COMPLETE MONTHLY HOURS] hours of changes to your site every month {DN("Suggested: 2")}',
                 'A monthly email report on your visits and orders'],
    ),
}

ORDER = [('starter', None, False), ('pro', 'Starter', False), ('complete', 'Pro', True)]


def plan_card(pid, inherit, featured):
    t = TIERS[pid]
    cls = 'plan is-featured' if featured else 'plan'
    btn = 'btn btn-primary' if featured else 'btn btn-ghost'
    head = f'<li class="inherit">Everything in {inherit}, plus:</li>' if inherit else ''
    builder = head + ''.join(f'<li>{i}</li>' for i in t['builder'])
    website = (f'<li class="inherit">Everything in {inherit}\'s website, plus:</li>' if inherit else '') + ''.join(f'<li>{i}</li>' for i in t['website'])
    wm, ws, wn = t['web']
    om, os_, on = t['only']
    return f'''<article class="{cls}" id="{pid}">
		<h3>{t['name']}</h3>
		<p class="for">{t['who']}</p>
		<p class="price" data-for="website"><strong>{wm} a month</strong><span>+ {ws} one-off set-up {DN(wn)}</span></p>
		<p class="price" data-for="builder" hidden><strong>{om} a month</strong><span>+ {os_} one-off set-up {DN(on)}</span></p>
		<p class="plan-sub">The builder</p>
		<ul>{builder}</ul>
		<p class="plan-sub" data-for="website">Your website</p>
		<ul data-for="website">{website}</ul>
		<a class="{btn}" href="contact.html?plan={pid}">Request a {t['name']} demo</a>
	</article>'''


def pricing():
    W = lambda s: s  # readability
    table = ''.join([
        group('The 3D builder'),
        row('Real-time 3D preview: turn, zoom, front and rear', Y, Y, Y),
        row('Legal spacing and Charles Wright characters as they type', Y, Y, Y),
        row('Road legal / show plate status, and fit checks', Y, Y, Y),
        row('Supplier name, postcode and BS AU 145e footer', Y, Y, Y),
        row('Seven character styles (printed, 3D gel, 4D, 4D gel, retro)', Y, Y, Y),
        row('Every plate size, including motorbike and trailer', Y, Y, Y),
        row('Different front and rear sizes', N, Y, Y),
        row('UK, EV and 4D badges; 1, 2 and 3 mm borders', N, Y, Y),
        row('"On a car" view with six paint colours', N, Y, Y),
        row('Phone app layout (full screen, step cards, buy bar)', 'In-page', Y, Y),
        row('Price editor, live prices and extras', Y, Y, Y),
        row('Design Your Plates page, menu item and ?reg= links', Y, Y, Y),
        row('Builder on every plate product page, with Classic switch', N, Y, Y),
        row('Orders added as your matching products', N, Y, Y),
        row('Floating "Design in 3D" button', N, Y, Y),
        row('Landing-page and category promos', N, N, Y),
        group('Checkout and orders'),
        row('Checkout inside the builder', N, N, Y),
        row('Apple Pay and Google Pay through Stripe', N, N, Y),
        row('Proof of ID and entitlement: upload or send later', N, N, Y),
        row('Awaiting documents status and staff check panel', N, N, Y),
        row('Collect in store and in-store fitting add-on', N, N, Y),
        row('Delivery choice cards at checkout', S, S, S),
        group('Your website (website packages)', 'web-row'),
        row('Design', 'Ready-made, in your colours', 'Custom home page', 'Fully custom', 'Fully custom', 'web-row'),
        row('Pages', 'Up to [STARTER PAGES]', 'Up to [PRO PAGES]', 'Up to [COMPLETE PAGES]', 'Up to [COMPLETE PAGES]', 'web-row'),
        row('Products set up', 'Up to [STARTER PRODUCTS]', 'Up to [PRO PRODUCTS]', 'Unlimited', 'Unlimited', 'web-row'),
        row('Landing pages for each plate type', N, Y, Y, Y, 'web-row'),
        row('Local pages for the towns you serve', N, N, Y, Y, 'web-row'),
        row('Product photos edited and laid out', N, Y, Y, Y, 'web-row'),
        row('Search basics: titles, descriptions, product details for Google', N, Y, Y, Y, 'web-row'),
        row('Google reviews shown on the site', N, Y, Y, Y, 'web-row'),
        row('Email addresses on your domain set up', N, Y, Y, Y, 'web-row'),
        row('Moving your current site across', 'Add-on', 'Add-on', Y, Y, 'web-row'),
        row('Launch articles for plate buyers', N, N, '[COMPLETE LAUNCH ARTICLES]', '[COMPLETE LAUNCH ARTICLES]', 'web-row'),
        row('Changes to your site each month', N, N, '[COMPLETE MONTHLY HOURS] hours', 'Yearly fee covers [OUTRIGHT MONTHLY HOURS] hours', 'web-row'),
        row('Monthly visits and orders report', N, N, Y, Y, 'web-row'),
        row('Google Analytics and Search Console set up', Y, Y, Y, Y, 'web-row'),
        row('Hosting, SSL and backups', Y, Y, Y, 'Yearly fee', 'web-row'),
        group('Looking after it'),
        row('Builder updates', Y, Y, Y, 'Yearly fee'),
        row('WordPress and WooCommerce updates', 'Website packages', 'Website packages', 'Website packages', 'Yearly fee'),
        row('Support by email', Y, Y, Y, 'Yearly fee'),
        row('Support tickets from your shop\'s admin', S, S, S, S),
        row('Account dashboard for your plan and invoices', S, S, S, S),
    ])
    cards = '\n'.join(plan_card(*o) for o in ORDER)

    return f'''
<section class="page-head" aria-labelledby="p-title">
	<div class="wrap">
		<div class="intro">
			<h1 id="p-title">Pricing</h1>
			<p>Get the 3D builder as part of a complete website, built, hosted and looked after by us. Or add it to the WooCommerce shop you already have. Each plan adds more as you go up. All prices exclude VAT.</p>
		</div>
		<p style="margin-top:16px">{DN('Draft notes like this one show suggested prices and limits. tools/fill-placeholders.py removes them when you fill in your own.')}</p>
	</div>
</section>

<section class="section-tight pricing" data-pricing="website" aria-labelledby="monthly-title">
	<div class="wrap">
		<span id="builder-only" class="anchor"></span>
		<div class="pricing-head">
			<h2 id="monthly-title">Monthly plans</h2>
			<div class="view-tabs pricing-switch" role="group" aria-label="What you need">
				<button type="button" data-pricing-mode="website" aria-pressed="true">Website + builder</button>
				<button type="button" data-pricing-mode="builder" aria-pressed="false">Builder only</button>
			</div>
		</div>
		<p class="pricing-note muted" data-for="website">A new plate shop with the builder built in. We design it, build it, host it and keep it up to date. The higher the plan, the more of the builder and the more website you get.</p>
		<p class="pricing-note muted" data-for="builder" hidden>Already selling on WordPress and WooCommerce? We add the builder to your own site. It needs WordPress 6.5 or later. We check your theme, checkout and hosting first, then install it, set your prices and test real orders with you. You keep your own hosting and look after WordPress updates; we keep the builder updated.</p>
		<div class="plans">
			{cards}
		</div>

		<div class="outright" id="outright" data-for="website">
			<div class="stack">
				<h3>Buy it outright</h3>
				<p class="muted">One payment for a Complete website and a builder licence for your shop. After that, a yearly fee covers hosting, updates, support and [OUTRIGHT MONTHLY HOURS] hours of changes a month. {DN('Suggested: 1 hour a month')}</p>
				<ul class="feature-list">
					<li><b>Licence</b><span>For one shop on one domain.</span></li>
					<li><b>Hosting and updates</b><span>Billed yearly from [OUTRIGHT HOSTING START].</span></li>
				</ul>
			</div>
			<div class="stack">
				<p class="price"><strong>[£ OUTRIGHT PRICE] one-off</strong></p>
				<p class="muted">then [£ OUTRIGHT HOSTING YEARLY] a year</p>
				<p>{DN('Suggested: £4,950–£7,950 one-off, then £795–£1,195 a year, starting from go-live')}</p>
				<a class="btn btn-primary" href="contact.html?plan=outright">Request a demo</a>
			</div>
		</div>
		<div class="outright" id="outright-builder" data-for="builder" hidden>
			<div class="stack">
				<h3>Buy the builder outright</h3>
				<p class="muted">One payment for a Complete builder licence on your own WooCommerce site, installed and set up by us. After that, a yearly fee keeps the builder updated and covers support.</p>
				<ul class="feature-list">
					<li><b>Licence</b><span>For one shop on one domain.</span></li>
					<li><b>Your site, your hosting</b><span>You look after hosting and WordPress updates.</span></li>
				</ul>
			</div>
			<div class="stack">
				<p class="price"><strong>[£ BUILDER-ONLY OUTRIGHT] one-off</strong></p>
				<p class="muted">then [£ BUILDER-ONLY UPDATES YEARLY] a year for updates and support</p>
				<p>{DN('Suggested: £1,950–£2,950 one-off, then £395–£595 a year')}</p>
				<a class="btn btn-primary" href="contact.html?plan=builder-only">Request a demo</a>
			</div>
		</div>
	</div>
</section>

<section class="section" aria-labelledby="compare-title">
	<div class="wrap">
		<h2 id="compare-title">Compare plans</h2>
		<div class="table-scroll" tabindex="0" role="region" aria-labelledby="compare-title">
			<table class="compare">
				<caption>Website packages. Builder only gets the same builder, checkout and support rows on your own site; the website rows don't apply. "Buy outright" includes everything in Complete.</caption>
				<thead>
					<tr><th scope="col">Feature</th><th scope="col">Starter</th><th scope="col">Pro</th><th scope="col">Complete</th><th scope="col">Buy outright</th></tr>
					<tr><th scope="row">Website + builder</th><td>[£ STARTER MONTHLY]/mo</td><td>[£ PRO MONTHLY]/mo</td><td>[£ COMPLETE MONTHLY]/mo</td><td>[£ OUTRIGHT PRICE]</td></tr>
					<tr><th scope="row">Set-up / yearly</th><td>[£ STARTER SET-UP]</td><td>[£ PRO SET-UP]</td><td>[£ COMPLETE SET-UP]</td><td>[£ OUTRIGHT HOSTING YEARLY]/yr</td></tr>
					<tr><th scope="row">Builder only</th><td>[£ STARTER BUILDER-ONLY MONTHLY]/mo</td><td>[£ PRO BUILDER-ONLY MONTHLY]/mo</td><td>[£ COMPLETE BUILDER-ONLY MONTHLY]/mo</td><td>[£ BUILDER-ONLY OUTRIGHT]</td></tr>
					<tr><th scope="row">Set-up / yearly</th><td>[£ STARTER BUILDER-ONLY SET-UP]</td><td>[£ PRO BUILDER-ONLY SET-UP]</td><td>[£ COMPLETE BUILDER-ONLY SET-UP]</td><td>[£ BUILDER-ONLY UPDATES YEARLY]/yr</td></tr>
				</thead>
				<tbody>{table}</tbody>
			</table>
		</div>
	</div>
</section>

<section class="section" aria-labelledby="terms-title">
	<div class="wrap spec-split">
		<div class="intro">
			<h2 id="terms-title">Set-up, hosting and terms</h2>
			<p>The short version. The full terms are in our <a href="terms.html">terms of service</a>.</p>
		</div>
		<ul class="feature-list">
			<li><b>Set-up fee</b><span>Paid once, before we start. For website packages it covers designing and building your site, copying your products and prices, adding your supplier details and logo, connecting Stripe and testing orders with you. For builder only, it covers checking your site, installing and setting up the builder, and testing orders.</span></li>
			<li><b>Monthly plans</b><span>Billed monthly in advance. Minimum term [MINIMUM TERM], then [NOTICE PERIOD] notice. {DN('Suggested: 12 months, then 1 month')}</span></li>
			<li><b>Hosting</b><span>Website packages run on [HOSTING PROVIDER], with an SSL certificate and backups [BACKUP FREQUENCY]. Builder only runs on your own hosting.</span></li>
			<li><b>Updates and support</b><span>We keep the builder up to date on every plan, and WordPress and WooCommerce too on website packages. Support by email, [SUPPORT HOURS]. No phone lines: everything is in writing. Support tickets from your shop's admin are coming soon.</span></li>
			<li><b>Monthly changes</b><span>Complete includes [COMPLETE MONTHLY HOURS] hours of changes a month. Unused hours don't roll over. Extra work is charged at the design rate below. [MONTHLY HOURS TERMS]</span></li>
			<li><b>Changing plan</b><span>Move up a plan at any time; we'll switch on the extra features and do the extra website work. Builder-only shops can move to a website package too. [PLAN CHANGE TERMS]</span></li>
			<li><b>Card fees</b><span>Payments go to your own Stripe account, and Stripe charges its fees to you directly.</span></li>
			<li><b>It's your shop</b><span>Your domain, products, customers and orders are yours. If you leave, [EXIT TERMS].</span></li>
		</ul>
	</div>
</section>

<section class="section" id="addons" aria-labelledby="addons-title">
	<div class="wrap">
		<div class="intro">
			<h2 id="addons-title">Add-ons</h2>
			<p>Available with any plan, website or builder only.</p>
		</div>
		<ul class="benefits">
			<li><h3>Custom character style</h3><p>A finish you make that isn't one of the seven, modelled in 3D and added to the builder with its own price.</p><p><strong>[£ ADDON CUSTOM STYLE]</strong> per style {DN('Suggested: £295–£495')}</p></li>
			<li><h3>Your logo on the plate and car</h3><p>Your logo in the legal footer instead of your name as text, and your badge on the car in the "on a car" view.</p><p><strong>[£ ADDON PLATE LOGO]</strong> one-off {DN('Suggested: £95–£150')}</p></li>
			<li><h3>Extra design work</h3><p>Banners, extra pages, product photos laid out, or changes beyond your plan.</p><p><strong>[£ ADDON DESIGN HOURLY]</strong> an hour {DN('Suggested: £45–£65 an hour')}</p></li>
			<li><h3>Moving from your current site</h3><p>We copy your products, prices and pages from your current shop, and keep your domain and search rankings in mind. Included in Complete.</p><p>From <strong>[£ ADDON MIGRATION]</strong> {DN('Suggested: £295–£995, depending on size')}</p></li>
		</ul>
		<p class="small muted" style="margin-top:24px">Your supplier name and postcode are always printed in the plate footer, on every plan. BS AU 145e requires them.</p>
	</div>
</section>

{cta_band('Not sure which plan?', "Tell us how many plates you sell, how you sell them and whether you already have a WooCommerce shop, and we'll tell you which plan fits. No pressure.")}
'''
