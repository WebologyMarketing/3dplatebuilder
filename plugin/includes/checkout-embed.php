<?php
/**
 * The checkout inside the builder: /checkout/?pbb_embed=1 loads in a frame in the builder
 * (assets/js/checkout.js). This strips the page down to the checkout itself, matches the builder's
 * look, reports its height to the builder, and sends the whole window on to the payment
 * provider or the order-received page when the order is placed.
 *
 * It's the shop's own checkout, so delivery choices, in-store fitting, documents, every payment
 * method (Apple Pay and Google Pay included) and all the checks work exactly as on the full page.
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

function pbb_is_checkout_embed() {
	return ! empty( $_GET['pbb_embed'] ) && function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

add_filter(
	'body_class',
	function ( $classes ) {
		if ( pbb_is_checkout_embed() ) {
			$classes[] = 'pbb-embed';
		}
		return $classes;
	}
);

// The embed is only ever shown inside the builder on this site.
add_action(
	'send_headers',
	function () {
		if ( ! empty( $_GET['pbb_embed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			header( 'X-Frame-Options: SAMEORIGIN' );
		}
	}
);

add_action(
	'wp_head',
	function () {
		if ( ! pbb_is_checkout_embed() ) {
			return;
		}
		?>
		<style id="pbb-embed-css">
			html, body.pbb-embed { background: #fff !important; margin: 0 !important; padding: 0 !important; }
			/* Only the checkout: everything else on the page (header, footer, banners, chat) is hidden. */
			body.pbb-embed > *:not(.pbb-embed-root):not(script):not(style):not(link) { display: none !important; }
			.pbb-embed-root { padding: 14px 14px 28px; max-width: 980px; margin: 0 auto; box-sizing: border-box; }
			.pbb-embed-root .checkout-page, .pbb-embed-root .rtsb-builder-content { padding-top: 0 !important; margin-top: 0 !important; }
			/* Builder look: the shop's brand colours. */
			body.pbb-embed #place_order {
				width: 100%; min-height: 56px; border-radius: 14px; border: 0;
				background: <?php echo esc_html( pbb_shop_colour( 'accent' ) ); ?> !important; color: <?php echo esc_html( pbb_shop_colour( 'accent_ink' ) ); ?> !important;
				font-weight: 800; font-size: 18px; letter-spacing: .04em; text-transform: uppercase;
			}
			body.pbb-embed .pbb-embed-klarna { display: block; margin: 10px 0 0; padding: 12px 14px; border-radius: 12px; background: <?php echo esc_html( pbb_shop_colour( 'dark' ) ); ?>; color: #fff; font-size: 14px; line-height: 1.45; }
			body.pbb-embed .pbb-embed-klarna[hidden] { display: none; }
		</style>
		<?php
	},
	99
);

/**
 * Before the shop's scripts run (they print later in the footer), lift the checkout out of the
 * theme so nothing else on the page shows, whatever the theme's layout.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! pbb_is_checkout_embed() ) {
			return;
		}
		$full = remove_query_arg( 'pbb_embed', wc_get_checkout_url() );
		?>
		<script id="pbb-embed-js">
		(function () {
			var form = document.querySelector('form.checkout');
			if (!form) return;
			var section = form.closest('.checkout-page') || form.closest('.rtsb-builder-content') || form.closest('.woocommerce') || form;
			var rootEl = document.createElement('div');
			rootEl.className = 'pbb-embed-root';
			document.body.appendChild(rootEl);
			rootEl.appendChild(section);

			// Tell the builder how tall the checkout is, so the frame fits it on computers.
			var report = function () {
				if (window.parent !== window) window.parent.postMessage({ pbb: 'checkout-height', height: rootEl.getBoundingClientRect().height }, location.origin);
			};
			if (window.ResizeObserver) new ResizeObserver(report).observe(rootEl);
			window.addEventListener('load', report);
			report();

			// Links (terms, documents list) open in a new tab rather than inside the builder.
			document.addEventListener('click', function (e) {
				var a = e.target.closest && e.target.closest('a[href]');
				if (a && !a.getAttribute('href').startsWith('#') && !a.closest('.woocommerce-checkout-payment')) a.target = '_blank';
			}, true);

			if (!window.jQuery) return;
			var $ = window.jQuery;

			// Order placed: open the next page (order received, or the bank's page) in the whole window.
			// Stripe's own card confirmation (#wc-stripe… in the address) stays here so it can finish.
			$(form).on('checkout_place_order_success', function (e, result) {
				if (!result || !result.redirect || /#wc-stripe|#confirm-/.test(result.redirect)) return;
				window.top.location.href = result.redirect;
				return false;
			});

			// Klarna takes the customer to Klarna's own pages, which can't open inside the builder.
			var fullCheckout = <?php echo wp_json_encode( $full ); ?>;
			var note = document.createElement('p');
			note.className = 'pbb-embed-klarna';
			note.hidden = true;
			note.textContent = 'Klarna opens on its own page: choosing Place order takes you to the full checkout to finish with Klarna.';
			var klarna = function () { return $('input[name="payment_method"]:checked').val() === 'stripe_klarna'; };
			var syncKlarna = function () {
				var btn = document.getElementById('place_order');
				if (btn && !note.isConnected) btn.after(note);
				note.hidden = !klarna();
			};
			$(document.body).on('updated_checkout payment_method_selected', syncKlarna);
			$(form).on('change', 'input[name="payment_method"]', syncKlarna);
			$(form).on('click', '#place_order', function (e) {
				if (!klarna()) return;
				e.preventDefault();
				e.stopImmediatePropagation();
				window.top.location.href = fullCheckout;
			});
		})();
		</script>
		<?php
	},
	1
);

// If an order-received (or any other) page ends up inside the builder's frame, show it full window.
add_action(
	'wp_head',
	function () {
		if ( pbb_is_checkout_embed() || ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		echo '<script>if (window.top !== window.self) { window.top.location.replace(window.location.href); }</script>' . "\n";
	},
	1
);
