<?php
/**
 * Server-side catalogue, validation and pricing.
 *
 * Mirrors the option ids in assets/js (plate-sizes.js, plate-styles.js, plate-badges.js).
 * The browser shows a live price, but the cart price is always recalculated here
 * from these rules, so a tampered request can't change what the customer pays.
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Everything a customer can choose. Keep ids in sync with the JS data files.
 */
function pbb_catalog() {
	static $catalog = null;
	if ( null !== $catalog ) {
		return $catalog;
	}

	$uk_sizes = array( 'standard', 'short', 'oversized', 'motorbike', 'trailer' );
	// For now these sizes are printed only (3D gel and 4D to follow); mirrors `styles` in plate-sizes.js.
	$printed_only = array( 'printed' );
	$ev_sizes = array( 'standard', 'short', 'trailer' );

	$catalog = array(
		'sizes'       => array(
			'standard'        => array( 'label' => 'Standard', 'mm' => '520 × 111', 'rear_only' => false ),
			'short'           => array( 'label' => 'Short', 'mm' => null, 'rear_only' => false ),
			'hex'             => array( 'label' => 'Hex', 'mm' => null, 'rear_only' => false ),
			// rear_only: offered for the rear plate only (the front can be another size).
			// single: a one-plate vehicle (motorbike, trailer), sold as one rear plate.
			'square'          => array( 'label' => '4x4', 'mm' => '279 × 203', 'rear_only' => true ),
			'oversized'       => array( 'label' => 'Oversized', 'mm' => '533 × 152', 'rear_only' => true ),
			'motorbike'          => array( 'label' => 'Motorbike', 'mm' => '229 × 178', 'rear_only' => true, 'single' => true, 'styles' => $printed_only ),
			'trailer'            => array( 'label' => 'Trailer', 'mm' => '520 × 111', 'rear_only' => true, 'single' => true, 'styles' => $printed_only ),
			'trailer-commercial' => array( 'label' => 'Commercial trailer (white)', 'mm' => '229 × 178', 'rear_only' => true, 'single' => true, 'styles' => $printed_only ),
			'japanese-import'    => array( 'label' => 'Japanese import', 'mm' => '330 × 165', 'rear_only' => false, 'styles' => $printed_only ),
			'us-import'          => array( 'label' => 'US import', 'mm' => '305 × 152', 'rear_only' => false, 'styles' => $printed_only ),
		),
		'styles'      => array(
			'printed'      => array( 'label' => 'Printed', 'road_legal' => true, 'accent' => false ),
			'gel-3d'       => array( 'label' => '3D Gel', 'road_legal' => true, 'accent' => false ),
			'acrylic-4d-3' => array( 'label' => '4D 3mm', 'road_legal' => true, 'accent' => false ),
			'acrylic-4d-6' => array( 'label' => '4D 6mm', 'road_legal' => true, 'accent' => false ),
			'gel-4d-3'     => array( 'label' => '4D Gel 3mm', 'road_legal' => true, 'accent' => false ),
			'gel-4d-6'     => array( 'label' => '4D Gel 6mm', 'road_legal' => true, 'accent' => false ),
			'retro'        => array( 'label' => 'Retro', 'road_legal' => true, 'accent' => false ),
		),
		'accents'     => array(
			'red'    => 'Red',
			'blue'   => 'Blue',
			'green'  => 'Green',
			'grey'   => 'Grey',
			'silver' => 'Silver',
			'gold'   => 'Gold',
		),
		'badges'      => array(
			'none'      => array( 'label' => 'None', 'sizes' => null ),
			'uk'        => array( 'label' => 'UK badge (printed)', 'sizes' => $uk_sizes ),
			'uk-gel'      => array( 'label' => 'UK gel badge, white', 'sizes' => $uk_sizes ),
			'uk-gel-blue' => array( 'label' => 'UK gel badge, blue', 'sizes' => $uk_sizes ),
			'uk-4d'       => array( 'label' => 'UK 4D badge (raised)', 'sizes' => $uk_sizes ),
			'ev'          => array( 'label' => 'EV strip (printed)', 'sizes' => $ev_sizes ),
			'ev-gel'      => array( 'label' => 'EV gel badge', 'sizes' => $ev_sizes ),
			'ev-uk-gel'   => array( 'label' => 'EV gel badge with UK', 'sizes' => $ev_sizes ),
		),
		'borders'     => array(
			'none'      => array( 'label' => 'None', 'road_legal' => true ),
			'black-1mm' => array( 'label' => 'Black, 1 mm', 'road_legal' => true ),
			'black-2mm' => array( 'label' => 'Black, 2 mm', 'road_legal' => true ),
			'black-3mm' => array( 'label' => 'Black, 3 mm', 'road_legal' => true ),
		),
		'plates'      => array(
			'pair'  => 'Front & rear',
			'front' => 'Front only',
			'rear'  => 'Rear only',
		),
		// Ids are stored on orders, so keep existing ones when renaming.
		'accessories' => array(
			'sticky_pads'       => '8 adhesive pads (1 mm)',
			'xl_pads'           => 'XL pad set (8 × 1 mm + 3 × 5 mm)',
			'screw_kit'         => 'Screws + caps (4 screws, 2 yellow & 2 white caps)',
			// Standard frame holders ('frame_holders') are out of stock; add the line back to sell them again.
			'frameless_holders' => 'Frameless holders (set)',
		),
		// A customer picks at most one extra from each group.
		'accessory_groups' => array(
			'pads'    => array( 'sticky_pads', 'xl_pads' ),
			'holders' => array( 'frame_holders', 'frameless_holders' ),
		),
	);
	return $catalog;
}

