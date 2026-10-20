<?php
/**
 * Plain-text version of the shop's own order email (see customer-order.php).
 *
 * @package PlateBuilder
 * @var WC_Order $order
 * @var string   $email_heading
 * @var WC_Email $email
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 */

defined( 'ABSPATH' ) || exit;

$own  = pbb_emails( 'order' );
$fill = function ( $key ) use ( $own, $order ) {
	return trim( pbb_email_fill( isset( $own[ $key ] ) ? $own[ $key ] : '', $order ) );
};

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";
if ( '' !== $fill( 'order_message' ) ) {
	echo esc_html( $fill( 'order_message' ) ) . "\n\n";
}
if ( '' !== $fill( 'order_needs' ) ) {
	echo esc_html( strtoupper( __( 'What we need from you', '3d-plate-builder' ) ) ) . "\n" . esc_html( $fill( 'order_needs' ) ) . "\n\n";
}
if ( '' !== $fill( 'order_next' ) ) {
	echo esc_html( strtoupper( __( 'What happens next', '3d-plate-builder' ) ) ) . "\n" . esc_html( $fill( 'order_next' ) ) . "\n\n";
}
echo "----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
