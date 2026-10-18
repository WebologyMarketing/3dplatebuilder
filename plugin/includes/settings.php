<?php
/**
 * Admin screen: WooCommerce → Plate Builder. Edit every price the builder uses.
 *
 * @package PlateBoss
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'woocommerce',
			__( 'Plate Builder', 'plateboss-builder' ),
			__( 'Plate Builder', 'plateboss-builder' ),
			'manage_woocommerce',
			'pbb-settings',
			'pbb_render_settings'
		);
	},
	60
);

add_action( 'admin_post_pbb_save_pricing', 'pbb_save_pricing' );

function pbb_save_pricing() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'plateboss-builder' ) );
	}
	check_admin_referer( 'pbb_save_pricing' );

	$input   = isset( $_POST['pbb_pricing'] ) && is_array( $_POST['pbb_pricing'] ) ? wp_unslash( $_POST['pbb_pricing'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each value is cast below.
	$pricing = pbb_default_pricing();

	foreach ( $pricing as $group => $value ) {
		foreach ( $value as $key => $default ) {
			if ( isset( $input[ $group ][ $key ] ) && is_numeric( $input[ $group ][ $key ] ) ) {
				$pricing[ $group ][ $key ] = max( 0, round( (float) $input[ $group ][ $key ], 2 ) );
			}
		}
	}
	update_option( 'pbb_pricing_v2', $pricing, false );

	wp_safe_redirect( add_query_arg( array( 'page' => 'pbb-settings', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * One price input.
 */
function pbb_price_field( $group, $key, $label, $value ) {
	$name = "pbb_pricing[{$group}][{$key}]";
	printf(
		'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td>%3$s <input type="number" id="%1$s" name="%4$s" value="%5$s" min="0" step="0.01" class="small-text"></td></tr>',
		esc_attr( 'pbb-' . $group . '-' . $key ),
		esc_html( $label ),
		esc_html( get_woocommerce_currency_symbol() ),
		esc_attr( $name ),
		esc_attr( wc_format_decimal( $value, 2 ) )
	);
}

function pbb_render_settings() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$c          = pbb_catalog();
	$p          = pbb_get_pricing();
	$product_id = pbb_get_product_id();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Plate Builder', 'plateboss-builder' ); ?></h1>

		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Prices saved.', 'plateboss-builder' ); ?></p></div>
		<?php endif; ?>

		<p>
			<?php esc_html_e( 'A plate is priced from its character style (front & rear, or a single plate), plus a surcharge per plate for non-standard sizes. Badge, border and extras are charged once per basket line. Motorbike and trailer plates have one fixed price.', 'plateboss-builder' ); ?>
		</p>
		<?php if ( $product_id ) : ?>
			<p>
				<?php
				printf(
					/* translators: %s: link to the product */
					esc_html__( 'Builder orders are added to the basket as %s (hidden from your shop).', 'plateboss-builder' ),
					'<a href="' . esc_url( get_edit_post_link( $product_id ) ) . '">' . esc_html( get_the_title( $product_id ) ) . '</a>'
				);
				?>
			</p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="pbb_save_pricing">
			<?php wp_nonce_field( 'pbb_save_pricing' ); ?>

			<h2><?php esc_html_e( 'Character style: front & rear plates', 'plateboss-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['styles'] as $id => $style ) {
					pbb_price_field( 'style_pair', $id, $style['label'], $p['style_pair'][ $id ] );
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Character style: one plate (front only or rear only)', 'plateboss-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['styles'] as $id => $style ) {
					pbb_price_field( 'style_single', $id, $style['label'], $p['style_single'][ $id ] );
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Plate size (added per plate)', 'plateboss-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $p['size'] as $id => $price ) {
					pbb_price_field( 'size', $id, $c['sizes'][ $id ]['label'], $price );
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Motorbike and trailer plates (one plate, fixed price)', 'plateboss-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $p['flat'] as $id => $price ) {
					pbb_price_field( 'flat', $id, $c['sizes'][ $id ]['label'], $price );
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Badge (once per order line; half price on motorbike and trailer plates)', 'plateboss-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['badges'] as $id => $badge ) {
					if ( 'none' !== $id ) {
						pbb_price_field( 'badge', $id, $badge['label'], $p['badge'][ $id ] );
					}
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Border (once per order line)', 'plateboss-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['borders'] as $id => $border ) {
					if ( 'none' !== $id ) {
						pbb_price_field( 'border', $id, $border['label'], $p['border'][ $id ] );
					}
				}
				?>
			</table>

			<h2><?php esc_html_e( 'In-store fitting (shown in the builder)', 'plateboss-builder' ); ?></h2>
			<p><?php esc_html_e( 'Customers who pick fitting in the builder go to checkout with Collect and "Fit my plates while I wait" ticked; the fee itself is charged by that checkout option, so keep this the same as it.', 'plateboss-builder' ); ?></p>
			<table class="form-table" role="presentation">
				<?php pbb_price_field( 'services', 'fitting', __( 'In-store fitting', 'plateboss-builder' ), $p['services']['fitting'] ); ?>
			</table>

			<h2><?php esc_html_e( 'Extras (once per order line)', 'plateboss-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['accessories'] as $id => $label ) {
					pbb_price_field( 'accessories', $id, $label, $p['accessories'][ $id ] );
				}
				?>
			</table>

			<?php submit_button( __( 'Save prices', 'plateboss-builder' ) ); ?>
		</form>

		<?php do_action( 'pbb_settings_after' ); ?>
	</div>
	<?php
}
