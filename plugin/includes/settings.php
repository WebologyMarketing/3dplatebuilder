<?php
/**
 * Admin screen: WooCommerce → Plate Builder. Shop details (supplier, logo, colours, wording)
 * and every price the builder uses.
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'woocommerce',
			__( 'Plate Builder', '3d-plate-builder' ),
			__( 'Plate Builder', '3d-plate-builder' ),
			'manage_woocommerce',
			'pbb-settings',
			'pbb_render_settings'
		);
	},
	60
);

add_action( 'admin_post_pbb_save_pricing', 'pbb_save_pricing' );
add_action( 'admin_post_pbb_save_shop', 'pbb_save_shop' );

/**
 * Whether the shop details can be changed here. Hosted shops are locked (filter returns false)
 * until their documents are verified; the 3D Plate Builder portal sets that.
 */
function pbb_shop_editable() {
	return (bool) apply_filters( 'pbb_shop_editable', true );
}

/** The everyday shop fields: key => [label, type, help]. */
function pbb_shop_fields() {
	return array(
		'supplier'              => array( __( 'Supplier name', '3d-plate-builder' ), 'text', __( 'Exactly as registered with the DVLA. Printed in the footer of every plate.', '3d-plate-builder' ) ),
		'postcode'              => array( __( 'Supplier postcode', '3d-plate-builder' ), 'text', __( 'The postcode of the registered premises. Printed next to the name.', '3d-plate-builder' ) ),
		'logo'                  => array( __( 'Footer logo (URL)', '3d-plate-builder' ), 'url', __( 'Optional. A wide logo from your media library; leave empty to print the name as text.', '3d-plate-builder' ) ),
		'accent'                => array( __( 'Button colour', '3d-plate-builder' ), 'color', __( 'Buttons and highlights in the builder, the checkout and the promo blocks.', '3d-plate-builder' ) ),
		'accent_ink'            => array( __( 'Text on buttons', '3d-plate-builder' ), 'color', __( 'Must be easy to read on the button colour.', '3d-plate-builder' ) ),
		'dark'                  => array( __( 'Dark panels', '3d-plate-builder' ), 'color', __( 'Banners, the documents notice and the floating button.', '3d-plate-builder' ) ),
		'fitting_note'          => array( __( 'Fitting note in the builder', '3d-plate-builder' ), 'text', __( 'Under "In-store fitting while you wait".', '3d-plate-builder' ) ),
		'fitting_checkout_note' => array( __( 'Fitting note at checkout', '3d-plate-builder' ), 'text', __( 'Shown when fitting is in the basket.', '3d-plate-builder' ) ),
		'car_badge'             => array( __( 'Car badge (URL)', '3d-plate-builder' ), 'url', __( 'Optional. An SVG or PNG for the boot and grille in the "on a car" view (5:3 shape). Leave empty for no badge.', '3d-plate-builder' ) ),
	);
}

