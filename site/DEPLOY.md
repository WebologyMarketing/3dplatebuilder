# Putting 3D Plate Builder live

The site is plain HTML, CSS and JavaScript, so any host works. The plan is **domain at Fasthosts, site on your own Plesk server**; that's the first section below. The other hosts are kept for reference.

The one piece of server code is `contact.php`, which emails demo requests to `hello@3dplatebuilder.com`. It's optional: without it, the form falls back to opening the visitor's email app.

**Before you upload**

1. Fill in `placeholders.json`, then run `python3 tools/fill-placeholders.py` from this folder. Add `--final` once your prices are set: that also removes the dashed "Suggested" notes. Keep an unfilled copy of the folder first, because the script edits files in place.
2. Set `https://3dplatebuilder.com/contact.php` to `https://3dplatebuilder.com/contact.php` (see "The Request a demo form" below).
3. Upload **the contents of this folder** (where `index.html` sits), not the folder above it.
4. Don't upload `placeholders.json`, `tools/`, `DEPLOY.md` or `LAUNCH-NOTES.md`. They're harmless if you do, but nobody needs to see them.

The 3D builder files in `builder/` must keep their folder structure, because they load each other by relative path. The builder loads Three.js r170 from jsDelivr, so visitors need an internet connection, as they would for any website.

## Fasthosts domain, your Plesk server

**1. Add the site in Plesk**

1. In Plesk, go to **Websites & Domains → Add Domain** and enter `3dplatebuilder.com`. Choose "Website hosting".
2. Open the new domain's **Files** (File Manager), go into `httpdocs`, and delete Plesk's default `index.html` placeholder.
3. Upload a zip of this folder's contents and **Extract** it there. Check that `index.html` sits directly in `httpdocs` and that the hidden `.htaccess` came across.
4. Note the server's IP address: **Websites & Domains → 3dplatebuilder.com → Hosting & DNS → Hosting**, or the IP shown on the Plesk home screen.

**2. Point the domain at your server (Fasthosts control panel)**

1. Log in to Fasthosts, open **Domains**, choose `3dplatebuilder.com` and go to **DNS settings** (Manage DNS).
2. Keep Fasthosts' own nameservers. Edit the records so that:
   - the `A` record for `@` (the bare domain) points to your server's IP;
   - `www` is either an `A` record to the same IP or a `CNAME` to `3dplatebuilder.com`.
3. Delete any other `A` or `AAAA` records for `@` or `www` that point elsewhere, such as Fasthosts' parking page.
4. Changes usually work within an hour, and can take up to 24 hours. Check with `nslookup 3dplatebuilder.com` or dnschecker.org.

If your Plesk server already runs DNS for your other sites and you'd rather manage it there, change the domain's nameservers at Fasthosts to your server's instead. Only do one or the other.

**3. HTTPS**

