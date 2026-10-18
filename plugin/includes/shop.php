<?php
/**
 * Shop config: everything that makes this builder one shop's rather than another's.
 *
 * Stored in one option, `pbb_shop` (an array). Provisioning sets it in one go:
 *
 *     wp option update pbb_shop "$(cat shop.json)" --format=json
 *
 * and shop owners edit the everyday parts in WooCommerce → Plate Builder → Shop details.
 * Missing keys fall back to the neutral defaults below, so a fresh install is never branded
 * as anyone else. The older filters (pbb_supplier_details, pbb_product_presets, …) still
 * run on top, for code that already uses them.
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

/** Neutral defaults: no supplier branding, no product or page mapping. */
function pbb_shop_defaults() {
	return array(
		// Plate footer (BS AU 145e: the registered supplier's name and postcode).
		'supplier'              => '',
		'postcode'              => '',
		'logo'                  => '', // URL; empty = the supplier name prints as text.
		'standard'              => 'BS AU 145e',

		// Brand colours (hex): buttons and highlights, the text on them, and dark panels.
		'accent'                => '#F7D333',
		'accent_ink'            => '#0D0E10',
		'dark'                  => '#0D0E10',

		// Wording.
		'fitting_note'          => __( 'Collect from the shop and we fit them on the spot.', 'plateboss-builder' ),
		'fitting_checkout_note' => __( 'In-store fitting is only available when you collect your plates.', 'plateboss-builder' ),

		// "On a car" view: the badge on the car's boot and grille. Empty = no badge.
		'car_badge'             => '',

		// The back of each plate: plain sheeting, or the sheeting maker's print (only with their permission).
		// Shape: array( 'title' => 'Brand', 'mark' => '®', 'tag' => 'Range', 'subtitle' => 'Reflective Sheeting', 'maker' => 'Maker Ltd' ).
		'backing'               => null,

		// Shop product ID => the preset that product starts on (see pbb_product_presets()).
		'products'              => array(),

		// Pages that promote or show the builder: page ID => preset (style, size, badge, name).
		'landing_pages'         => array(),
		'feature_front_page'    => true,

		// Product category slug => preset, for the category-page promo band.
		'category_presets'      => array(),

		// Nav menu slugs that get a "3D Plate Builder" item.
		'menu_slugs'            => array(),
	);
}

/**
 * The shop config, or one key of it.
 *
 * @param string|null $key Key to return, or null for the whole array.
 * @return mixed
 */
function pbb_shop( $key = null ) {
	static $shop = null;
	if ( null === $shop ) {
		$saved = get_option( 'pbb_shop', array() );
		$shop  = array_merge( pbb_shop_defaults(), is_array( $saved ) ? $saved : array() );
	}
	if ( null === $key ) {
		return $shop;
	}
	return array_key_exists( $key, $shop ) ? $shop[ $key ] : null;
}

/**
 * Supplier name and postcode for the plate footer. Falls back to the store's own name and
 * postcode (WooCommerce → Settings) until the shop sets them, and flags that in the admin.
 */
function pbb_shop_supplier() {
	$supplier = trim( (string) pbb_shop( 'supplier' ) );
	$postcode = trim( (string) pbb_shop( 'postcode' ) );
	return array(
		'supplier' => '' !== $supplier ? $supplier : wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'postcode' => '' !== $postcode ? $postcode : (string) get_option( 'woocommerce_store_postcode', '' ),
		'set'      => '' !== $supplier && '' !== $postcode,
	);
}

/** One brand colour as a safe hex value (falls back to the default if the saved one isn't valid). */
function pbb_shop_colour( $key ) {
	$colour = sanitize_hex_color( (string) pbb_shop( $key ) );
	if ( ! $colour ) {
		$defaults = pbb_shop_defaults();
		$colour   = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '#000000';
	}
	return $colour;
}

/** The brand colours as CSS custom properties, for the builder and the promo blocks. */
function pbb_shop_css_vars() {
	return ':root{--pbb-accent:' . pbb_shop_colour( 'accent' ) . ';--pbb-accent-ink:' . pbb_shop_colour( 'accent_ink' ) . ';--pbb-dark:' . pbb_shop_colour( 'dark' ) . '}';
}

/** The backing print as plain strings for the builder, or null for plain sheeting. */
function pbb_shop_backing() {
	$backing = pbb_shop( 'backing' );
	if ( ! is_array( $backing ) || empty( $backing['title'] ) ) {
		return null;
	}
	$out = array();
	foreach ( array( 'title', 'mark', 'tag', 'subtitle', 'maker' ) as $key ) {
		$out[ $key ] = isset( $backing[ $key ] ) ? sanitize_text_field( (string) $backing[ $key ] ) : '';
	}
	return $out;
}

/** Map array keys to integers (JSON object keys arrive as strings). */
function pbb_int_keys( $map ) {
	$out = array();
	foreach ( (array) $map as $id => $value ) {
		if ( (int) $id > 0 && is_array( $value ) ) {
			$out[ (int) $id ] = $value;
		}
	}
	return $out;
}

/** Until the supplier details are set, every plate prints a guess: say so on the admin screens. */
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_woocommerce' ) || pbb_shop_supplier()['set'] ) {
			return;
		}
		$url = admin_url( 'admin.php?page=pbb-settings#pbb-shop' );
		echo '<div class="notice notice-warning"><p>';
		printf(
			/* translators: %s: link to the settings screen */
			esc_html__( 'Plate Builder: set your supplier name and postcode, exactly as registered with the DVLA, so they print correctly on every plate. %s', 'plateboss-builder' ),
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Set them now', 'plateboss-builder' ) . '</a>'
		);
		echo '</p></div>';
	}
);
