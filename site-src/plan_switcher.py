"""The plan switcher: one shop preview (laptop and phone) with a Starter / Pro / Complete toggle.
Each plan adds to the one before, so the same shop grows as you click. Used on Pricing and Home.
site.js switches data-plan on the root; CSS shows what each plan adds. Prices are placeholders,
filled by tools/fill-placeholders.py. The Complete phone is drawn, not a screenshot."""

FEATURES = [
    ('The 3D builder on your shop', 'starter'),
    ('Every style, size, badge and border', 'starter'),
    ('Different front and rear sizes', 'starter'),
    ('A landing page for each of the seven styles', 'pro'),
    ('App-like builder on phones', 'pro'),
    ('Car view', 'pro'),
    ('Checkout inside the builder', 'complete'),
    ('Apple Pay and Google Pay', 'complete'),
    ('Document checks at checkout', 'complete'),
    ('A blog, set up for you', 'complete'),
]
NAMES = {'starter': 'Starter', 'pro': 'Pro', 'complete': 'Complete'}
PLANS = {
    'starter': ('[£ STARTER MONTHLY]', '[£ STARTER SET-UP]', 'one page', '1',
                'A one-page shop with the 3D builder at its heart.'),
    'pro': ('[£ PRO MONTHLY]', '[£ PRO SET-UP]', 'up to [PRO PAGES] pages', '[PRO PAGES]',
            'Everything in Starter, plus a page for every style and an app-like builder on phones.'),
    'complete': ('[£ COMPLETE MONTHLY]', '[£ COMPLETE SET-UP]', 'up to [COMPLETE PAGES] pages', '[COMPLETE PAGES]',
                 'Everything in Pro, plus checkout inside the builder, document checks and a blog.'),
}
TICK = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>'


def per_plan(fn):
    """One span per plan; CSS shows the one for the current plan."""
    return ''.join(f'<span data-only="{p}">{fn(p)}</span>' for p in PLANS)


def plan_switcher(start='pro'):
    tabs = ''.join(
        f'<button type="button" class="ps-tab" data-plan-pick="{p}" aria-pressed="{"true" if p == start else "false"}">{n}</button>'
        for p, n in NAMES.items())
    feats = ''.join(
        f'<li data-lvl="{lvl}"><span class="ps-ic">{TICK}</span><span>{label}</span>'
        f'<span class="ps-tag ps-tag-new">New on {NAMES[lvl]}</span><span class="ps-tag ps-tag-up">On {NAMES[lvl]}</span></li>'
        for label, lvl in FEATURES)
    styles = ''.join(f'<span>{s}</span>' for s in ('Printed', '3D Gel', '4D 3mm', '4D 6mm', '4D Gel 3', '4D Gel 6', 'Retro'))
    squares = ''.join(f'<i data-sq="{i + 1}"></i>' for i in range(30))
    return f'''<div class="ps" data-plan-switcher data-plan="{start}">
	<div class="ps-head">
		<div class="ps-tabs" role="group" aria-label="Choose a plan">{tabs}</div>
		<p class="ps-price" aria-live="polite">{per_plan(lambda p: f"<b>{PLANS[p][0]} a month</b> + {PLANS[p][1]} set-up · <span class='muted'>{PLANS[p][2]}</span>")}</p>
	</div>
	<div class="ps-body">
		<div class="ps-devices">
			<div class="ps-laptop" aria-hidden="true">
				<div class="ps-screen">
					<div class="ps-bar">
						<span class="ps-logo"><span>YP</span>Your Plates Ltd</span>
						<span class="ps-nav" data-only="starter"><span>Design</span><span>How it works</span><span>Find us</span><span class="ps-pill">Basket</span></span>
						<span class="ps-nav" data-from="pro"><span class="ps-cur">Number plates ▾</span><span>Sizes</span><span>Badges</span><span class="ps-pill ps-blog" data-from="complete">Blog</span><span>Help</span><span class="ps-pill">Design your plates</span></span>
					</div>
					<div class="ps-strip">Road-legal plates · Collect today · UK delivery</div>
					<div class="ps-hero">
						<div class="ps-copy">
							<b class="ps-h">{per_plan(lambda p: "Number plates, made while you wait" if p == "starter" else "See your plates before you buy")}</b>
							<span class="ps-sub">{per_plan(lambda p: {"starter": "Design yours below, then pay and collect.", "pro": "Pick a style, see it on a car, collect today.", "complete": "Design, pay and send your documents, all in one place."}[p])}</span>
							<span class="ps-styles" data-from="pro">{styles}</span>
						</div>
						<div class="ps-shot">
							<img data-only="starter" src="assets/img/plans/shop-builder.webp" width="900" height="348" alt="" loading="lazy">
							<img data-from="pro" src="assets/img/plans/shop-plate.webp" width="700" height="636" alt="" loading="lazy">
						</div>
						<span class="ps-car" data-from="pro"><img src="assets/img/plans/car-view.webp" width="240" height="173" alt="" loading="lazy">See it on a car</span>
					</div>
				</div>
			</div>
			<div class="ps-base" aria-hidden="true"></div>
			<div class="ps-phone" aria-hidden="true">
				<div class="ps-phone-screen">
					<img data-only="starter" src="assets/img/plans/phone-starter.webp" width="420" height="913" alt="" loading="lazy">
					<img data-only="pro" src="assets/img/plans/phone-pro.webp" width="420" height="913" alt="" loading="lazy">
					<div class="ps-pay" data-only="complete">
						<div class="ps-pay-top">Your Plates Ltd</div>
						<div class="ps-pay-body">
							<small>LAST STEP</small>
							<b>Pay and we'll make them</b>
							<span class="ps-pay-item"><span class="ps-mini">3D BLDR</span>4D Gel 3mm, pair</span>
							<span class="ps-apple">Apple Pay</span>
							<span class="ps-gpay"><b>G</b> Pay</span>
							<span class="ps-docs"><b>Your documents</b><span class="ok">✓ Proof of identity</span><span>○ Proof you can use the reg</span></span>
						</div>
						<div class="ps-pay-foot"><b>Total</b><span>Pay now</span></div>
					</div>
				</div>
			</div>
			<p class="sr-only">{per_plan(lambda p: {"starter": "Preview: a one-page shop with the 3D builder in the page, on a computer and a phone.", "pro": "Preview: the shop gains a Number plates menu with seven style pages, a car view, and an app-like builder on phones.", "complete": "Preview: the shop gains a blog, and on a phone customers pay inside the builder with Apple Pay or Google Pay and upload their documents."}[p])}</p>
		</div>
		<div class="ps-side">
			<p class="ps-lead">{per_plan(lambda p: f"<b>{NAMES[p]} includes</b><br><span class='muted'>{PLANS[p][4]}</span>")}</p>
			<ul class="ps-feats">{feats}</ul>
			<div class="ps-pages">
				<div><b>Pages on your site</b><strong data-page-count>{per_plan(lambda p: PLANS[p][3])}</strong></div>
				<div class="ps-grid" aria-hidden="true">{squares}</div>
			</div>
			<a class="btn btn-primary ps-go" data-plan-link href="https://app.3dplatebuilder.com/signup?plan={start}"><span>Start with <span data-plan-name>{NAMES[start]}</span></span> <span class="arr" aria-hidden="true">→</span></a>
		</div>
	</div>
</div>'''