Once the domain points at the server, in Plesk go to **3dplatebuilder.com → SSL/TLS Certificates → Install** (Let's Encrypt), and tick both `3dplatebuilder.com` and `www.3dplatebuilder.com`. Then under **Hosting & DNS → Hosting**, switch on "Permanent SEO-safe 301 redirect from HTTP to HTTPS".

**4. Email for the form**

`contact.php` sends from `noreply@3dplatebuilder.com` to `hello@3dplatebuilder.com`.

1. In Plesk, check **3dplatebuilder.com → Mail** is switched on, so the server can send for the domain.
2. To stop requests landing in spam, add the SPF and DKIM records Plesk shows under **Mail Settings / DNS** into Fasthosts' DNS. This matters most if your mailboxes are elsewhere (Fasthosts mail, Google Workspace or Microsoft 365). If they are, merge Plesk's SPF entry into the existing SPF record rather than adding a second one.
3. Send a test request from `/contact.html` and check it arrives.

**nginx note:** Plesk usually runs nginx in front of Apache, so `.htaccess` works. If the domain is set to "nginx only", copy the redirects into **Apache & nginx Settings → Additional nginx directives**. The site works without them.

## Netlify

1. Log in at app.netlify.com and choose **Add new site → Deploy manually**.
2. Drag this folder onto the upload area. Netlify gives you a `something.netlify.app` address straight away.
3. Go to **Domain management → Add a domain** and enter `3dplatebuilder.com`. Then either:
   - use Netlify DNS: change your domain's nameservers, at your registrar, to the four Netlify shows you; or
   - keep your current DNS: add a `CNAME` record for `www` pointing to `something.netlify.app`, and an `A` record for the bare domain pointing to `75.2.60.5`. Check Netlify's screen for the current values.
4. HTTPS switches on by itself once the domain resolves, usually within an hour.

Netlify reads `_headers` for security and caching headers, and serves `404.html` for missing pages. Pretty URLs (`/pricing` instead of `/pricing.html`) are under **Site configuration → Build & deploy → Post processing**.

**Form option:** Netlify Forms can collect the demo form with no other service. Add `data-netlify="true"` and `name="demo"` to the `<form>` in `contact.html`, add `<input type="hidden" name="form-name" value="demo">` inside it, and set `https://3dplatebuilder.com/contact.php` to `https://3dplatebuilder.com/contact.html`.

## Cloudflare Pages

1. In the Cloudflare dashboard go to **Workers & Pages → Create → Pages → Upload assets**.
2. Name the project and upload this folder. You'll get a `project.pages.dev` address.
3. Open **Custom domains → Set up a custom domain** and enter `3dplatebuilder.com`. If the domain's DNS is already on Cloudflare, it adds the record for you. If not, add a `CNAME` at your DNS provider pointing to `project.pages.dev` (for a bare domain, Cloudflare will ask you to move the nameservers to Cloudflare).
4. HTTPS is automatic.

Cloudflare Pages reads `_headers`, serves `404.html` for missing pages, and drops `.html` from URLs by itself.

## cPanel or Plesk hosting

1. Open **File Manager** (cPanel) or **Files** (Plesk) and go to `public_html` (cPanel) or `httpdocs` (Plesk).
2. Upload a zip of this folder's contents and use **Extract**. Check that `index.html` sits directly in `public_html` / `httpdocs`, and that `.htaccess` came across: it's a hidden file, so tick "Show hidden files".
3. Switch on SSL: **SSL/TLS Status → Run AutoSSL** in cPanel, or **SSL/TLS Certificates → Let's Encrypt** in Plesk.
4. `.htaccess` forces HTTPS, serves `404.html`, gives `/pricing` style URLs and sets caching headers. It works on Apache, which most cPanel hosts use. On Plesk servers running nginx only, or Windows/IIS, ask your host to add the same rules (or ignore them: the site works without them).

**Pointing 3dplatebuilder.com at this hosting:** if the domain is registered with the same host, it's usually already set. If it's registered elsewhere, either change its nameservers to your host's, or add an `A` record for `@` and for `www` with the server's IP address (shown in cPanel under **Server information**, or in Plesk under **Websites & Domains → Hosting settings**).

## After it's live

- Open `https://3dplatebuilder.com/demo.html` on a computer and a phone. Type a reg, turn the plate, open the car view and press Continue to checkout: you should see the "Here's the order your shop would receive" panel.
- Send a test demo request from `contact.html` and check it arrives.
- Submit `https://3dplatebuilder.com/sitemap.xml` in Google Search Console.

## The Request a demo form

The form posts to `https://3dplatebuilder.com/contact.php` with a normal form post (fields: `name`, `business`, `email`, `website`, `platform`, `orders`, `plan`, `message`, plus a hidden `company_url` spam trap that should be empty). On your Plesk server, that's `contact.php` in this folder, which checks the fields and emails them to `hello@3dplatebuilder.com`, with Reply-To set to the visitor. Any form service that accepts a POST and returns a 2xx status works instead, such as Formspree or Basin.

Until `https://3dplatebuilder.com/contact.php` is a real `https://` address, or if a post fails, the form opens the visitor's email app with their answers filled in, addressed to `hello@3dplatebuilder.com`.

## Fonts and privacy

The site loads its typeface, Archivo, from Google Fonts. To avoid sending visitors' IP addresses to Google, download Archivo (it's free under the SIL Open Font License), put the files in `assets/fonts/`, replace the three Google Fonts `<link>` lines in each page with your own `@font-face` rule, and remove the Google Fonts line from the privacy and cookie pages.

For Three.js, the builder kit's README suggests copying the three files into `builder/assets/vendor/three/` for production. On this static site, that means changing the import map in `index.html` and `demo.html` to point at the local copies. Use exactly version 0.170.0.
