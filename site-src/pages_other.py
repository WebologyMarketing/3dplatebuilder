"""Pricing, set-up, contact and legal templates."""
from pages_main import DN, cta_band, tier

Y = '<span class="yes" aria-label="Included">✓</span>'
N = '<span class="no" aria-label="Not included">–</span>'
S = '<span class="soon">Coming soon</span>'


def row(label, s, p, c, o=None):
    o = c if o is None else o
    return f'<tr><th scope="row">{label}</th><td>{s}</td><td>{p}</td><td>{c}</td><td>{o}</td></tr>'


def group(label):
    return f'<tr class="group"><th scope="rowgroup" colspan="5">{label}</th></tr>'


from pricing_new import pricing  # noqa: E402


def setup():
    steps = [
        ('Sign up online', 'Choose your plan and tell us about your shop. It takes about five minutes.', 'Day 1'),
        ('Pay and verify', "We email your secure link to pay the set-up fee and upload your RNPS registration and ID. We check them before we build.", 'Within [RESPONSE TIME]'),
        ('We copy your products and prices', 'From your current site or your price list. We set up the price editor and your extras, and match each design to your products.', '[TIMELINE BUILD]'),
        ('We design your website', "Your supplier name and postcode go in the plate footer, exactly as registered. Your site is designed to your plan: a one-page shop in your colours on Starter, a custom home page and a page for each character style on Pro, a fully custom site on Complete. You review a private preview and ask for changes by email.", '[TIMELINE BRANDING]'),
        ('Connect Stripe', "You open (or already have) your own Stripe account. We connect it and switch on Apple Pay and Google Pay.", '[TIMELINE PAYMENTS]'),
        ('Test orders', 'On your private preview we run real test orders: a road-legal order with documents, a show plate, a failed then successful card payment.', '[TIMELINE TESTING]'),
        ('Go live', "We point your domain at the new shop, switch Stripe to live and watch the first orders come in with you.", '[TIMELINE GO LIVE]'),
    ]
    lis = '\n'.join(f'<li><h3>{t}</h3><div class="stack"><p>{d}</p><p class="when">Usually {w}</p></div></li>' for t, d, w in steps)
    return f'''
<section class="page-head page-head-art" aria-labelledby="s-title">
	<div class="wrap">
		<img class="head-plate" src="assets/img/hero-front.webp" width="1112" height="318" alt="" aria-hidden="true">
		<div class="intro">
			<p class="eyebrow">Sign-up to live</p>
			<h1 id="s-title">How it works</h1>
			<p>You run the workshop; we build the shop. From sign-up to your first order usually takes [TIMELINE TOTAL], all online and by email. No calls, no meetings. <a href="start.html">Start your shop</a>.</p>
		</div>
	</div>
</section>

<section class="section-tight" aria-labelledby="steps-title">
	<div class="wrap">
		<h2 id="steps-title" class="sr-only">Set-up steps</h2>
		<ol class="steps">
			{lis}
		</ol>
	</div>
</section>

<section class="section" aria-labelledby="need-title">
	<div class="wrap spec-split">
		<div class="intro">
			<h2 id="need-title">What we need from you</h2>
			<p>Get these together and set-up goes quickly. We'll email you a checklist when you sign up.</p>
		</div>
		<ul class="feature-list">
			<li><b>Your products and prices</b><span>A link to your current shop, or your price list.</span></li>
			<li><b>Your RNPS registration and ID</b><span>We verify them before we build. Your supplier name and postcode go in the plate footer exactly as registered.</span></li>
			<li><b>Your logo</b><span>SVG or a large PNG, if you have one.</span></li>
			<li><b>A Stripe account</b><span>In your business's name. We'll help you set it up if you don't have one.</span></li>
			<li><b>Your domain</b><span>Who it's registered with, so we can point it at the new shop.</span></li>
			<li><b>Document contacts</b><span>The email, WhatsApp number and postal address customers should send documents to.</span></li>
			<li><b>Collection and fitting</b><span>Your address, opening hours and fitting price, if you offer them.</span></li>
		</ul>
	</div>
</section>

<section class="section" aria-labelledby="after-title">
	<div class="wrap spec-split">
		<div class="intro">
			<h2 id="after-title">After you're live</h2>
			<p>We keep looking after it, so you can get on with making plates.</p>
		</div>
		<ul class="feature-list">
			<li><b>Prices are yours to change</b><span>One screen in WooCommerce. The builder's totals follow straight away.</span></li>
			<li><b>Updates</b><span>We update WordPress, WooCommerce and the builder for you, and test them first.</span></li>
			<li><b>Support</b><span>By email, [SUPPORT HOURS]. Support tickets from your shop's admin are coming soon.</span></li>
			<li><b>Room to grow</b><span>Move up a plan, or add a custom style when you start making a new finish.</span></li>
		</ul>
	</div>
</section>

{cta_band()}
'''


from pages_signup import start, contact  # noqa: E402,F401


BANNER = '''<div class="template-banner" role="note">
	<p><strong>Interim version.</strong> Our solicitor is finalising the full wording. This page states how things work today; we'll update it and the date below when the final version is ready.</p>
</div>'''