/**
 * Shop products that use the builder, and what each one starts on (product ID => preset).
 * Set per shop in the shop config's `products` (includes/shop.php); filter `pbb_product_presets` runs on top.
 *
 * The list also works the other way: a finished design goes into the basket as the product
 * that matches it best (see pbb_product_for_config()), so reports and Google show what sold.
 * Order matters there: more specific entries (EV, short, hex) come before general ones.
 */
function pbb_product_presets() {
	return pbb_int_keys( apply_filters( 'pbb_product_presets', pbb_int_keys( pbb_shop( 'products' ) ) ) );
}

/** Rear plate size; baskets from before 1.9 had one size for both. */
function pbb_rear_size( array $config ) {
	return ! empty( $config['rear_size'] ) ? $config['rear_size'] : $config['size'];
}

/** Preset for a shop product, or null if it doesn't use the builder. */
function pbb_product_preset( $product_id ) {
	$presets = pbb_product_presets();
	return isset( $presets[ $product_id ] ) ? $presets[ $product_id ] : null;
}

/**
 * The shop product a finished design is sold as: the first preset whose style, EV badge and
 * (if it names one) size all match. Falls back to the hidden "Custom Number Plate" product.
 */
function pbb_product_for_config( array $config ) {
	$ev   = 0 === strpos( $config['badge'], 'ev' );
	$size = 'rear' === $config['plates'] ? pbb_rear_size( $config ) : $config['size'];
	foreach ( pbb_product_presets() as $id => $preset ) {
		if ( isset( $preset['match'] ) && ! $preset['match'] ) {
			continue;
		}
		$preset_ev = isset( $preset['badge'] ) && 0 === strpos( $preset['badge'], 'ev' );
		if ( $preset['style'] !== $config['style'] || $preset_ev !== $ev ) {
			continue;
		}
		if ( isset( $preset['size'] ) ? $preset['size'] !== $size : in_array( $size, array( 'motorbike', 'trailer', 'trailer-commercial' ), true ) ) {
			continue;
		}
		$product = wc_get_product( $id );
		if ( $product && $product->is_purchasable() && 'publish' === $product->get_status() ) {
			return $id;
		}
	}
	return pbb_get_product_id();
}

