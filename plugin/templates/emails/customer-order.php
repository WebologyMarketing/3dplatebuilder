<?php
/**
 * The shop's own order email (Pro and Complete), in place of WooCommerce's
 * "processing" and "on hold" emails. Wording from the portal (pbb_emails()); the order
 * details, documents box and customer details follow as in WooCommerce's own template.
 *
 * @package PlateBuilder
 * @var WC_Order $order
 * @var string   $email_heading
 * @var WC_Email $email
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 */

defined( 'ABSPATH' ) || exit;

$own    = pbb_emails( 'order' );
$fill   = function ( $key ) use ( $own, $order ) {
	return trim( pbb_email_fill( isset( $own[ $key ] ) ? $own[ $key ] : '', $order ) );
};
$para   = 'margin:0 0 18px;font-size:16px;line-height:1.6';

do_action( 'woocommerce_email_header', $email_heading, $email );

if ( '' !== $fill( 'order_message' ) ) {
	echo '<p style="' . esc_attr( $para ) . '">' . nl2br( esc_html( $fill( 'order_message' ) ) ) . '</p>';
}
if ( '' !== $fill( 'order_needs' ) ) {
	echo '<div style="background:#f6f6f7;border-radius:12px;padding:18px;margin:0 0 20px">';
	echo '<p style="margin:0 0 6px;font-size:16px;font-weight:bold">' . esc_html__( 'What we need from you', '3d-plate-builder' ) . '</p>';
	echo '<p style="margin:0;font-size:15px;line-height:1.6">' . nl2br( esc_html( $fill( 'order_needs' ) ) ) . '</p>';
	echo '</div>';
}
if ( '' !== $fill( 'order_next' ) ) {
	echo '<p style="margin:0 0 6px;font-size:16px;font-weight:bold">' . esc_html__( 'What happens next', '3d-plate-builder' ) . '</p>';
	echo '<p style="' . esc_attr( $para ) . '">' . nl2br( esc_html( $fill( 'order_next' ) ) ) . '</p>';
}

/* The order table, then the documents box (includes/documents.php), as in WooCommerce's template. */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_footer', $email );
