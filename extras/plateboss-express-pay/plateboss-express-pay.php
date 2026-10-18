<?php
/**
 * Plugin Name: PlateBoss Express Pay Placement
 * Description: Shows the Stripe Apple Pay / Google Pay buttons below "Proceed to checkout" on the basket and below "Place order" on checkout, with an "or" divider above them.
 * Version:     1.0.0
 * Author:      PlateBoss
 * Requires Plugins: woocommerce
 *
 * @package PlateBoss
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stripe's express checkout element (WooCommerce Stripe Gateway 8+), found through the hook it
 * registers on the checkout page, so we don't depend on its internal properties.
 */
function pbx_stripe_element() {
	global $wp_filter;
	static $found = null;
	if ( null !== $found ) {
		return $found ?: null;
	}
	$found = false;
	if ( ! class_exists( 'WC_Stripe_Express_Checkout_Element' ) || empty( $wp_filter['woocommerce_proceed_to_checkout'] ) ) {
		return null;
	}
	foreach ( $wp_filter['woocommerce_proceed_to_checkout']->callbacks as $callbacks ) {
		foreach ( $callbacks as $cb ) {
			if ( is_array( $cb['function'] ) && $cb['function'][0] instanceof WC_Stripe_Express_Checkout_Element ) {
				$found = $cb['function'][0];
				return $found;
			}
		}
	}
	return null;
}

/** The buttons, with the divider above them (Stripe shows both once a wallet is available). */
function pbx_render( $element ) {
	ob_start();
	$element->display_express_checkout_button_html();
	$html = ob_get_clean();
	if ( false === strpos( $html, 'wc-stripe-express-checkout-element' ) ) {
		return; // Stripe decided not to show the buttons here.
	}
	// Stripe puts its own divider after the buttons on checkout; ours goes above them instead.
	$html = preg_replace( '#<p id="wc-stripe-express-checkout-button-separator".*?</p>#s', '', $html );
	?>
	<div class="pbx-express">
		<p id="wc-stripe-express-checkout-button-separator" class="pbx-or" style="display:none;"><span><?php esc_html_e( 'or pay with', 'plateboss-express-pay' ); ?></span></p>
		<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Stripe's own markup. ?>
	</div>
	<?php
}

add_action(
	'wp',
	function () {
		$element = pbx_stripe_element();
		if ( ! $element || ! function_exists( 'is_cart' ) ) {
			return;
		}

		// Basket: after the "Proceed to checkout" button (WooCommerce prints it at priority 20).
		remove_action( 'woocommerce_proceed_to_checkout', array( $element, 'display_express_checkout_button_html' ), 20 );
		add_action( 'woocommerce_proceed_to_checkout', fn () => pbx_render( $element ), 30 );

		// Checkout: not at the top of the form, but under the payment box that ends with "Place order".
		remove_action( 'woocommerce_checkout_before_customer_details', array( $element, 'display_express_checkout_button_html' ), 1 );
		if ( is_checkout() && ! is_wc_endpoint_url() ) {
			add_action( 'wp_footer', fn () => pbx_checkout_footer( $element ), 5 );
		}
	}
);

/**
 * Checkout: print the buttons in the footer and move them under #payment before Stripe mounts them
 * (its scripts print later in the footer). #payment is swapped out on every checkout refresh;
 * the space after it isn't, so the mounted buttons survive.
 */
function pbx_checkout_footer( $element ) {
	?>
	<div id="pbx-express-holder" hidden><?php pbx_render( $element ); ?></div>
	<script>
	(function () {
		var holder = document.getElementById('pbx-express-holder');
		var box = holder && holder.querySelector('.pbx-express');
		var payment = document.getElementById('payment');
		if (box && payment) payment.after(box);
		else if (box) holder.hidden = false; // No payment box (unusual template): leave them at the foot of the page.
		if (holder && !holder.children.length) holder.remove();
	})();
	</script>
	<?php
}

add_action(
	'wp_head',
	function () {
		if ( ! function_exists( 'is_cart' ) || ! ( is_cart() || is_checkout() ) ) {
			return;
		}
		?>
		<style>
			.pbx-express{clear:both;margin-top:4px}
			.pbx-express #wc-stripe-express-checkout-element{margin-top:10px!important}
			.pbx-or{display:flex!important;align-items:center;gap:12px;margin:16px 0 0!important;color:#6b7280;font-size:13px;text-transform:uppercase;letter-spacing:.06em}
			.pbx-or::before,.pbx-or::after{content:"";flex:1;height:1px;background:#e5e7eb}
			.pbx-or[style*="display: none"],.pbx-or[style*="display:none"]{display:none!important}
			.checkout-page .pbx-express{padding:0 0 4px}
		</style>
		<?php
	}
);
