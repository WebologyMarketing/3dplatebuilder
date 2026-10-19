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
        ('We design your website', "Your supplier name and postcode go in the plate footer, exactly as registered. Your site is designed to your plan: our plate-shop design in your colours on Starter, a custom home page and plate-type pages on Pro, a fully custom site on Complete. You review a private preview and ask for changes by email.", '[TIMELINE BRANDING]'),
        ('Connect Stripe', "You open (or already have) your own Stripe account. We connect it and switch on Apple Pay and Google Pay.", '[TIMELINE PAYMENTS]'),
        ('Test orders', 'On your private preview we run real test orders: a road-legal order with documents, a show plate, a failed then successful card payment.', '[TIMELINE TESTING]'),
        ('Go live', "We point your domain at the new shop, switch Stripe to live and watch the first orders come in with you.", '[TIMELINE GO LIVE]'),
    ]
    lis = '\n'.join(f'<li><h3>{t}</h3><div class="stack"><p>{d}</p><p class="when">Usually {w}</p></div></li>' for t, d, w in steps)
    return f'''
<section class="page-head" aria-labelledby="s-title">
	<div class="wrap">
		<div class="intro">
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
	<p><strong>Template for your solicitor.</strong> This page is a starting draft, not legal advice. Everything in [SQUARE BRACKETS] needs filling in, and the whole page needs checking before the site goes live.</p>
</div>'''


def legal_page(title, intro, body):
    return f'''
<section class="page-head" aria-labelledby="l-title">
	<div class="wrap legal">
		<div class="intro">
			<h1 id="l-title">{title}</h1>
			<p>{intro} Last updated: [DATE].</p>
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
    return legal_page('Privacy notice', 'How [COMPANY LEGAL NAME] trading as [BRAND NAME] uses personal information.', '''
<h2>Who we are</h2>
<p>[COMPANY LEGAL NAME] ("we", "us"), trading as [BRAND NAME], company number [COMPANY NUMBER], registered office [REGISTERED ADDRESS]. We are the controller for personal information collected through [DOMAIN]. ICO registration number: [ICO REGISTRATION NUMBER]. Contact us about privacy at [PRIVACY EMAIL].</p>

<h2>What we collect</h2>
<ul>
	<li><strong>Sign-ups:</strong> your name, email, business name, the supplier name and postcode on your RNPS registration, your current website and domain, your brand colour, the plan you chose and your message. <strong>Questions:</strong> your name, email, business name and question.</li>
	<li><strong>Customers:</strong> contact, billing and account details needed to provide your plan. [ADD ANY OTHERS]</li>
	<li><strong>Technical data:</strong> your IP address and browser details, which reach the services that deliver this site (see "Who we share it with").</li>
</ul>
<p>The live demo on this site doesn't send your designs anywhere. Registrations you type into it stay in your browser.</p>

<h2>Why we use it, and our lawful basis</h2>
<table>
	<thead><tr><th>Purpose</th><th>Lawful basis</th></tr></thead>
	<tbody>
		<tr><td>Replying to sign-ups and questions, and setting up your shop</td><td>Steps at your request before a contract; legitimate interests [CONFIRM]</td></tr>
		<tr><td>Providing your plan, billing and support</td><td>Contract</td></tr>
		<tr><td>Keeping business records</td><td>Legal obligation</td></tr>
		<tr><td>Occasional product news to business contacts</td><td>Legitimate interests, with an opt-out in every email [CONFIRM UNDER PECR]</td></tr>
	</tbody>
</table>

<h2>Who we share it with</h2>
<ul>
	<li>Form handling: [FORM PROVIDER]</li>
	<li>Email: [EMAIL PROVIDER]</li>
	<li>Website hosting: [HOSTING PROVIDER]</li>
	<li>Fonts: Google Fonts (Google LLC), which receives your IP address when this site loads its typeface. [OR: REMOVE IF YOU SELF-HOST THE FONT]</li>
	<li>3D library: jsDelivr, which delivers the Three.js files for the demo and receives your IP address.</li>
	<li>[ACCOUNTANT / PAYMENT PROVIDER / CRM]</li>
</ul>
<p>[INTERNATIONAL TRANSFERS: describe any transfers outside the UK and the safeguards used.]</p>

<h2>Our customers' shops</h2>
<p>When we host a shop for a plate maker, that plate maker is the controller for its own customers' data, including proof of ID and entitlement documents uploaded at checkout. We act as their processor under our data processing agreement [DPA REFERENCE]. Questions about a particular shop's orders should go to that shop.</p>

<h2>How long we keep it</h2>
<p>Sign-ups and questions that don't lead to an order: [RETENTION PERIOD]. Customer records: [RETENTION PERIOD]. Accounting records: as long as the law requires.</p>

