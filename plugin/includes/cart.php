<?php
/**
 * WooCommerce integration: each design goes into the basket as the shop product that
 * matches it (4D 3mm, EV 3D Gel, Short…, see pbb_product_presets()), or the hidden
 * "Custom Number Plate" product if none does, and carries its configuration through
 * basket, checkout and the order.
 *
 * Works with both the classic and the block-based basket/checkout.
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fallback product for designs no shop product matches. Created (hidden from the shop) the first time it's needed.
 */
function pbb_get_product_id() {
	$id = (int) get_option( 'pbb_product_id' );
	if ( $id && 'product' === get_post_type( $id ) && 'trash' !== get_post_status( $id ) ) {
		return $id;
	}
	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		return 0;
	}

	$product = new WC_Product_Simple();
	$product->set_name( __( 'Custom Number Plate', '3d-plate-builder' ) );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_regular_price( '0' ); // Real price is set per basket line from the configuration.
	$product->set_sold_individually( false );
	$product->set_reviews_allowed( false );
	$id = $product->save();

	update_option( 'pbb_product_id', $id, false );
	return $id;
}

/**
 * ?wc-ajax=pbb_add_to_cart. Like WooCommerce's own add-to-cart, no nonce:
 * it only adds to the visitor's own basket, and nonces break on cached pages.
 */
add_action( 'wc_ajax_pbb_add_to_cart', 'pbb_ajax_add_to_cart' );

