"""Start your shop (sign-up) and Ask a question. Copy in UK English."""

PLAN_CHOICES = [
    ('starter', 'Starter', '[£ STARTER MONTHLY] a month', '+ [£ STARTER SET-UP] set-up', 'Our plate-shop design in your colours, with the 3D builder.'),
    ('pro', 'Pro', '[£ PRO MONTHLY] a month', '+ [£ PRO SET-UP] set-up', 'A home page designed for you, plate-type pages, badges, borders and the car view.'),
    ('complete', 'Complete', '[£ COMPLETE MONTHLY] a month', '+ [£ COMPLETE SET-UP] set-up', 'Fully custom, with checkout in the builder, document checks and monthly changes.'),
    ('outright', 'Buy outright', '[£ OUTRIGHT PRICE] one-off', 'then [£ OUTRIGHT HOSTING YEARLY] a year', 'Everything in Complete, paid once, then yearly for hosting and updates.'),
]


def start():
    cards = ''.join(
        '<label class="plan-pick">'
        f'<input type="radio" name="plan" value="{v}" required>'
        f'<span class="plan-pick-body"><b>{n}</b><span class="plan-pick-price">{pr} <small>{su}</small></span><span class="plan-pick-line">{line}</span></span>'
        '</label>'
        for v, n, pr, su, line in PLAN_CHOICES)
    return f"""
<section class="page-head page-head-compact" aria-labelledby="st-title">
	<div class="wrap">
		<div class="intro">
			<p class="eyebrow">Sign up</p>
			<h1 id="st-title">Start your shop</h1>
			<p>Three quick steps, about five minutes. You've seen the builder; now tell us about your shop and we'll build it. No calls, and nothing to pay on this page.</p>
		</div>
		<ol class="mini-steps" aria-label="Sign-up steps">
			<li><b>1</b> Choose your plan</li>
			<li><b>2</b> About your shop</li>
			<li><b>3</b> Pay and verify by secure link</li>
		</ol>
	</div>
</section>

<section class="section-tight" style="padding-top:0">
	<div class="wrap start-split">
		<form class="start-form" action="[FORM ENDPOINT]" method="post" data-demo-form data-email="[EMAIL]" data-subject="Shop sign-up" data-success="Thanks, you're signed up. We'll email your secure link to pay and verify within [RESPONSE TIME]." novalidate>
			<input type="hidden" name="type" value="signup">
			<fieldset class="start-step">
				<legend><span class="step-num">1</span> Choose your plan</legend>
				<div class="plan-picks">{cards}</div>
				<p class="small muted">Prices exclude VAT. <a href="pricing.html">Compare everything in each plan</a>.</p>
			</fieldset>

			<fieldset class="start-step">
				<legend><span class="step-num">2</span> About your shop</legend>
				<div class="form-grid">
					<div class="field">
						<label for="s-name">Your name <span class="req" aria-hidden="true">*</span></label>
						<input id="s-name" name="name" type="text" autocomplete="name" required>
					</div>
					<div class="field">
						<label for="s-email">Email <span class="req" aria-hidden="true">*</span></label>
						<input id="s-email" name="email" type="email" autocomplete="email" required>
					</div>
					<div class="field full">
						<label for="s-business">Business name <span class="req" aria-hidden="true">*</span></label>
						<input id="s-business" name="business" type="text" autocomplete="organization" required>
					</div>
					<div class="field">
						<label for="s-supplier">Supplier name on your RNPS registration <span class="req" aria-hidden="true">*</span></label>
						<input id="s-supplier" name="supplier" type="text" required aria-describedby="s-supplier-hint">
						<span class="hint" id="s-supplier-hint">Printed in the footer of every plate.</span>
					</div>
					<div class="field">
						<label for="s-postcode">Supplier postcode <span class="req" aria-hidden="true">*</span></label>
						<input id="s-postcode" name="postcode" type="text" autocomplete="postal-code" autocapitalize="characters" required>
					</div>
					<div class="field">
						<label for="s-website">Your current website</label>
						<input id="s-website" name="website" type="url" inputmode="url" placeholder="https://" aria-describedby="s-website-hint">
						<span class="hint" id="s-website-hint">We'll copy your products and prices from it.</span>
					</div>
					<div class="field">
						<label for="s-domain">Domain for your new shop</label>
						<input id="s-domain" name="domain" type="text" placeholder="yourplates.co.uk" aria-describedby="s-domain-hint">
						<span class="hint" id="s-domain-hint">Leave it blank if you need one; we'll help.</span>
					</div>
					<div class="field full">
						<label for="s-colour">Main brand colour</label>
						<span class="colour-row"><input id="s-colour" name="colour" type="color" value="#f7d333"><span class="hint">Your logo and the rest come later, in a short form.</span></span>
					</div>
					<div class="field full">
						<label for="s-message">Anything we should know?</label>
						<textarea id="s-message" name="message" rows="4" placeholder="The plates you make, collection and fitting, anything you'd like on your site."></textarea>
					</div>
					<div class="hp" aria-hidden="true">
						<label for="s-company-url">Leave this empty</label>
						<input id="s-company-url" name="company_url" type="text" tabindex="-1" autocomplete="off">
					</div>
					<div class="field full checks">
						<label class="check"><input type="checkbox" name="rnps" value="yes" required> <span>I'm a Registered Number Plate Supplier, and I'll upload my RNPS registration and ID when asked. <span class="req" aria-hidden="true">*</span></span></label>
						<label class="check"><input type="checkbox" name="terms" value="yes" required> <span>I've read the <a href="privacy.html">privacy notice</a>, and I understand the <a href="terms.html">full terms</a> come with my payment link, to agree to before I pay. <span class="req" aria-hidden="true">*</span></span></label>
					</div>
				</div>
			</fieldset>

			<div class="start-submit">
				<button type="submit" class="btn btn-primary btn-lg">Send my sign-up</button>
				<p class="small muted">Nothing is charged now. Fields marked * are required.</p>
				<p class="form-status" data-form-status role="status" aria-live="polite"></p>
			</div>
		</form>

		<aside class="start-next" aria-labelledby="next-title">
			<h2 id="next-title"><span class="step-num">3</span> What happens next</h2>
			<ol>
				<li><b>Your secure link, within [RESPONSE TIME]</b><span>We email you a link to pay the set-up fee.</span></li>
				<li><b>We verify you</b><span>Upload your RNPS registration and ID through the same link. We check them before we build: it protects you, your customers and us.</span></li>
				<li><b>We build your shop</b><span>Your products, prices, colours and supplier details go in. You get a private preview to review and ask for changes.</span></li>
				<li><b>You go live</b><span>We connect your Stripe, test real orders and point your domain at the shop. Usually [TIMELINE TOTAL] from sign-up.</span></li>
			</ol>
			<p class="small muted">Your online account for these steps is coming soon. Until then they happen by email, so everything's in writing.</p>
		</aside>
	</div>
</section>
<script>
	// Pre-select the plan from links like start.html?plan=pro.
	(() => {{
		const p = new URLSearchParams(location.search).get('plan');
		const r = p && document.querySelector('.plan-pick input[value="' + p + '"]');
		if (r) r.checked = true;
	}})();
</script>
"""