/** Option names by group, for the builder's price lines. */
function pbb_catalog_labels() {
	$c   = pbb_catalog();
	$out = array( 'accessories' => $c['accessories'] );
	foreach ( array( 'sizes', 'styles', 'badges', 'borders' ) as $group ) {
		$out[ $group ] = wp_list_pluck( $c[ $group ], 'label' );
	}
	return $out;
}

/**
 * Default prices (GBP): typical UK prices, October 2026. Each shop sets its own.
 * Edit them in WooCommerce → Plate Builder.
 *
 *   style_pair / style_single  front & rear / one plate, standard size, no extras
 *   size                       surcharge per plate for non-standard sizes
 *   flat                       motorbike and trailer plates: one printed plate at a fixed price
 *   badge, border              once per basket line (half for motorbike and trailer plates' badge)
 *   accessories                once per basket line
 */
function pbb_default_pricing() {
	return array(
		'style_pair'   => array(
			'printed'      => 29.99,
			'gel-3d'       => 49.99,
			'acrylic-4d-3' => 49.99,
			'acrylic-4d-6' => 64.99,
			'gel-4d-3'     => 64.99,
			'gel-4d-6'     => 79.99,
			'retro'        => 69.99,
		),
		// Half the pair price, rounded up to the nearest £5, minus 1p, plus £5.
		'style_single' => array(
			'printed'      => 19.99,
			'gel-3d'       => 29.99,
			'acrylic-4d-3' => 29.99,
			'acrylic-4d-6' => 39.99,
			'gel-4d-3'     => 39.99,
			'gel-4d-6'     => 44.99,
			'retro'        => 39.99,
		),
		'size'         => array(
			'standard'        => 0,
			'short'           => 10,
			'hex'             => 10,
			'square'          => 10,
			'oversized'       => 10,
			'japanese-import' => 10,
			'us-import'       => 10,
		),
		'flat'         => array(
			'motorbike'          => 19.99,
			'trailer'            => 19.99,
			'trailer-commercial' => 19.99,
		),
		'badge'        => array(
			'uk'          => 5,
			'uk-gel'      => 10,
			'uk-gel-blue' => 10,
			'uk-4d'       => 15,
			'ev'          => 5,
			'ev-gel'      => 10,
			'ev-uk-gel'   => 10,
		),
		'border'       => array(
			'black-1mm' => 10,
			'black-2mm' => 10,
			'black-3mm' => 10,
		),
		// Shown in the builder only: the £30 is charged at checkout by the "Fit my plates" option.
		'services'     => array(
			'fitting' => 30,
		),
		'accessories'  => array(
			'sticky_pads'       => 2.50,
			'xl_pads'           => 6.99,
			'screw_kit'         => 2.50,
			'frame_holders'     => 18,
			'frameless_holders' => 20,
		),
	);
}

/**
 * Saved prices merged over the defaults, so new options always have a price.
 * Prices are kept in `pbb_pricing_v2`: the 1.x list (`pbb_pricing`) priced per plate
 * and can't be reused, apart from the extras, which are carried over.
 */
function pbb_get_pricing() {
	$saved = get_option( 'pbb_pricing_v2', null );
	if ( null === $saved ) {
		$old   = get_option( 'pbb_pricing', array() );
		$saved = isset( $old['accessories'] ) ? array( 'accessories' => $old['accessories'] ) : array();
	}
	$out = pbb_default_pricing();
	foreach ( $out as $group => $value ) {
		foreach ( $value as $key => $default ) {
			if ( isset( $saved[ $group ][ $key ] ) && is_numeric( $saved[ $group ][ $key ] ) ) {
				$out[ $group ][ $key ] = (float) $saved[ $group ][ $key ];
			}
		}
	}
	return $out;
}

/**
 * Same cleaning and DVLA auto-spacing as plate-layout.js.
 */