function pbb_ajax_add_to_cart() {
	$raw    = isset( $_POST['config'] ) ? json_decode( wp_unslash( $_POST['config'] ), true ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated by pbb_sanitize_config().
	$config = pbb_sanitize_config( $raw );
	if ( is_wp_error( $config ) ) {
		wp_send_json_error( array( 'message' => $config->get_error_message() ), 400 );
	}

	$product_id = pbb_product_for_config( $config );
	if ( ! $product_id ) {
		wp_send_json_error( array( 'message' => __( 'The shop is not set up for custom plates yet.', '3d-plate-builder' ) ), 500 );
	}

	// Identical configurations stack as quantity; anything different is its own basket line.
	// add_to_cart() skips the add-to-cart validation filters, so Product Add-Ons' required
	// fields don't apply: the builder has already validated the design.
	// Back from the builder's checkout to change the design: replace that basket line, don't add another.
	$replace = isset( $_POST['replace'] ) ? sanitize_key( wp_unslash( $_POST['replace'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $replace && isset( WC()->cart->get_cart()[ $replace ]['pbb'] ) ) {
		WC()->cart->remove_cart_item( $replace );
	}

	$key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), array( 'pbb' => $config ) );
	if ( ! $key ) {
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();
		$message = $notices ? wp_strip_all_tags( $notices[0]['notice'] ) : __( 'Could not add to basket. Please try again.', '3d-plate-builder' );
		wp_send_json_error( array( 'message' => $message ), 400 );
	}

	wp_send_json_success(
		array(
			'key'         => $key,
			'cartUrl'     => wc_get_cart_url(),
			'checkoutUrl' => wc_get_checkout_url(),
			'count'   => WC()->cart->get_cart_contents_count(),
			'price'   => pbb_calculate_price( $config )['total'],
		)
	);
}

/**
 * Always price from the configuration and the current price list, never from the browser.
 * Runs late so Product Add-Ons, which reprices its own lines, can't override it.
 */
add_action(
	'woocommerce_before_calculate_totals',
	function ( $cart ) {
		foreach ( $cart->get_cart() as $item ) {
			if ( ! empty( $item['pbb'] ) ) {
				$item['data']->set_price( pbb_calculate_price( $item['pbb'] )['total'] );
			}
		}
	},
	999
);

/** Show the configuration under the item in basket, checkout and mini-basket. */
add_filter(
	'woocommerce_get_item_data',
	function ( $data, $item ) {
		if ( empty( $item['pbb'] ) ) {
			return $data;
		}
		foreach ( pbb_describe_config( $item['pbb'] ) as $label => $value ) {
			$data[] = array(
				'key'   => $label,
				'value' => $value,
			);
		}
		return $data;
	},
	10,
	2
);

/** Basket line title: "Custom Number Plate – AB12 CDE". */
add_filter(
	'woocommerce_cart_item_name',
	function ( $name, $item ) {
		return empty( $item['pbb'] ) ? $name : $name . ' – ' . esc_html( $item['pbb']['registration'] );
	},
	10,
	2
);

/**
 * Save to the order: readable lines for staff and emails, plus the full
 * configuration as JSON (hidden meta) for production.
 */
add_action(
	'woocommerce_checkout_create_order_line_item',
	function ( $order_item, $cart_item_key, $values ) {
		if ( empty( $values['pbb'] ) ) {
			return;
		}
		foreach ( pbb_describe_config( $values['pbb'] ) as $label => $value ) {
			$order_item->add_meta_data( $label, $value );
		}
		$order_item->add_meta_data( '_pbb_config', wp_json_encode( $values['pbb'] ) );
	},
	10,
	3
);

/*
 * The design's JSON is for production, not for reading: keep it out of the order screen, emails,
 * the REST API and the WooCommerce app, where it would show as a "pbb_config" line.
 */
add_filter(
	'woocommerce_hidden_order_itemmeta',
	function ( $keys ) {
		$keys[] = '_pbb_config';
		return $keys;
	}
);
add_filter(
	'woocommerce_order_item_get_formatted_meta_data',
	function ( $meta ) {
		foreach ( $meta as $id => $m ) {
			if ( isset( $m->key ) && '_pbb_config' === $m->key ) {
				unset( $meta[ $id ] );
			}
		}
		return $meta;
	}
);

/*
 * In-store fitting chosen in the builder: send the customer to checkout with Collect selected and
 * the checkout's own "Fit my plates while I wait" option (which charges the £30) ticked. If they
 * switch to delivery, fitting drops off and a note says why.
 */

/** Does the basket hold a builder design with fitting chosen? */
function pbb_cart_wants_fitting() {
	if ( ! WC()->cart ) {
		return false;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( ! empty( $item['pbb']['fitting'] ) ) {
			return true;
		}
	}
	return false;
}

/** After adding a design with fitting, choose the first Collect (local pickup) rate for the basket. */
add_action(
	'woocommerce_add_to_cart',
	function ( $key, $product_id, $qty, $variation_id, $variation, $data ) {
		if ( empty( $data['pbb']['fitting'] ) || ! WC()->session ) {
			return;
		}
		WC()->cart->calculate_shipping();
		$chosen = array();
		foreach ( WC()->shipping()->get_packages() as $i => $package ) {
			foreach ( $package['rates'] as $rate_id => $rate ) {
				if ( 0 === strpos( $rate->get_method_id(), 'local_pickup' ) ) {
					$chosen[ $i ] = $rate_id;
					break;
				}
			}
		}
		if ( $chosen ) {
			WC()->session->set( 'chosen_shipping_methods', $chosen );
		}
	},
	10,
	6
);

/** Checkout: tick the fitting option once on arrival, and explain if delivery is chosen instead. */
add_action(
	'wp_footer',
	function () {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || ! pbb_cart_wants_fitting() ) {
			return;
		}
		?>
		<script>
		jQuery(function ($) {
			var ticked = false;
			function sync() {
				var collect = $('input[name^="shipping_method"]:checked').val() || '';
				collect = collect.indexOf('local_pickup') === 0;
				var fit = $('#pb_fitting');
				if (collect && fit.length && !ticked) {
					ticked = true;
					if (!fit.prop('checked')) fit.prop('checked', true).trigger('change');
				}
				var note = $('.pbb-fitting-note');
				if (!collect) {
					if (!note.length) {
						$('<p class="pbb-fitting-note"/>').text(<?php echo wp_json_encode( (string) pbb_shop( 'fitting_checkout_note' ) ); ?>)
							.css({ margin: '8px 0 0', color: '#a15c00', fontSize: '13px' })
							.appendTo($('.pb-ship').first().length ? $('.pb-ship').first() : $('#order_review'));
					}
				} else {
					note.remove();
				}
			}
			$(document.body).on('updated_checkout', sync);
			sync();
		});
		</script>
		<?php
	}
);
