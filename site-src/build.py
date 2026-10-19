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
 ('contact.html','Ask a question | [BRAND NAME]','Ask us a question about 3D Plate Builder. We reply by email.',O.contact(),False),
 ('website.html','What you get | [BRAND NAME]','The plate shop website you get: the pages, how far we customise it, everything it does and the 3D builder at its heart.',M.website(),False),
 ('privacy.html','Privacy notice | [BRAND NAME]','How [BRAND NAME] uses personal information.',O.privacy(),False),
 ('terms.html','Terms of service | [BRAND NAME]','Terms for [BRAND NAME] plans and purchases.',O.terms(),False),
 ('cookies.html','Cookies | [BRAND NAME]','Cookies used on [DOMAIN].',O.cookies(),False),
]
for f,t,d,b,bl in P:
    open(f'{OUT}/{f}','w').write(page(f,t,d,b,builder=bl))
# Old start.html links now go to sign-up in the customer portal, keeping ?plan=.
_go = 'https://app.3dplatebuilder.com/signup'
_start = ('<section class="page-head"><div class="wrap"><div class="intro"><h1>Start your shop</h1><p>Sign-up has moved to your account area. <a id="go" href="' + _go + '">Continue to sign up</a>.</p></div></div></section>\n'
  "<script>(function(){var p=new URLSearchParams(location.search).get('plan');var u='" + _go + "'+(p&&/^(starter|pro|complete)$/.test(p)?'?plan='+p:'');document.getElementById('go').href=u;location.replace(u);})();</script>")
open(f'{OUT}/start.html','w').write(page('start.html','Start your shop | [BRAND NAME]','Sign up for a number plate shop website with the 3D plate builder built in.',_start,extra_head=f'<meta name="robots" content="noindex"><noscript><meta http-equiv="refresh" content="0;url={_go}"></noscript>'))
nf = '''<section class="page-head"><div class="wrap"><div class="intro"><h1>Page not found</h1><p>That page isn't here. Try the <a href="index.html">home page</a> or the <a href="demo.html">live demo</a>.</p></div></div></section>'''
open(f'{OUT}/404.html','w').write(page('404.html','Page not found | [BRAND NAME]','Page not found.',nf).replace('href="index.html"','href="/"'))
import re as _re
t=open(f'{OUT}/404.html').read()
t=_re.sub(r'(href|src)="(?!https?:|mailto:|tel:|#|/|\[)', r'\1="/', t)
open(f'{OUT}/404.html','w').write(t)
bm = builder_markup(mobile='app', url_options=True, reg='3D BLDR', exit_href='demo.html')
start = bm.index('<div class="pbb-builder"'); end = bm.index('\n\t\t<p class="demo-failed">')
open(f'{OUT}/phone.html','w').write(phone_page(bm[start:end]))
dm = builder_markup(mobile='inline', url_options=True, reg='3D BLDR', exit_href='demo.html')
ds = dm.index('<div class="pbb-builder"'); de = dm.index('\n\t\t<p class="demo-failed">')
open(f'{OUT}/desktop.html','w').write(desktop_page(dm[ds:de]))
open(f'{OUT}/shop-home.html','w').write(shop_home_page())
# Cache busting: stamp the site's own CSS and JS with a hash of their contents, so browsers
# fetch fresh copies whenever they change (the server lets them cache otherwise).
import hashlib, glob
h = hashlib.sha1()
for f in sorted(glob.glob(f'{OUT}/assets/css/*.css') + glob.glob(f'{OUT}/assets/js/*.js') + glob.glob(f'{OUT}/builder/assets/**/*.*', recursive=True)):
    h.update(open(f, 'rb').read())
V = h.hexdigest()[:8]
# Images get their own hash, so only a re-rendered picture is fetched again.
import re, os
IMG = re.compile(r'((?:demo-)?assets/img/[\w./-]+\.(?:webp|jpg|jpeg|png|svg))(?=["\s,])')
_img_hash = {}
def img_v(m):
    p = m.group(1)
    if p not in _img_hash:
        path = f'{OUT}/{p}'
        _img_hash[p] = hashlib.sha1(open(path, 'rb').read()).hexdigest()[:8] if os.path.exists(path) else None
    return f'{p}?v={_img_hash[p]}' if _img_hash[p] else p
for f in glob.glob(f'{OUT}/*.html'):
    t = open(f).read()
    t = t.replace('assets/css/site.css"', f'assets/css/site.css?v={V}"').replace('assets/js/site.js"', f'assets/js/site.js?v={V}"').replace('assets/js/demo.js"', f'assets/js/demo.js?v={V}"')
    t = t.replace('data-src="phone.html"', f'data-src="phone.html?v={V}"').replace('data-src="desktop.html"', f'data-src="desktop.html?v={V}"')
    t = t.replace('builder/assets/css/builder.css?v=1.10.2"', f'builder/assets/css/builder.css?v={V}"')
    t = IMG.sub(img_v, t)
    open(f, 'w').write(t)
print('ok', V)