function pbb_format_registration( $raw ) {
	$raw   = strtoupper( preg_replace( '/\s+/', ' ', (string) $raw ) );
	$clean = '';
	$chars = 0;
	foreach ( str_split( ltrim( $raw ) ) as $c ) {
		if ( ' ' === $c ) {
			if ( false === strpos( $clean, ' ' ) ) {
				$clean .= ' ';
			}
		} elseif ( preg_match( '/[A-Z0-9]/', $c ) && $chars < 7 ) {
			$clean .= $c;
			$chars++;
		}
	}
	$clean = trim( $clean );
	if ( false !== strpos( $clean, ' ' ) ) {
		return $clean;
	}
	$patterns = array(
		'/^([A-Z]{2}\d{2})([A-Z]{3})$/', // Current: AB12 CDE.
		'/^([A-Z]\d{1,3})([A-Z]{3})$/',  // Prefix: A123 BCD.
		'/^([A-Z]{3})(\d{1,3}[A-Z])$/',  // Suffix: ABC 123D.
	);
	foreach ( $patterns as $re ) {
		if ( preg_match( $re, $clean, $m ) ) {
			return $m[1] . ' ' . $m[2];
		}
	}
	return $clean;
}

/**
 * Validate a configuration sent from the builder.
 *
 * @param mixed $raw Decoded JSON from the browser.
 * @return array|WP_Error Clean configuration.
 */
function pbb_sanitize_config( $raw ) {
	$c = pbb_catalog();
	if ( ! is_array( $raw ) ) {
		return new WP_Error( 'pbb_invalid', __( 'Something went wrong. Please refresh and try again.', '3d-plate-builder' ) );
	}

	$pick = function ( $key, $allowed, $default ) use ( $raw ) {
		$value = isset( $raw[ $key ] ) && is_string( $raw[ $key ] ) ? sanitize_key( $raw[ $key ] ) : $default;
		return array_key_exists( $value, $allowed ) ? $value : $default;
	};

	$registration = pbb_format_registration( isset( $raw['registration'] ) ? $raw['registration'] : '' );
	if ( '' === $registration ) {
		return new WP_Error( 'pbb_registration', __( 'Please enter your registration.', '3d-plate-builder' ) );
	}

	$size      = $pick( 'size', $c['sizes'], 'standard' );
	$rear_size = $pick( 'rearSize', $c['sizes'], $size );
	$style     = $pick( 'style', $c['styles'], 'printed' );
	$badge  = $pick( 'badge', $c['badges'], 'none' );
	$border = $pick( 'border', $c['borders'], 'none' );
	$plates = $pick( 'plates', $c['plates'], 'pair' );
	$accent = $c['styles'][ $style ]['accent'] ? $pick( 'accent', $c['accents'], 'grey' ) : null;

	// Front and rear sizes: one-plate sizes (motorbike, trailer) are both; rear-only sizes can't be the front.
	if ( ! empty( $c['sizes'][ $size ]['single'] ) || ! empty( $c['sizes'][ $rear_size ]['single'] ) ) {
		$size      = ! empty( $c['sizes'][ $size ]['single'] ) ? $size : $rear_size;
		$rear_size = $size;
		$plates    = 'rear';
	} elseif ( $c['sizes'][ $size ]['rear_only'] ) {
		return new WP_Error( 'pbb_size', __( 'That size is for rear plates only. Please choose another front plate size.', '3d-plate-builder' ) );
	}
	$in_use = array_unique( 'pair' === $plates ? array( $size, $rear_size ) : array( 'rear' === $plates ? $rear_size : $size ) );

	foreach ( $in_use as $s ) {
		if ( null !== $c['badges'][ $badge ]['sizes'] && ! in_array( $s, $c['badges'][ $badge ]['sizes'], true ) ) {
			return new WP_Error( 'pbb_badge', __( 'That badge isn\'t available on this plate size.', '3d-plate-builder' ) );
		}
		if ( isset( $c['sizes'][ $s ]['styles'] ) && ! in_array( $style, $c['sizes'][ $s ]['styles'], true ) ) {
			return new WP_Error( 'pbb_style', __( 'That character style isn\'t available on this plate size yet.', '3d-plate-builder' ) );
		}
	}

	$accessories = array();
	if ( isset( $raw['accessories'] ) && is_array( $raw['accessories'] ) ) {
		foreach ( $raw['accessories'] as $a ) {
			$a = sanitize_key( $a );
			if ( isset( $c['accessories'][ $a ] ) ) {
				// Only one per group (pads, holders): a later pick replaces an earlier one.
				foreach ( $c['accessory_groups'] as $group ) {
					if ( in_array( $a, $group, true ) ) {
						foreach ( $group as $other ) {
							unset( $accessories[ $other ] );
						}
					}
				}
				$accessories[ $a ] = true;
			}
		}
	}

	// Short and hex plates are cut to the registration; record the widths the builder calculated.
	$cut        = function ( $key, $s ) use ( $raw ) {
		$w = isset( $raw[ $key ] ) ? (int) round( (float) $raw[ $key ] ) : 0;
		return in_array( $s, array( 'short', 'hex' ), true ) ? max( 150, min( 520, $w ) ) : null;
	};
	$width      = $cut( 'width', $size );
	$rear_width = $cut( isset( $raw['rearWidth'] ) ? 'rearWidth' : 'width', $rear_size );

	return array(
		'registration' => $registration,
		'size'         => $size,
		'width'        => $width,
		'rear_size'    => $rear_size,
		'rear_width'   => $rear_width,
		// In-store fitting: chosen in the builder, charged at checkout (Collect only).
		'fitting'      => ! empty( $raw['fitting'] ),
		'style'        => $style,
		'accent'       => $accent,
		'badge'        => $badge,
		'border'       => $border,
		'plates'       => $plates,
		'accessories'  => array_keys( $accessories ),
		'road_legal'   => $c['styles'][ $style ]['road_legal'] && $c['borders'][ $border ]['road_legal'],
	);
}

