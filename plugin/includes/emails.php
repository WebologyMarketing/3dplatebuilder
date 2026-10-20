<?php
/**
 * Customer emails.
 *
 * Every shop: WooCommerce's own emails, in the shop's colours (set by portal-sync.php).
 * Pro and Complete (when the portal sends `emails`):
 *  - the order email (processing / on hold) uses the shop's own heading and wording,
 *    with the order details underneath (templates/emails/customer-order.php);
 *  - a review request goes out once, N days after an order is marked complete.
 *
 * The wording lives in the `pbb_emails` option. {first_name}, {order_number} and {shop_name}
 * are filled in per order.
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

const PBB_REVIEW_HOOK = 'pbb_send_review_request';

/** The shop's email wording from the portal, or null for the standard emails. */
function pbb_emails( $which = null ) {
	$e = get_option( 'pbb_emails', null );
	if ( ! is_array( $e ) ) {
		return null;
	}
	if ( null === $which ) {
		return $e;
	}
	return isset( $e[ $which ] ) && is_array( $e[ $which ] ) ? $e[ $which ] : null;
}

/** Fill in {first_name}, {order_number} and {shop_name}. */
function pbb_email_fill( $text, WC_Order $order ) {
	return strtr(
		(string) $text,
		array(
			'{first_name}'   => $order->get_billing_first_name() ? $order->get_billing_first_name() : __( 'there', '3d-plate-builder' ),
			'{order_number}' => $order->get_order_number(),
			'{shop_name}'    => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		)
	);
}

/** The emails that become the shop's own order email. */
function pbb_order_email_ids() {
	return array( 'customer_processing_order', 'customer_on_hold_order' );
}

/* Order email: the shop's own template when they've written one. */
add_filter(
	'wc_get_template',
	function ( $template, $template_name, $args ) {
		$map = array(
			'emails/customer-processing-order.php'       => 'customer-order.php',
			'emails/customer-on-hold-order.php'          => 'customer-order.php',
			'emails/plain/customer-processing-order.php' => 'plain-customer-order.php',
			'emails/plain/customer-on-hold-order.php'    => 'plain-customer-order.php',
		);
		if ( isset( $map[ $template_name ] ) && pbb_emails( 'order' ) && ! empty( $args['order'] ) ) {
			return PBB_PATH . 'templates/emails/' . $map[ $template_name ];
		}
		return $template;
	},
	10,
	3
);

foreach ( pbb_order_email_ids() as $pbb_email_id ) {
	add_filter(
		'woocommerce_email_heading_' . $pbb_email_id,
		function ( $heading, $order ) {
			$own = pbb_emails( 'order' );
			return $own && $order instanceof WC_Order && '' !== trim( (string) $own['order_heading'] ) ? pbb_email_fill( $own['order_heading'], $order ) : $heading;
		},
		10,
		2
	);
}

/* Review request: scheduled when an order is completed, sent once. */
add_action(
	'woocommerce_order_status_completed',
	function ( $order_id ) {
		$review = pbb_emails( 'review' );
		$order  = wc_get_order( $order_id );
		if ( ! $review || ! $order || $order->get_meta( '_pbb_review_scheduled' ) || ! $order->get_billing_email() || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}
		$days = max( 1, min( 30, (int) ( $review['days'] ?? 3 ) ) );
		as_schedule_single_action( time() + $days * DAY_IN_SECONDS, PBB_REVIEW_HOOK, array( 'order_id' => (int) $order_id ), '3d-plate-builder' );
		$order->update_meta_data( '_pbb_review_scheduled', time() );
		$order->save();
	}
);

add_action( PBB_REVIEW_HOOK, 'pbb_send_review_request' );

/**
 * Send the review request for an order, if the shop still asks for reviews and it hasn't gone already.
 *
 * @return bool True if sent.
 */
function pbb_send_review_request( $order_id ) {
	$review = pbb_emails( 'review' );
	$order  = wc_get_order( (int) $order_id );
	if ( ! $review || empty( $review['url'] ) || ! $order || $order->get_meta( '_pbb_review_sent' ) || ! $order->has_status( 'completed' ) ) {
		return false;
	}

	$mailer  = WC()->mailer();
	$heading = pbb_email_fill( $review['heading'], $order );
	$accent  = pbb_shop_colour( 'accent' );
	$ink     = pbb_shop_colour( 'accent_ink' );
	$date    = $order->get_date_created() ? wc_format_datetime( $order->get_date_created(), 'j F Y' ) : '';

	$body  = '<p style="text-align:center;font-size:16px;line-height:1.6;margin:0 0 24px">' . nl2br( esc_html( pbb_email_fill( $review['message'], $order ) ) ) . '</p>';
	$body .= '<p style="text-align:center;margin:0 0 22px"><a href="' . esc_url( $review['url'] ) . '" style="display:inline-block;background:' . esc_attr( $accent ) . ';color:' . esc_attr( $ink ) . ';text-decoration:none;font-weight:bold;font-size:17px;padding:15px 28px;border-radius:999px">' . esc_html__( 'Leave a review', '3d-plate-builder' ) . '</a></p>';
	$body .= '<p style="text-align:center;font-size:14px;margin:0 0 18px">' . esc_html__( 'Something not right? Reply to this email and we\'ll sort it.', '3d-plate-builder' ) . '</p>';
	/* translators: %s: order date */
	$body .= '<p style="text-align:center;font-size:12px;color:#777;margin:0">' . esc_html( sprintf( __( 'You\'re getting this because you ordered from us on %s. We only ask once.', '3d-plate-builder' ), $date ) ) . '</p>';

	$sent = $mailer->send( $order->get_billing_email(), $heading, $mailer->wrap_message( $heading, $body ), array( 'Content-Type: text/html; charset=UTF-8' ) );
	if ( $sent ) {
		$order->update_meta_data( '_pbb_review_sent', time() );
		$order->add_order_note( __( 'Review request sent.', '3d-plate-builder' ) );
		$order->save();
	}
	return (bool) $sent;
}

/**
 * WooCommerce's email look, from the shop's brand: dark header, PNG logo, footer with the supplier line.
 * Called by portal-sync.php with the shop config it has just saved (pbb_shop() is cached per request).
 */
function pbb_apply_email_branding( array $shop ) {
	$dark = isset( $shop['dark'] ) ? sanitize_hex_color( $shop['dark'] ) : null;
	if ( $dark ) {
		update_option( 'woocommerce_email_base_color', $dark );
	}
	$logo = isset( $shop['logo'] ) ? (string) $shop['logo'] : '';
	// Email apps don't show SVG, so only a PNG logo goes in the header.
	update_option( 'woocommerce_email_header_image', preg_match( '/\.png$/i', $logo ) ? esc_url_raw( $logo ) : '' );
	if ( ! empty( $shop['supplier'] ) ) {
		update_option(
			'woocommerce_email_footer_text',
			/* translators: 1: supplier name, 2: postcode */
			sprintf( __( '%1$s · %2$s · Registered number plate supplier', '3d-plate-builder' ), $shop['supplier'], isset( $shop['postcode'] ) ? $shop['postcode'] : '' )
		);
	}
}
