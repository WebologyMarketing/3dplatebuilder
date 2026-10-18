import os, sys
sys.path.insert(0, os.path.dirname(__file__))
from partials import page, phone_page, desktop_page, builder_markup
import pages_main as M, pages_other as O
from shop_demo import shop_home_page
import pathlib
OUT = str(pathlib.Path(__file__).resolve().parent.parent / 'site')
FONT = OUT + '/builder/assets/fonts/charles-wright-bold.json'
P = [
 ('index.html','[BRAND NAME]: 3D number plate builder shops','A complete number plate shop with a real-time 3D plate builder built in, for UK registered number plate suppliers.',M.home(FONT),False),
 ('demo.html','Live demo | [BRAND NAME]','Try the full 3D number plate builder, set up for a demo supplier.',M.demo(),False),
 ('features.html','Features | [BRAND NAME]','Everything the 3D plate builder does: legal spacing, 4D and gel styles, badges, phone app layout, checkout and document checks.',M.features(),False),
 ('pricing.html','Pricing | [BRAND NAME]','Monthly plans with a set-up fee, or buy your plate shop outright.',O.pricing(),False),
 ('setup.html','How we set you up | [BRAND NAME]','How we build your plate shop: products and prices, supplier details, Stripe, test orders and go live.',O.setup(),False),
 ('contact.html','Request a demo | [BRAND NAME]','Request a demo of the 3D number plate builder, set up with your own plates and prices.',O.contact(),False),
 ('privacy.html','Privacy notice | [BRAND NAME]','How [BRAND NAME] uses personal information.',O.privacy(),False),
 ('terms.html','Terms of service | [BRAND NAME]','Terms for [BRAND NAME] plans and purchases.',O.terms(),False),
 ('cookies.html','Cookies | [BRAND NAME]','Cookies used on [DOMAIN].',O.cookies(),False),
]
for f,t,d,b,bl in P:
    open(f'{OUT}/{f}','w').write(page(f,t,d,b,builder=bl))
nf = '''<section class="page-head"><div class="wrap"><div class="intro"><h1>Page not found</h1><p>That page isn't here. Try the <a href="index.html">home page</a> or the <a href="demo.html">live demo</a>.</p></div></div></section>'''
open(f'{OUT}/404.html','w').write(page('404.html','Page not found | [BRAND NAME]','Page not found.',nf).replace('href="index.html"','href="/"'))
import re as _re
t=open(f'{OUT}/404.html').read()
t=_re.sub(r'(href|src)="(?!https?:|mailto:|tel:|#|/|\[)', r'\1="/', t)
open(f'{OUT}/404.html','w').write(t)
bm = builder_markup(mobile='app', url_options=True, reg='AB12 CDE', exit_href='demo.html')
start = bm.index('<div class="pbb-builder"'); end = bm.index('\n\t\t<p class="demo-failed">')
open(f'{OUT}/phone.html','w').write(phone_page(bm[start:end]))
dm = builder_markup(mobile='inline', url_options=True, reg='AB12 CDE', exit_href='demo.html')
ds = dm.index('<div class="pbb-builder"'); de = dm.index('\n\t\t<p class="demo-failed">')
open(f'{OUT}/desktop.html','w').write(desktop_page(dm[ds:de]))
open(f'{OUT}/shop-home.html','w').write(shop_home_page())
print('ok')