/**
 * Price a clean configuration. Mirrors assets/js/pricing.js.
 *
 * Standard sizes: style price for the pair or one plate, plus a surcharge per plate
 * for non-standard sizes. Motorbike and trailer plates: one fixed price.
 * Badge, border and extras are charged once.
 *
 * @return array{lines: array<int, array{label: string, amount: float}>, total: float}
 */
function pbb_calculate_price( array $config, array $pricing = null ) {
	$p   = $pricing ? $pricing : pbb_get_pricing();
	$c   = pbb_catalog();
	$num = function ( $group, $key ) use ( $p ) {
		return isset( $p[ $group ][ $key ] ) ? (float) $p[ $group ][ $key ] : 0.0;
	};

	$size   = $c['sizes'][ $config['size'] ];
	$flat   = isset( $p['flat'][ $config['size'] ] );
	$plates = 'pair' === $config['plates'] ? 2 : 1;
	$lines  = array();

	if ( $flat ) {
		$lines[] = array( 'label' => $size['label'] . ' plate', 'amount' => $num( 'flat', $config['size'] ) );
	} else {
		$style   = $c['styles'][ $config['style'] ]['label'];
		$lines[] = 2 === $plates
			? array( 'label' => $style . ' plates, front & rear', 'amount' => $num( 'style_pair', $config['style'] ) )
			: array( 'label' => $style . ' plate, ' . strtolower( $c['plates'][ $config['plates'] ] ), 'amount' => $num( 'style_single', $config['style'] ) );
		// Each plate's own size surcharge (front and rear can differ).
		$rear_id = pbb_rear_size( $config );
		$front   = 'rear' === $config['plates'] ? 0.0 : $num( 'size', $config['size'] );
		$rear    = 'front' === $config['plates'] ? 0.0 : $num( 'size', $rear_id );
		if ( 2 === $plates && $rear_id === $config['size'] ) {
			if ( $front + $rear ) {
				$lines[] = array( 'label' => $size['label'] . ' size × 2', 'amount' => $front + $rear );
			}
		} else {
			if ( $front ) {
				$lines[] = array( 'label' => 'Front: ' . $size['label'] . ' size', 'amount' => $front );
			}
			if ( $rear ) {
				$lines[] = array( 'label' => 'Rear: ' . $c['sizes'][ $rear_id ]['label'] . ' size', 'amount' => $rear );
			}
		}
	}
	if ( 'none' !== $config['badge'] ) {
		$badge   = $num( 'badge', $config['badge'] );
		$lines[] = array( 'label' => $c['badges'][ $config['badge'] ]['label'], 'amount' => $flat ? $badge / 2 : $badge );
	}
	if ( 'none' !== $config['border'] ) {
		$lines[] = array( 'label' => $c['borders'][ $config['border'] ]['label'] . ' border', 'amount' => $num( 'border', $config['border'] ) );
	}
	foreach ( $config['accessories'] as $a ) {
		$lines[] = array( 'label' => $c['accessories'][ $a ], 'amount' => $num( 'accessories', $a ) );
	}

	$total = 0.0;
	foreach ( $lines as $i => $line ) {
		$lines[ $i ]['amount'] = round( $line['amount'], 2 );
		$total                += $line['amount'];
	}
	return array(
		'lines' => $lines,
		'total' => round( max( 0, $total ), 2 ),
	);
}