function pbb_save_shop() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', '3d-plate-builder' ) );
	}
	check_admin_referer( 'pbb_save_shop' );
	if ( ! pbb_shop_editable() ) {
		wp_die( esc_html__( 'Your shop details are locked until your account is verified.', '3d-plate-builder' ) );
	}

	$saved = get_option( 'pbb_shop', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$input = isset( $_POST['pbb_shop'] ) && is_array( $_POST['pbb_shop'] ) ? wp_unslash( $_POST['pbb_shop'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per field below.
	$error = '';

	// Advanced: the whole config as JSON (site admins only). Applied first, so the fields win.
	if ( current_user_can( 'manage_options' ) && isset( $_POST['pbb_shop_json'] ) ) {
		$json = trim( (string) wp_unslash( $_POST['pbb_shop_json'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded and checked below; each value is escaped on output.
		if ( '' !== $json && wp_json_encode( $saved, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) !== $json ) {
			$decoded = json_decode( $json, true );
			if ( is_array( $decoded ) ) {
				$saved = array_intersect_key( $decoded, pbb_shop_defaults() );
			} else {
				$error = 'json';
			}
		}
	}

	foreach ( pbb_shop_fields() as $key => $field ) {
		if ( ! isset( $input[ $key ] ) ) {
			continue;
		}
		$value = (string) $input[ $key ];
		if ( 'url' === $field[1] ) {
			$value = esc_url_raw( trim( $value ) );
		} elseif ( 'color' === $field[1] ) {
			$value = (string) sanitize_hex_color( $value );
			if ( '' === $value ) {
				continue;
			}
		} else {
			$value = sanitize_text_field( $value );
		}
		$saved[ $key ] = 'postcode' === $key ? strtoupper( $value ) : $value;
	}
	update_option( 'pbb_shop', $saved, true );

	$args = array( 'page' => 'pbb-settings', 'updated' => $error ? 'shop-json' : 'shop' );
	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) . '#pbb-shop' );
	exit;
}

function pbb_save_pricing() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', '3d-plate-builder' ) );
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
		<h1><?php esc_html_e( 'Plate Builder', '3d-plate-builder' ); ?></h1>

		<?php
		$updated = isset( $_GET['updated'] ) ? sanitize_key( $_GET['updated'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'shop-json' === $updated ) :
			?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Shop details saved, but the advanced JSON could not be read, so it was left as it was.', '3d-plate-builder' ); ?></p></div>
		<?php elseif ( 'shop' === $updated ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Shop details saved.', '3d-plate-builder' ); ?></p></div>
		<?php elseif ( $updated ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Prices saved.', '3d-plate-builder' ); ?></p></div>
		<?php endif; ?>

		<?php pbb_render_shop_form(); ?>

		<h2 class="title" id="pbb-prices"><?php esc_html_e( 'Prices', '3d-plate-builder' ); ?></h2>

		<p>
			<?php esc_html_e( 'A plate is priced from its character style (front & rear, or a single plate), plus a surcharge per plate for non-standard sizes. Badge, border and extras are charged once per basket line. Motorbike and trailer plates have one fixed price.', '3d-plate-builder' ); ?>
		</p>
		<?php if ( $product_id ) : ?>
			<p>
				<?php
				printf(
					/* translators: %s: link to the product */
					esc_html__( 'Builder orders are added to the basket as %s (hidden from your shop).', '3d-plate-builder' ),
					'<a href="' . esc_url( get_edit_post_link( $product_id ) ) . '">' . esc_html( get_the_title( $product_id ) ) . '</a>'
				);
				?>
			</p>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="pbb_save_pricing">
			<?php wp_nonce_field( 'pbb_save_pricing' ); ?>

			<h2><?php esc_html_e( 'Character style: front & rear plates', '3d-plate-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['styles'] as $id => $style ) {
					pbb_price_field( 'style_pair', $id, $style['label'], $p['style_pair'][ $id ] );
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Character style: one plate (front only or rear only)', '3d-plate-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['styles'] as $id => $style ) {
					pbb_price_field( 'style_single', $id, $style['label'], $p['style_single'][ $id ] );
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Plate size (added per plate)', '3d-plate-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $p['size'] as $id => $price ) {
					pbb_price_field( 'size', $id, $c['sizes'][ $id ]['label'], $price );
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Motorbike and trailer plates (one plate, fixed price)', '3d-plate-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $p['flat'] as $id => $price ) {
					pbb_price_field( 'flat', $id, $c['sizes'][ $id ]['label'], $price );
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Badge (once per order line; half price on motorbike and trailer plates)', '3d-plate-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['badges'] as $id => $badge ) {
					if ( 'none' !== $id ) {
						pbb_price_field( 'badge', $id, $badge['label'], $p['badge'][ $id ] );
					}
				}
				?>
			</table>

			<h2><?php esc_html_e( 'Border (once per order line)', '3d-plate-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['borders'] as $id => $border ) {
					if ( 'none' !== $id ) {
						pbb_price_field( 'border', $id, $border['label'], $p['border'][ $id ] );
					}
				}
				?>
			</table>

			<h2><?php esc_html_e( 'In-store fitting (shown in the builder)', '3d-plate-builder' ); ?></h2>
			<p><?php esc_html_e( 'Customers who pick fitting in the builder go to checkout with Collect and "Fit my plates while I wait" ticked; the fee itself is charged by that checkout option, so keep this the same as it.', '3d-plate-builder' ); ?></p>
			<table class="form-table" role="presentation">
				<?php pbb_price_field( 'services', 'fitting', __( 'In-store fitting', '3d-plate-builder' ), $p['services']['fitting'] ); ?>
			</table>

			<h2><?php esc_html_e( 'Extras (once per order line)', '3d-plate-builder' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				foreach ( $c['accessories'] as $id => $label ) {
					pbb_price_field( 'accessories', $id, $label, $p['accessories'][ $id ] );
				}
				?>
			</table>

			<?php submit_button( __( 'Save prices', '3d-plate-builder' ) ); ?>
		</form>

		<?php do_action( 'pbb_settings_after' ); ?>
	</div>
	<?php
}

/** The "Shop details" form at the top of the settings screen. */
function pbb_render_shop_form() {
	$shop     = pbb_shop();
	$editable = pbb_shop_editable();
	$saved    = get_option( 'pbb_shop', array() );
	?>
	<h2 class="title" id="pbb-shop"><?php esc_html_e( 'Shop details', '3d-plate-builder' ); ?></h2>
	<?php if ( ! $editable ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'You can change these once we have checked your documents and verified your account.', '3d-plate-builder' ); ?></p></div>
	<?php endif; ?>
	<p><?php esc_html_e( 'The builder helps you lay plates out to BS AU 145e. You stay responsible, as the registered supplier, for the plates you make.', '3d-plate-builder' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="pbb_save_shop">
		<?php wp_nonce_field( 'pbb_save_shop' ); ?>
		<fieldset <?php disabled( ! $editable ); ?>>
		<table class="form-table" role="presentation">
			<?php
			foreach ( pbb_shop_fields() as $key => $field ) {
				$id    = 'pbb-shop-' . $key;
				$value = 'color' === $field[1] ? pbb_shop_colour( $key ) : (string) $shop[ $key ];
				$class = 'color' === $field[1] ? '' : ' class="regular-text"';
				printf(
					'<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="%3$s" id="%1$s" name="pbb_shop[%4$s]" value="%5$s"%6$s><p class="description">%7$s</p></td></tr>',
					esc_attr( $id ),
					esc_html( $field[0] ),
					esc_attr( $field[1] ),
					esc_attr( $key ),
					esc_attr( $value ),
					$class, // phpcs:ignore WordPress.Security.EscapeOutput -- fixed string.
					esc_html( $field[2] )
				);
			}
			?>
		</table>
		<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<details>
				<summary><?php esc_html_e( 'Advanced: the whole shop config as JSON', '3d-plate-builder' ); ?></summary>
				<p class="description"><?php esc_html_e( 'Product and page mappings, category presets, menus and the sheeting print. Only print a sheeting brand on the plate backs if you have the maker\'s permission.', '3d-plate-builder' ); ?></p>
				<textarea name="pbb_shop_json" rows="16" class="large-text code" spellcheck="false"><?php echo esc_textarea( wp_json_encode( is_array( $saved ) ? $saved : array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></textarea>
			</details>
		<?php endif; ?>
		<?php submit_button( __( 'Save shop details', '3d-plate-builder' ) ); ?>
		</fieldset>
	</form>
	<hr>
	<?php
}
