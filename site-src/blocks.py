"""Blocks shared by the home page and What you get: the package USPs, the rendering showcase
and the quick process. Copy in UK English; nothing here may promise what isn't built."""

# The five reasons to buy the package. Plan limits are said where they apply.
USPS = [
    ('Photo-real 3D, rendered live',
     'Real reflections, true 3 mm and 6 mm depth, glossy gel domes and pressed retro edges, in Charles Wright characters. It renders as customers type, on a phone or a computer, with nothing to install.'),
    ('A complete plate shop, built for you',
     'We design the website in your brand and set up every page. You set your prices and products in your account, then publish. You never need to touch WordPress.'),
    ('Legal layout on every plate',
     'BS AU 145e spacing as they type, your supplier name and postcode in the footer, and road-legal or show-plate status on every order. Document checks at checkout on Complete.'),
    ('Built to sell on phones',
     'The plate stays in view, one step at a time, with the price always showing. On Pro and Complete the builder fills the screen like an app; on Complete customers pay with Apple Pay or Google Pay without leaving it.'),
    ('Hosted, updated and looked after',
     'Hosting, security certificate, daily backups and updates are included. Support is by email, and you can be live in [TIMELINE TOTAL] without a single call.'),
]


def usp_grid(cls=''):
    items = ''.join(f'<li><span class="usp-num">{i}</span><h3>{h}</h3><p>{t}</p></li>' for i, (h, t) in enumerate(USPS, 1))
    return f'<ol class="usps {cls}">{items}</ol>'


DETAILS = [
    ('detail-4d-gel.webp', '4D gel', 'Laser-cut characters with a clear gel top that catches the light.'),
    ('detail-gel-3d.webp', '3D gel', 'Domed resin characters, glossy from every angle.'),
    ('detail-retro.webp', 'Retro pressed', 'Chamfered, pressed edges, like a classic plate.'),
    ('detail-4d-acrylic.webp', '4D 6 mm with border', 'Deep acrylic characters and a 2 mm black border.'),
]


def render_showcase(heading_id='render-title', compact=False):
    details = ''.join(f'<li><figure><img src="assets/img/{img}" width="800" height="600" alt="Close-up of {name} characters, rendered by the builder" loading="lazy" decoding="async"><figcaption><b>{name}</b> {cap}</figcaption></figure></li>' for img, name, cap in DETAILS)
    points = '' if compact else '''<ul class="render-points">
				<li><b>Real-time</b><span>Rendered live in the browser as customers type and turn the plate. No app, no plug-in.</span></li>
				<li><b>Studio lighting</b><span>Reflections and soft shadows, like a plate on a light box in your workshop.</span></li>
				<li><b>True depth</b><span>3 mm and 6 mm characters at their real thickness, so 4D looks like 4D.</span></li>
				<li><b>Turn it, zoom it</b><span>Front, rear and the back of the plate, right up close to the edges, then on a car.</span></li>
			</ul>'''
    return f'''<section class="section render" aria-labelledby="{heading_id}">
	<div class="wrap">
		<div class="render-head">
			<div class="stack">
				<p class="eyebrow">The rendering</p>
				<h2 id="{heading_id}">Rendered like the real plate</h2>
				<p class="lede muted">Customers see the finish they're paying for: the gloss on a gel dome, the depth of a 4D character, the edge of a pressed retro plate. Every picture on this page is the builder itself, not a photo.</p>
				<div class="actions"><a class="btn btn-primary" href="demo.html">Turn one yourself</a></div>
			</div>
			<figure class="render-hero"><img src="assets/img/render-4d-gel.webp" width="1600" height="1000" alt="A yellow rear plate, 3D BLDR, in 4D gel characters, rendered by the builder" loading="lazy" decoding="async"></figure>
		</div>
		<ul class="render-details">{details}</ul>
		{points}
	</div>
</section>'''


PROCESS = [
    ('Sign up and pay', 'Pick a plan and pay the set-up fee and your first month online. About five minutes.', 'Today'),
    ('Add your details and prices', 'In your account: logo, colours and RNPS registration. Tick what you sell, set your prices and publish.', 'In your own time'),
    ('We build, you check', 'We build your shop in your brand with the prices you published. You try a private preview.', '[TIMELINE BUILD]'),
    ('Go live', 'We connect your Stripe, test real orders and point your domain at your new shop.', '[TIMELINE TOTAL] in all'),
]


def process_strip(heading_id='process-title', heading='Live in [TIMELINE TOTAL], all online'):
    items = ''.join(f'<li><span class="proc-num">{i}</span><h3>{h}</h3><p>{t}</p><p class="proc-when">{w}</p></li>' for i, (h, t, w) in enumerate(PROCESS, 1))
    return f'''<section class="section studio" aria-labelledby="{heading_id}">
	<div class="wrap">
		<div class="intro">
			<p class="eyebrow">How it works</p>
			<h2 id="{heading_id}">{heading}</h2>
			<p>No demos to book, no calls, no meetings. Everything happens online and by email, so it's all in writing.</p>
		</div>
		<ol class="process">{items}</ol>
		<div class="actions" style="margin-top:32px"><a class="btn btn-primary btn-lg" href="https://app.3dplatebuilder.com/signup">Start your shop</a><a class="btn btn-ghost btn-lg" href="setup.html">The full set-up, step by step</a></div>
	</div>
</section>'''
