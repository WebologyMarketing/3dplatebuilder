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
        ('Your demo', 'We set the builder up with your kind of plates and prices and email you a private link. Try it, send us your questions, and pick a plan.', '[TIMELINE DEMO]'),
        ('We copy your products and prices', 'From your current site or your price list. We set up the price editor, your extras, and match each design to your real products.', '[TIMELINE BUILD]'),
        ('Your website and supplier details', 'Your supplier name and postcode go in the plate footer, exactly as registered. On website packages we design your site to your plan: a ready-made design in your colours on Starter, a custom home page and plate-type pages on Pro, a fully custom site on Complete. On Builder only, we fit the builder into your existing site.', '[TIMELINE BRANDING]'),
        ('Connect Stripe', "You open (or already have) your own Stripe account. We connect it, switch on Apple Pay and Google Pay and check your domain with Apple.", '[TIMELINE PAYMENTS]'),
        ('Documents, collection and fitting', 'We set the document lists customers choose from, where send-later documents should go (email, WhatsApp or post), your collection address and your fitting price.', '[TIMELINE DOCUMENTS]'),
        ('Test orders, together', 'On a private copy of your shop we run real test orders: a road-legal order with uploads, a send-later order, a show plate, a failed then successful card payment, and a staff document check.', '[TIMELINE TESTING]'),
        ('Go live', "We point your domain at the new shop, switch Stripe to live and watch the first orders come in with you.", '[TIMELINE GO LIVE]'),
    ]
    lis = '\n'.join(f'<li><h3>{t}</h3><div class="stack"><p>{d}</p><p class="when">Usually {w}</p></div></li>' for t, d, w in steps)
    return f'''
<section class="page-head" aria-labelledby="s-title">
	<div class="wrap">
		<div class="intro">
			<h1 id="s-title">How we set you up</h1>
			<p>You run the workshop; we build the shop, or add the builder to the one you have. From your demo to your first order usually takes [TIMELINE TOTAL], all arranged by email. {DN('Suggested: 2–4 weeks, depending on how quickly products and Stripe are ready')}</p>
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
			<p>Get these together and set-up goes quickly. We'll email you a checklist after your demo.</p>
		</div>
		<ul class="feature-list">
			<li><b>Your products and prices</b><span>A link to your current shop, or your price list.</span></li>
			<li><b>Supplier name and postcode</b><span>Exactly as on your RNPS registration, for the plate footer.</span></li>
			<li><b>Your logo</b><span>SVG or a large PNG, if you have one.</span></li>
			<li><b>A Stripe account</b><span>In your business's name. We'll help you set it up if you don't have one.</span></li>
			<li><b>Your domain</b><span>Who it's registered with, so we can point it at the new shop.</span></li>
			<li><b>Builder only: access to your site</b><span>A WordPress admin login for us, and your hosting details, so we can check and install the builder.</span></li>
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

{cta_band('Ready when you are', "Request a demo and we'll email you a set-up date.")}
'''