/**
 * Human-readable lines for the basket, emails and the order screen.
 *
 * @return array<string,string> label => value
 */
function pbb_describe_config( array $config ) {
	$c       = pbb_catalog();
	$size_of = function ( $id, $width ) use ( $c ) {
		$s  = $c['sizes'][ $id ];
		$mm = $width ? $width . ' × 111' : $s['mm'];
		return $s['label'] . ( $mm ? ' (' . $mm . ' mm)' : '' );
	};
	$rear_id = pbb_rear_size( $config );
	$front   = $size_of( $config['size'], $config['width'] );
	// Baskets from before 1.9 have no rear_width: the one size was both plates.
	$rear    = $size_of( $rear_id, array_key_exists( 'rear_width', $config ) ? $config['rear_width'] : $config['width'] );
	if ( 'front' === $config['plates'] ) {
		$size_text = $front;
	} elseif ( 'rear' === $config['plates'] ) {
		$size_text = $rear;
	} else {
		$size_text = $front === $rear ? $front : sprintf( 'Front %1$s, Rear %2$s', $front, $rear );
	}

	$style = $c['styles'][ $config['style'] ]['label'];
	if ( $config['accent'] ) {
		$style .= ' (' . $c['accents'][ $config['accent'] ] . ')';
	}

	$lines = array(
		__( 'Registration', '3d-plate-builder' ) => $config['registration'],
		__( 'Plates', '3d-plate-builder' )       => $c['plates'][ $config['plates'] ],
		__( 'Size', '3d-plate-builder' )         => $size_text,
		__( 'Style', '3d-plate-builder' )        => $style,
	);
	if ( 'none' !== $config['badge'] ) {
		$lines[ __( 'Badge', '3d-plate-builder' ) ] = $c['badges'][ $config['badge'] ]['label'];
	}
	if ( 'none' !== $config['border'] ) {
		$lines[ __( 'Border', '3d-plate-builder' ) ] = $c['borders'][ $config['border'] ]['label'];
	}
	if ( $config['accessories'] ) {
		$names = array();
		foreach ( $config['accessories'] as $a ) {
			$names[] = $c['accessories'][ $a ];
		}
		$lines[ __( 'Extras', '3d-plate-builder' ) ] = implode( ', ', $names );
	}
	$lines[ __( 'Use', '3d-plate-builder' ) ] = $config['road_legal']
		? __( 'Road legal', '3d-plate-builder' )
		: __( 'Show plate (not for road use)', '3d-plate-builder' );

	return $lines;
}
