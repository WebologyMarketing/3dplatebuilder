<?php
/**
 * The shop's brand and details, from the builder plugin's shop config (pbb_shop()), which the
 * 3dPlateBuilder portal fills in on Publish: main colour, text on it, dark colour, heading font,
 * logo, name, email and address. Without the plugin, theme.json's defaults show.
 *
 * @package 3dpb-shop
 */

defined( 'ABSPATH' ) || exit;

/** Heading fonts a shop can choose (the same list as the portal). */
const PBB_SHOP_FONTS = array( 'Archivo', 'Barlow Condensed', 'Montserrat', 'Poppins', 'Geist' );

/** One value from the shop config, or ''. */
function pbb_shop_theme_value( $key ) {
	return function_exists( 'pbb_shop' ) ? (string) pbb_shop( $key ) : '';
}

/* Colours and heading font into theme.json, so the editor, blocks and WooCommerce all use them. */
add_filter(
	'wp_theme_json_data_theme',
	function ( $theme_json ) {
		$data    = $theme_json->get_data();
		$colours = array(
			'accent'     => sanitize_hex_color( pbb_shop_theme_value( 'accent' ) ),
			'accent-ink' => sanitize_hex_color( pbb_shop_theme_value( 'accent_ink' ) ),
			'dark'       => sanitize_hex_color( pbb_shop_theme_value( 'dark' ) ),
		);
		$palette = isset( $data['settings']['color']['palette']['theme'] ) ? $data['settings']['color']['palette']['theme'] : ( $data['settings']['color']['palette'] ?? array() );
		foreach ( $palette as $i => $entry ) {
			if ( ! empty( $colours[ $entry['slug'] ] ) ) {
				$palette[ $i ]['color'] = $colours[ $entry['slug'] ];
			}
		}

		$families = isset( $data['settings']['typography']['fontFamilies']['theme'] ) ? $data['settings']['typography']['fontFamilies']['theme'] : ( $data['settings']['typography']['fontFamilies'] ?? array() );
		$font     = pbb_shop_theme_value( 'heading_font' );
		if ( in_array( $font, PBB_SHOP_FONTS, true ) ) {
			foreach ( $families as $i => $family ) {
				if ( 'heading' === $family['slug'] ) {
					$families[ $i ]['fontFamily'] = '"' . $font . '", Geist, system-ui, sans-serif';
				}
			}
		}

		return $theme_json->update_with(
			array(
				'version'  => 3,
				'settings' => array(
					'color'      => array( 'palette' => $palette ),
					'typography' => array( 'fontFamilies' => $families ),
				),
			)
		);
	}
);

/** [pbb_brand]: the logo, or the shop's initials and name, linking home. */
add_shortcode(
	'pbb_brand',
	function () {
		$name = pbb_shop_theme_value( 'name' );
		$name = '' !== $name ? $name : wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$logo = pbb_shop_theme_value( 'logo' );
		$link = esc_url( home_url( '/' ) );
		if ( '' !== $logo ) {
			return '<a class="pbb-brand" href="' . $link . '" rel="home"><img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $name ) . '" height="44"></a>';
		}
		$words    = array_values( array_filter( preg_split( '/\s+/', $name ), fn ( $w ) => '' !== $w && ! preg_match( '/^(ltd|limited|the)$/i', $w ) ) );
		$initials = strtoupper( implode( '', array_map( fn ( $w ) => mb_substr( $w, 0, 1 ), array_slice( $words, 0, 2 ) ) ) );
		return '<a class="pbb-brand" href="' . $link . '" rel="home"><span class="pbb-brand__mark" aria-hidden="true">' . esc_html( $initials ) . '</span>' . esc_html( $name ) . '</a>';
	}
);

/**
 * [pbb_info key="name|email|address|supplier|postcode"]: a shop detail from the account.
 * Falls back to the site title and WooCommerce's store address.
 */
add_shortcode(
	'pbb_info',
	function ( $atts ) {
		$key = sanitize_key( $atts['key'] ?? '' );
		$val = pbb_shop_theme_value( $key );
		if ( '' === $val ) {
			$val = match ( $key ) {
				'name', 'supplier' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'email'            => (string) get_option( 'woocommerce_email_from_address', get_option( 'admin_email' ) ),
				'postcode'         => (string) get_option( 'woocommerce_store_postcode', '' ),
				'address'          => trim( implode( ', ', array_filter( array( get_option( 'woocommerce_store_address' ), get_option( 'woocommerce_store_city' ), get_option( 'woocommerce_store_postcode' ) ) ) ) ),
				default            => '',
			};
		}
		if ( 'email' === $key && is_email( $val ) ) {
			return '<a href="mailto:' . esc_attr( $val ) . '">' . esc_html( $val ) . '</a>';
		}
		return nl2br( esc_html( $val ) );
	}
);

/** [pbb_from style="gel-3d"]: the "from" price for a pair in that style, from the builder's prices. */
add_shortcode(
	'pbb_from',
	function ( $atts ) {
		if ( ! function_exists( 'pbb_get_pricing' ) ) {
			return '';
		}
		$pricing = pbb_get_pricing();
		$style   = sanitize_key( $atts['style'] ?? '' );
		if ( '' === $style ) {
			$on    = function_exists( 'pbb_offered_options' ) ? array_keys( pbb_offered_options( 'styles' ) ) : array_keys( $pricing['style_pair'] );
			$price = min( array_intersect_key( $pricing['style_pair'], array_flip( $on ) ) ?: array( 0 ) );
		} else {
			$price = $pricing['style_pair'][ $style ] ?? 0;
		}
		return function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $price ) ) : '£' . number_format( (float) $price, 2 );
	}
);

/** A style switched off in the account: its grid card and page link are hidden. */
add_filter(
	'render_block',
	function ( $html, $block ) {
		$class = $block['attrs']['className'] ?? '';
		if ( preg_match( '/\bpbb-style-([a-z0-9-]+)\b/', $class, $m ) && function_exists( 'pbb_offered' ) && ! pbb_offered( 'styles', $m[1] ) ) {
			return '';
		}
		return $html;
	},
	10,
	2
);