def contact():
    return """
<section class="page-head page-head-compact" aria-labelledby="c-title">
	<div class="wrap">
		<div class="intro">
			<h1 id="c-title">Ask a question</h1>
			<p>Something not covered on <a href="website.html">What you get</a> or <a href="pricing.html">Pricing</a>? Ask here and we'll reply by email within [RESPONSE TIME]. Ready to go? <a href="start.html">Start your shop</a>.</p>
		</div>
	</div>
</section>

<section class="section-tight" style="padding-top:0">
	<div class="wrap contact-split">
		<form class="form-grid" action="[FORM ENDPOINT]" method="post" data-demo-form data-email="[EMAIL]" data-subject="Question" data-success="Thanks, we've got your question. We'll reply within [RESPONSE TIME]." novalidate>
			<input type="hidden" name="type" value="question">
			<div class="field">
				<label for="f-name">Your name <span class="req" aria-hidden="true">*</span></label>
				<input id="f-name" name="name" type="text" autocomplete="name" required>
			</div>
			<div class="field">
				<label for="f-email">Email <span class="req" aria-hidden="true">*</span></label>
				<input id="f-email" name="email" type="email" autocomplete="email" required>
			</div>
			<div class="field full">
				<label for="f-business">Business name</label>
				<input id="f-business" name="business" type="text" autocomplete="organization">
			</div>
			<div class="field full">
				<label for="f-message">Your question <span class="req" aria-hidden="true">*</span></label>
				<textarea id="f-message" name="message" rows="6" required></textarea>
			</div>
			<div class="hp" aria-hidden="true">
				<label for="f-company-url">Leave this empty</label>
				<input id="f-company-url" name="company_url" type="text" tabindex="-1" autocomplete="off">
			</div>
			<div class="field full">
				<p class="small muted">We'll only use your details to reply. See our <a href="privacy.html">privacy notice</a>.</p>
			</div>
			<div class="field full">
				<button type="submit" class="btn btn-primary">Send question</button>
				<p class="form-status" data-form-status role="status" aria-live="polite"></p>
			</div>
		</form>

		<aside class="contact-card" aria-labelledby="direct-title">
			<h2 id="direct-title" style="font-size:var(--step-2)">Or email us</h2>
			<dl>
				<div><dt>Email</dt><dd><a href="mailto:[EMAIL]">[EMAIL]</a></dd></div>
				<div><dt>Replies</dt><dd>Within [RESPONSE TIME]</dd></div>
			</dl>
			<p class="small muted">We don't do phone calls. Everything is handled by email, so you've always got it in writing.</p>
		</aside>
	</div>
</section>
"""