<h2>Your rights</h2>
<p>You can ask to see, correct or delete your information, object to or restrict how we use it, or ask for a copy to take elsewhere. Email [PRIVACY EMAIL]. You can also complain to the Information Commissioner's Office at ico.org.uk.</p>
''')


def terms():
    return legal_page('Terms of service', 'The terms for [BRAND NAME] plans, set-up and outright purchases. For business customers only.', '''
<h2>1. About these terms</h2>
<p>These terms are between [COMPANY LEGAL NAME] ("we") and the business that orders a plan ("you"). They apply to business customers only, not consumers. [CONFIRM]</p>

<h2>2. What we provide</h2>
<p>A WooCommerce website with the 3D plate builder, set up as described for your plan on our pricing page at the time you order. Features marked "coming soon" are not part of any plan until we tell you in writing that they are. [SERVICE DESCRIPTION / ORDER FORM REFERENCE]</p>

<h2>3. Set-up</h2>
<p>The set-up fee is paid before work starts and is [REFUNDABLE / NON-REFUNDABLE] once work has started. Timings depend on you providing the items on our set-up checklist. [SET-UP TERMS]</p>

<h2>4. Monthly plans</h2>
<p>Billed monthly in advance. Minimum term [MINIMUM TERM], then [NOTICE PERIOD] notice. [PRICE CHANGE TERMS] [LATE PAYMENT TERMS]</p>

<h2>5. Buying outright</h2>
<p>A one-off fee for your website and a licence to use the builder for one shop on one domain. The licence is not transferable and does not allow resale, sharing or copying of the builder. Hosting, updates and support are billed yearly at [£ OUTRIGHT HOSTING YEARLY]. [WHAT HAPPENS IF YEARLY FEE IS NOT RENEWED] [SELF-HOSTING TERMS]</p>

<h2>5b. Website work included in plans</h2>
<p>Page counts, product counts, design level, launch articles and monthly change hours are as listed for your plan on our pricing page when you order. Unused monthly hours don't carry over. [WEBSITE SCOPE TERMS]</p>

<h2>6. Your responsibilities</h2>
<ul>
	<li>You must be a Registered Number Plate Supplier with the DVLA, and keep that registration current.</li>
	<li>The builder helps with BS AU 145e character layout and legal spacing. It is not approved or certified by the DVLA. You are responsible for checking that every plate you make and sell is legal, and for checking customers' proof of ID and entitlement.</li>
	<li>You are the controller for your customers' personal data and must have your own privacy notice and lawful basis. [DPA REFERENCE]</li>
	<li>Your prices, product descriptions and delivery terms are yours to set and to get right.</li>
</ul>

<h2>7. Hosting, availability and support</h2>
<p>[SERVICE LEVEL: uptime target, maintenance windows, support hours [SUPPORT HOURS], response times.]</p>

<h2>8. Payments to you</h2>
<p>Payments from your customers go through your own Stripe account under Stripe's terms. We don't hold your customers' money.</p>

<h2>9. Intellectual property</h2>
<p>We own the builder software and our site designs. You own your content, logo, products and data. The builder includes Three.js, used under the MIT licence. [FONT AND THIRD-PARTY LICENCE TERMS]</p>

<h2>10. Ending the agreement</h2>
<p>[TERMINATION RIGHTS] When the agreement ends, [EXIT TERMS].</p>

<h2>11. Liability</h2>
<p>[LIABILITY CAP AND EXCLUSIONS]</p>

<h2>12. Law</h2>
<p>These terms are governed by the law of England and Wales, and the courts of England and Wales have exclusive jurisdiction. [CONFIRM]</p>
''')


def cookies():
    return legal_page('Cookies', 'Which cookies and similar technologies [DOMAIN] uses.', '''
<h2>What this site uses</h2>
<p>As built, this site sets no cookies of its own and doesn't use analytics or advertising trackers. The live demo keeps your design in the page while you use it and stores nothing on your device afterwards.</p>
<table>
	<thead><tr><th>Name</th><th>Provider</th><th>Purpose</th><th>Duration</th></tr></thead>
	<tbody>
		<tr><td>None set by default</td><td>[BRAND NAME]</td><td>–</td><td>–</td></tr>
		<tr><td>[ADD IF YOU INSTALL ANALYTICS, CHAT OR A FORM SERVICE THAT SETS COOKIES]</td><td>[PROVIDER]</td><td>[PURPOSE]</td><td>[DURATION]</td></tr>
	</tbody>
</table>

<h2>Third-party services</h2>
<p>The site loads its typeface from Google Fonts and the 3D library (Three.js) from jsDelivr. These services receive your IP address so they can send the files. [CHECK WHETHER EITHER SETS COOKIES IN YOUR CONFIGURATION]</p>

<h2>If you add analytics later</h2>
<p>Non-essential cookies need consent before they're set. Add a consent banner and list each cookie in the table above. [CONSENT TOOL]</p>

<h2>Managing cookies</h2>
<p>You can block or delete cookies in your browser settings.</p>
''')
