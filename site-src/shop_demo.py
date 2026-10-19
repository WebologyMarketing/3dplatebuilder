"""shop-home.html: the home page of the demo plate shop, Your Plates Ltd.

It shows what a [BRAND NAME] website looks like (the shop, not just the builder), and it's
where the screenshots on the marketing pages come from (tools/shots.js). Not linked from
the menus; noindex. Everything on it is clearly a demo: no reviews, no real business.
"""

CATS = [
    ('style-printed.webp', 'Standard printed', 'From £29.99 a pair', 'The everyday road-legal plate.'),
    ('style-gel-3d.webp', '3D gel', 'From £49.99 a pair', 'Raised resin characters with a glossy dome.'),
    ('style-acrylic-4d-3.webp', '4D 3 mm', 'From £49.99 a pair', 'Laser-cut acrylic characters, sharp edges.'),
    ('style-gel-4d-3.webp', '4D gel', 'From £64.99 a pair', 'Laser-cut characters with a gel top.'),
    ('style-retro.webp', 'Retro pressed', 'From £69.99 a pair', 'The pressed look, chamfered edges.'),
    ('style-acrylic-4d-6.webp', '4D 6 mm', 'From £64.99 a pair', 'Our deepest characters, for show.'),
]


def shop_home_page():
    cards = ''.join(f'''<a class="cat" href="#"><img src="assets/img/{img}" alt="" width="640" height="480"><span class="cat-body"><b>{name}</b><span>{blurb}</span><em>{price}</em></span></a>''' for img, name, price, blurb in CATS)
    return f'''<!doctype html>
<html lang="en-GB">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your Plates Ltd (demo shop) | [BRAND NAME]</title>
<meta name="robots" content="noindex">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..100,400..900&amp;display=swap">
<style>
	:root {{ --ink: #111418; --yellow: #F7D333; --muted: #5b6168; --line: #e5e7ea; --soft: #f4f5f6; }}
	* {{ box-sizing: border-box; }}
	body {{ margin: 0; font-family: "Archivo", Arial, sans-serif; color: var(--ink); background: #fff; line-height: 1.5; }}
	a {{ color: inherit; }}
	.in {{ max-width: 1240px; margin: 0 auto; padding: 0 40px; }}
	.top {{ background: var(--ink); color: #fff; }}
	.top .in {{ display: flex; align-items: center; justify-content: space-between; height: 78px; }}
	.top img {{ height: 36px; width: auto; display: block; }}
	.top nav {{ display: flex; gap: 30px; font-weight: 650; font-size: 15px; }}
	.top nav a {{ text-decoration: none; opacity: .85; }}
	.basket {{ display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 999px; background: var(--yellow); color: var(--ink); font-weight: 800; text-decoration: none; font-size: 14px; }}
	.strip {{ background: var(--yellow); font-weight: 750; font-size: 14px; }}
	.strip .in {{ display: flex; justify-content: center; gap: 36px; padding-block: 9px; }}
	.hero {{ background: radial-gradient(70% 80% at 72% 40%, #3a3f46 0%, #16181b 70%); color: #fff; }}
	.hero .in {{ display: grid; grid-template-columns: 1fr 1.05fr; align-items: center; gap: 40px; padding-block: 64px 72px; }}
	.hero h1 {{ margin: 0 0 16px; font-size: 60px; line-height: .95; font-weight: 900; font-variation-settings: "wdth" 70; text-transform: uppercase; letter-spacing: -.01em; }}
	.hero p {{ margin: 0 0 26px; font-size: 19px; color: #c9ccd1; max-width: 30em; }}
	.reg {{ display: flex; gap: 10px; max-width: 520px; }}
	.reg span {{ flex: 1; display: flex; align-items: center; height: 60px; padding: 0 18px; border-radius: 10px; background: var(--yellow); color: var(--ink); font-weight: 900; font-size: 28px; letter-spacing: .06em; font-variation-settings: "wdth" 80; }}
	.reg a {{ display: inline-flex; align-items: center; padding: 0 22px; border-radius: 10px; background: #fff; color: var(--ink); font-weight: 800; text-decoration: none; white-space: nowrap; }}
	.hero small {{ display: block; margin-top: 14px; color: #a9adb3; font-size: 14px; }}
	.hero figure {{ margin: 0; position: relative; }}
	.hero figure img {{ display: block; width: 100%; height: auto; filter: drop-shadow(0 30px 30px rgba(0,0,0,.5)); }}
	.hero figure .front {{ width: 66%; margin: -5% 0 0 auto; }}
	.usp {{ border-bottom: 1px solid var(--line); }}
	.usp .in {{ display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; padding-block: 22px; font-size: 15px; }}
	.usp b {{ display: block; font-size: 16px; }}
	.usp span {{ color: var(--muted); }}
	section.sec {{ padding-block: 64px; }}
	h2 {{ margin: 0 0 8px; font-size: 38px; line-height: 1; font-weight: 900; font-variation-settings: "wdth" 72; text-transform: uppercase; }}
	.sub {{ margin: 0 0 28px; color: var(--muted); font-size: 17px; }}
	.cats {{ display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }}
	.cat {{ display: block; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; text-decoration: none; background: #fff; }}
	.cat img {{ display: block; width: 100%; height: 190px; object-fit: cover; }}
	.cat-body {{ display: grid; gap: 2px; padding: 16px 18px 18px; }}
	.cat-body b {{ font-size: 19px; }}
	.cat-body span {{ color: var(--muted); font-size: 15px; }}
	.cat-body em {{ font-style: normal; font-weight: 800; margin-top: 6px; }}
	.car {{ background: var(--soft); }}
	.car .in {{ display: grid; grid-template-columns: 1.1fr 1fr; gap: 48px; align-items: center; }}
	.car img {{ display: block; width: 100%; height: auto; border-radius: 14px; }}
	.btn {{ display: inline-flex; align-items: center; padding: 14px 22px; border-radius: 10px; background: var(--ink); color: #fff; font-weight: 800; text-decoration: none; }}
	.steps {{ display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; margin: 0; padding: 0; list-style: none; counter-reset: s; }}
	.steps li {{ padding: 22px; border-radius: 14px; border: 1px solid var(--line); counter-increment: s; }}
	.steps li::before {{ content: counter(s); display: grid; place-items: center; width: 36px; height: 36px; margin-bottom: 12px; border-radius: 8px; background: var(--yellow); font-weight: 900; }}
	.steps b {{ display: block; font-size: 18px; margin-bottom: 4px; }}
	.steps span {{ color: var(--muted); }}
	.docs {{ margin-top: 18px; color: var(--muted); font-size: 14px; }}
	.foot {{ background: var(--ink); color: #a9adb3; font-size: 14px; }}
	.foot .in {{ display: flex; justify-content: space-between; padding-block: 28px; }}
</style>
</head>
<body>
<header class="top"><div class="in">
	<img src="demo-assets/img/your-plates-logo.svg" alt="Your Plates Ltd" width="220" height="40">
	<nav aria-label="Demo shop"><a href="#">Number plates</a><a href="#">Design in 3D</a><a href="#">Motorbike plates</a><a href="#">Fitting</a><a href="#">Help</a></nav>
	<a class="basket" href="#">Basket · £0.00</a>
</div></header>
<div class="strip"><div class="in"><span>Road-legal plates to BS AU 145e</span><span>Collect today in Demotown</span><span>Free UK delivery over £40</span></div></div>
<section class="hero"><div class="in">
	<div>
		<h1>Number plates, made while you wait</h1>
		<p>Design yours in 3D, see them on a car, pay with Apple Pay and collect today or have them posted.</p>
		<div class="reg"><span>3D BLDR</span><a href="#">Design in 3D</a></div>
		<small>Road-legal plates need proof of ID and your V5C. Upload them at checkout.</small>
	</div>
	<figure>
		<img src="assets/img/hero-rear.webp" alt="" width="881" height="500">
		<img class="front" src="assets/img/hero-front.webp" alt="" width="1112" height="318">
	</figure>
</div></section>
<div class="usp"><div class="in">
	<div><b>Made in Demotown</b><span>Pressed and printed in our workshop</span></div>
	<div><b>Fitted while you wait</b><span>Book fitting when you order</span></div>
	<div><b>Apple Pay and Google Pay</b><span>Or Klarna, pay in three</span></div>
	<div><b>Show plates too</b><span>Clearly marked, not for road use</span></div>
</div></div>
<section class="sec"><div class="in">
	<h2>Choose your style</h2>
	<p class="sub">Every style opens in the 3D builder, so you see exactly what you're buying.</p>
	<div class="cats">{cards}</div>
</div></section>
<section class="sec car"><div class="in">
	<img src="assets/img/car.webp" alt="" width="1280" height="720">
	<div>
		<h2>See them on your car</h2>
		<p class="sub">Turn the plate, zoom into the 4D edge, then put it on a car and pick the paint colour before you buy.</p>
		<a class="btn" href="#">Start designing</a>
	</div>
</div></section>
<section class="sec"><div class="in">
	<h2>How it works</h2>
	<p class="sub">Three steps, on your phone or computer.</p>
	<ol class="steps">
		<li><b>Design</b><span>Type your reg. We space it legally and show it in 3D.</span></li>
		<li><b>Pay</b><span>Card, Apple Pay, Google Pay or Klarna. Upload your documents.</span></li>
		<li><b>Collect or delivery</b><span>Collect today and we fit them, or we post them to you.</span></li>
	</ol>
	<p class="docs">By law, we must see proof of ID and entitlement to the registration before we make road-legal plates.</p>
</div></section>
<footer class="foot"><div class="in"><span>Your Plates Ltd · 1 Demo Street, Demotown AB1 2CD</span><span>A demo shop built by [BRAND NAME]</span></div></footer>
</body>
</html>
'''