def legal_page(title, intro, body):
    return f'''
<section class="page-head" aria-labelledby="l-title">
	<div class="wrap legal">
		<div class="intro">
			<h1 id="l-title">{title}</h1>
			<p>{intro} Last updated: 19 October 2026.</p>
		</div>
		<div style="margin-top:28px">{BANNER}</div>
	</div>
</section>
<section class="section-tight" style="padding-top:0">
	<div class="wrap legal stack">
		{body}
	</div>
</section>'''


def privacy():
    return legal_page('Privacy notice', 'How [COMPANY LEGAL NAME] trading as [TRADING NAME] uses personal information.', '''
<h2>Who we are</h2>
<p>[COMPANY LEGAL NAME] ("we", "us"), trading as [TRADING NAME], company number [COMPANY NUMBER], registered office [REGISTERED ADDRESS]. We are responsible for the personal information collected through this website. Contact us about privacy at <a href="mailto:[EMAIL]">[EMAIL]</a>.</p>

<h2>What we collect</h2>
<ul>
	<li><strong>Sign-ups:</strong> your name, email, business name, the supplier name and postcode on your RNPS registration, your current website and domain, your brand colour, the plan you chose and your message.</li>
	<li><strong>Questions:</strong> your name, email, business name and question.</li>
	<li><strong>Technical data:</strong> your IP address and browser details, which reach the services that deliver this site (see "Who we share it with").</li>
</ul>
<p>The live demo on this site doesn't send your designs anywhere. Registrations you type into it stay in your browser.</p>

<h2>Why we use it</h2>
<ul>
	<li>To reply to your sign-up or question and, if you go ahead, to set up and provide your plan. This is a step you've asked us to take before entering a contract.</li>
	<li>To keep the business records the law requires.</li>
</ul>
<p>We don't sell your information and we don't send marketing emails.</p>

<h2>Who we share it with</h2>
<ul>
	<li>Our web host and email provider, who store and deliver the site and our email for us.</li>
	<li>Google Fonts (Google LLC), which receives your IP address when this site loads its typefaces.</li>
	<li>jsDelivr, which delivers the Three.js files for the live demo and receives your IP address.</li>
</ul>

<h2>Our customers' shops</h2>
<p>When we host a shop for a plate maker, that plate maker is responsible for its own customers' data, including proof of ID and entitlement documents uploaded at checkout. We handle that data only on their behalf. Questions about a particular shop's orders should go to that shop.</p>

<h2>How long we keep it</h2>
<p>Only as long as we need it to reply to you and, if you become a customer, to provide your plan, plus any period the law requires for business records.</p>

<h2>Your rights</h2>
<p>You can ask to see, correct or delete your information, object to or restrict how we use it, or ask for a copy to take elsewhere. Email <a href="mailto:[EMAIL]">[EMAIL]</a>. You can also complain to the Information Commissioner's Office at ico.org.uk.</p>
''')


def terms():
    return legal_page('Terms of service', 'How [BRAND NAME] plans work while our full terms are finalised. For business customers only.', '''
<h2>Before you pay, you'll get the full terms</h2>
<p>Signing up on this site costs nothing and doesn't commit you to anything. When we email your secure link to pay the set-up fee, it comes with our full terms of service. You'll read and agree to them before you pay anything.</p>

<h2>The main points</h2>
<ul>
	<li>Plans are for businesses only, not consumers. You must be a Registered Number Plate Supplier with the DVLA and keep that registration current.</li>
	<li>Each plan includes what's listed for it on our <a href="pricing.html">pricing page</a> when you sign up. Anything marked "coming soon" isn't part of a plan until we tell you in writing that it is.</li>
	<li>The builder helps with BS AU 145e character layout and legal spacing. It is not approved or certified by the DVLA. You're responsible for checking every plate you make and sell is legal, and for checking customers' proof of ID and entitlement.</li>
	<li>Monthly plans have a minimum term of [MINIMUM TERM]. After that you can cancel any time with [NOTICE PERIOD] notice by email to <a href="mailto:[EMAIL]">[EMAIL]</a>. The set-up fee is paid once, at the start.</li>
	<li>We're not VAT registered, so no VAT is added to our prices.</li>
	<li>Your customers pay you through your own Stripe account, under Stripe's terms. We never hold your customers' money.</li>
	<li>Your content, logo, products, customers and orders are yours. We own the builder software and our site designs.</li>
	<li>The law of England and Wales applies.</li>
</ul>
<p>Questions about any of this: <a href="mailto:[EMAIL]">[EMAIL]</a>.</p>
''')


def cookies():
    return legal_page('Cookies', 'Which cookies and similar technologies [DOMAIN] uses.', '''
<h2>What this site uses</h2>
<p>This site sets no cookies of its own and doesn't use analytics or advertising trackers. The live demo keeps your design in the page while you use it and stores nothing on your device afterwards.</p>

<h2>Third-party services</h2>
<p>The site loads its typefaces from Google Fonts and the 3D library (Three.js) from jsDelivr. These services receive your IP address so they can send the files.</p>

<h2>If this changes</h2>
<p>If we ever add analytics or anything else that sets non-essential cookies, we'll ask for your consent first and list each cookie on this page.</p>

<h2>Managing cookies</h2>
<p>You can block or delete cookies in your browser settings.</p>
''')