def contact():
    return f'''
<section class="page-head" aria-labelledby="c-title">
	<div class="wrap">
		<div class="intro">
			<h1 id="c-title">Request a demo</h1>
			<p>Tell us a bit about your shop. We'll set the builder up with your kind of plates and email you a private demo link. No sales calls.</p>
		</div>
	</div>
</section>

<section class="section-tight" style="padding-top:0">
	<div class="wrap contact-split">
		<form class="form-grid" action="[FORM ENDPOINT]" method="post" data-demo-form data-email="[EMAIL]" novalidate>
			<div class="field">
				<label for="f-name">Your name <span class="req" aria-hidden="true">*</span></label>
				<input id="f-name" name="name" type="text" autocomplete="name" required>
			</div>
			<div class="field">
				<label for="f-business">Business name <span class="req" aria-hidden="true">*</span></label>
				<input id="f-business" name="business" type="text" autocomplete="organization" required>
			</div>
			<div class="field full">
				<label for="f-email">Email <span class="req" aria-hidden="true">*</span></label>
				<input id="f-email" name="email" type="email" autocomplete="email" required>
			</div>
			<div class="field full">
				<label for="f-website">Your current website</label>
				<input id="f-website" name="website" type="url" inputmode="url" placeholder="https://" aria-describedby="f-website-hint">
				<span class="hint" id="f-website-hint">Leave it blank if you don't have one yet.</span>
			</div>
			<div class="field">
				<label for="f-platform">Current platform <span class="req" aria-hidden="true">*</span></label>
				<select id="f-platform" name="platform" required>
					<option value="">Choose one</option>
					<option>WooCommerce / WordPress</option>
					<option>Shopify</option>
					<option>Wix</option>
					<option>Squarespace</option>
					<option>eBay or Amazon only</option>
					<option>Built for us by an agency</option>
					<option>No website yet</option>
					<option>Something else</option>
				</select>
			</div>
			<div class="field">
				<label for="f-orders">Plate orders a month <span class="req" aria-hidden="true">*</span></label>
				<select id="f-orders" name="orders" required>
					<option value="">Choose one</option>
					<option>Under 50</option>
					<option>50 to 200</option>
					<option>200 to 500</option>
					<option>500 to 1,000</option>
					<option>Over 1,000</option>
				</select>
			</div>
			<div class="field full">
				<label for="f-plan">Plan you're interested in</label>
				<select id="f-plan" name="plan">
					<option value="">Not sure yet</option>
					<option value="starter">Starter</option>
					<option value="pro">Pro</option>
					<option value="complete">Complete</option>
					<option value="outright">Buy outright</option>
					<option value="builder-only">Builder only, on my own WooCommerce site</option>
				</select>
			</div>
			<div class="field full">
				<label for="f-message">Message</label>
				<textarea id="f-message" name="message" rows="5" placeholder="What you sell, how you sell it, and anything you'd like to see in the demo."></textarea>
			</div>
			<div class="hp" aria-hidden="true">
				<label for="f-company-url">Leave this empty</label>
				<input id="f-company-url" name="company_url" type="text" tabindex="-1" autocomplete="off">
			</div>
			<div class="field full">
				<p class="small muted">Fields marked * are required. We'll use your details to reply and set up your demo. See our <a href="privacy.html">privacy notice</a>.</p>
			</div>
			<div class="field full">
				<button type="submit" class="btn btn-primary">Send demo request</button>
				<p class="form-status" data-form-status role="status" aria-live="polite"></p>
			</div>
		</form>

		<aside class="contact-card" aria-labelledby="direct-title">
			<h2 id="direct-title" style="font-size:var(--step-2)">Or email us</h2>
			<dl>
				<div><dt>Email</dt><dd><a href="mailto:[EMAIL]">[EMAIL]</a></dd></div>
								<div><dt>Replies</dt><dd>Within [RESPONSE TIME]</dd></div>
			</dl>
			<p class="small muted">We don't do phone calls. Everything is handled by email, so you've always got it in writing. If the form doesn't send, it opens your email app with your answers filled in.</p>
		</aside>
	</div>
</section>
<script>
	// Pre-select the plan from pricing links (contact.html?plan=pro).
	(() => {{
		const p = new URLSearchParams(location.search).get('plan');
		const s = document.getElementById('f-plan');
		if (p && s && [...s.options].some((o) => o.value === p)) s.value = p;
	}})();
</script>
'''


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
	<li><strong>Demo requests:</strong> your name, business name, email, website, current platform, how many plate orders you take a month, the plan you're interested in and your message.</li>
	<li><strong>Customers:</strong> contact, billing and account details needed to provide your plan. [ADD ANY OTHERS]</li>
	<li><strong>Technical data:</strong> your IP address and browser details, which reach the services that deliver this site (see "Who we share it with").</li>
</ul>
<p>The live demo on this site doesn't send your designs anywhere. Registrations you type into it stay in your browser.</p>

<h2>Why we use it, and our lawful basis</h2>
<table>
	<thead><tr><th>Purpose</th><th>Lawful basis</th></tr></thead>
	<tbody>
		<tr><td>Replying to demo requests and setting up demos</td><td>Steps at your request before a contract; legitimate interests [CONFIRM]</td></tr>
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
<p>Demo requests: [RETENTION PERIOD]. Customer records: [RETENTION PERIOD]. Accounting records: as long as the law requires.</p>

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

<h2>5a. Builder only</h2>
<p>A licence to use the builder on your own WordPress and WooCommerce site, for one shop on one domain, installed and set up by us. You are responsible for your hosting, backups, security and WordPress, WooCommerce and theme updates. We keep the builder updated while your plan (or yearly fee) is paid. We'll check your site before installing, but we can't guarantee the builder works with every theme, plugin or hosting set-up. [BUILDER-ONLY TERMS]</p>

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
