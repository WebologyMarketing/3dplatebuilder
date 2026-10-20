<?php
/**
 * Plugin Name:       3D Plate Builder
 * Description:       Real-time 3D/4D number plate customiser for WooCommerce. Use the [plate_builder] shortcode.
 * Requires Plugins:  woocommerce
 * Version:           1.11.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            3D Plate Builder
 * Author URI:        https://3dplatebuilder.com
 * Text Domain:       3d-plate-builder
 */

defined( 'ABSPATH' ) || exit;

define( 'PBB_VERSION', '1.11.0' );
define( 'PBB_URL', plugin_dir_url( __FILE__ ) );
define( 'PBB_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Three.js version. Pinned so a CDN update can never change the render.
 * For production, vendor these files into assets/vendor/ and point the URLs there.
 */
define( 'PBB_THREE_VERSION', '0.170.0' );

require_once PBB_PATH . 'includes/shop.php';
require_once PBB_PATH . 'includes/catalog.php';
require_once PBB_PATH . 'includes/placement.php';
require_once PBB_PATH . 'includes/rocket.php';
require_once PBB_PATH . 'includes/checkout-embed.php';
require_once PBB_PATH . 'includes/portal-sync.php';

// Basket, checkout and the pricing screen need WooCommerce.
add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( 'WooCommerce' ) ) {
			require_once PBB_PATH . 'includes/cart.php';
			require_once PBB_PATH . 'includes/settings.php';
			require_once PBB_PATH . 'includes/documents.php';
			require_once PBB_PATH . 'includes/documents-admin.php';
		}
	}
);

/**
 * Register the ES modules. WordPress 6.5+ prints an import map for these,
 * so `import * as THREE from 'three'` works in our own modules.
 */
function pbb_register_assets() {
	$three = 'https://cdn.jsdelivr.net/npm/three@' . PBB_THREE_VERSION;

	wp_register_script_module( 'three', $three . '/build/three.module.min.js', array(), null );
	wp_register_script_module( 'three/addons/controls/OrbitControls.js', $three . '/examples/jsm/controls/OrbitControls.js', array( 'three' ), null );
	wp_register_script_module( 'three/addons/utils/BufferGeometryUtils.js', $three . '/examples/jsm/utils/BufferGeometryUtils.js', array( 'three' ), null );
	// Only fetched when a customer picks a purchased car model in "On a car".
	wp_register_script_module( 'three/addons/loaders/GLTFLoader.js', $three . '/examples/jsm/loaders/GLTFLoader.js', array( 'three', 'three/addons/utils/BufferGeometryUtils.js' ), null );

	// main.js imports these by relative path (./steps.js), which carries no version, so browsers
	// can keep an old copy after an update. Each one goes in the import map under its plain URL,
	// mapped to the URL with ?ver=, so an update always loads the matching files.
	$parts = array( 'basket', 'builder-ui', 'car-model', 'checkout', 'plate-badges', 'plate-font', 'plate-layout', 'plate-model', 'plate-sizes', 'plate-studio', 'plate-styles', 'plate-text', 'plate-viewport', 'pricing', 'product-mode', 'steps' );
	$deps  = array(
		'three',
		'three/addons/controls/OrbitControls.js',
		'three/addons/utils/BufferGeometryUtils.js',
		array(
			'id'     => 'three/addons/loaders/GLTFLoader.js',
			'import' => 'dynamic',
		),
	);
	foreach ( $parts as $part ) {
		$url = PBB_URL . 'assets/js/' . $part . '.js';
		wp_register_script_module( $url, $url, array(), PBB_VERSION );
		$deps[] = $url;
	}

	wp_register_script_module( '3d-plate-builder', PBB_URL . 'assets/js/main.js', $deps, PBB_VERSION );

	wp_register_style( '3d-plate-builder', PBB_URL . 'assets/css/builder.css', array(), PBB_VERSION );
	wp_add_inline_style( '3d-plate-builder', pbb_shop_css_vars() );
}
add_action( 'init', 'pbb_register_assets' );

/**
 * Enqueue early (in wp_head) so the import map is printed before any module runs.
 * Shortcodes render too late for that on classic themes, so we detect the shortcode here.
 */
function pbb_maybe_enqueue() {
	$post    = get_post();
	$enqueue = $post && has_shortcode( $post->post_content, 'plate_builder' );

	// Shop products that use the builder (see pbb_product_presets()).
	if ( ! $enqueue && function_exists( 'is_product' ) && is_product() ) {
		$enqueue = (bool) pbb_product_preset( get_queried_object_id() );
	}
	// Featured landing pages show it after their opening section (includes/placement.php).
	$enqueue = $enqueue || pbb_page_has_builder();

	/** Force-load on pages built with page builders that hide shortcodes from post_content. */
	if ( apply_filters( 'pbb_should_enqueue', $enqueue ) ) {
		wp_enqueue_script_module( '3d-plate-builder' );
		wp_enqueue_style( '3d-plate-builder' );
	}
}
add_action( 'wp_enqueue_scripts', 'pbb_maybe_enqueue' );

/**
 * Legal footer details (BS AU 145e requires the supplier's name and postcode on every plate).
 * Set in the shop config (includes/shop.php); filter `pbb_supplier_details` still runs on top.
 */
function pbb_supplier_details() {
	$s = pbb_shop_supplier();
	return apply_filters(
		'pbb_supplier_details',
		array(
			'supplier' => $s['supplier'],
			'postcode' => $s['postcode'],
			'standard' => (string) pbb_shop( 'standard' ),
			'logo'     => esc_url_raw( (string) pbb_shop( 'logo' ) ),
		)
	);
}

/**
 * [plate_builder side="front"]
 */
function pbb_shortcode( $atts ) {
	static $count = 0;
	$instance_id  = ++$count;

	$atts = shortcode_atts(
		array(
			'side'         => 'front',
			'registration' => '',
			'style'        => 'printed',
			'accent'       => '',
			'size'         => 'standard',
			'badge'        => 'none',
			'border'       => 'none',
			// Phones: "app" fills the screen like an app; "launch" shows a button that opens it
			// (product pages); "inline" keeps it in the page.
			'mobile'       => 'app',
			// Product pages: a "Design in 3D / Classic plate builder" switch drives this builder.
			'product_mode' => false,
			// Phones, with mobile="launch": show the 3D plate and reg box in the page as a teaser (landing pages).
			'teaser'       => false,
		),
		$atts,
		'plate_builder'
	);

	// Fallback in case pbb_maybe_enqueue() could not see the shortcode.
	wp_enqueue_script_module( '3d-plate-builder' );
	wp_enqueue_style( '3d-plate-builder' );

	// Allow ?reg=AB12CDE so "search a reg" forms and ads can deep-link into the builder.
	$registration = $atts['registration'];
	if ( isset( $_GET['reg'] ) && is_string( $_GET['reg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$registration = sanitize_text_field( wp_unslash( $_GET['reg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	$registration = substr( strtoupper( preg_replace( '/[^A-Za-z0-9 ]/', '', $registration ) ), 0, 8 );

	// The Design Your Plates page also takes ?style=, ?size= and ?badge=, so category pages and
	// ads can open it on a style ("Design your 4D plates in 3D"). Unknown values are ignored.
	if ( function_exists( 'pbb_builder_page_id' ) && is_page( pbb_builder_page_id() ) ) {
		foreach ( array( 'style', 'size', 'badge' ) as $key ) {
			if ( isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$atts[ $key ] = sanitize_key( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
	}

	// A preset the shop has switched off (in the portal) falls back to the default.
	foreach ( array( 'style' => 'styles', 'size' => 'sizes', 'badge' => 'badges', 'border' => 'borders' ) as $key => $group ) {
		$atts[ $key ] = sanitize_key( $atts[ $key ] );
		if ( ! pbb_offered( $group, $atts[ $key ] ) || ! isset( pbb_catalog()[ $group ][ $atts[ $key ] ] ) ) {
			$atts[ $key ] = array( 'style' => 'printed', 'size' => 'standard', 'badge' => 'none', 'border' => 'none' )[ $key ];
		}
	}

	$config = array_merge(
		pbb_supplier_details(),
		array(
			'side'         => 'rear' === $atts['side'] ? 'rear' : 'front',
			'registration' => $registration,
			'style'        => sanitize_key( $atts['style'] ),
			'accent'       => $atts['accent'] ? sanitize_key( $atts['accent'] ) : null,
			'size'         => sanitize_key( $atts['size'] ),
			'badge'        => sanitize_key( $atts['badge'] ),
			'border'       => sanitize_key( $atts['border'] ),
			/** Production artwork, e.g. array( 'uk' => 'https://…/union-flag.svg' ). */
			'flagArtwork'  => (object) apply_filters( 'pbb_flag_artwork', array() ),
			'fontUrl'      => PBB_URL . 'assets/fonts/charles-wright-bold.json?ver=' . PBB_VERSION,
			'assetsUrl'    => PBB_URL . 'assets/',
			// Motorbike, import and commercial trailer plates.
			'fonts'        => array( 'motorcycle' => PBB_URL . 'assets/fonts/charles-wright-singapore.json?ver=' . PBB_VERSION ),
			'pricing'      => pbb_get_pricing(),
			'accessories'  => pbb_active_accessories(),
			// What this shop sells (id lists per group); null = everything.
			'offer'        => pbb_offer(),
			'accessoryGroups' => array_values( pbb_catalog()['accessory_groups'] ),
			// Catalogue names, so the live price lines read exactly like the basket.
			'labels'       => pbb_catalog_labels(),
			'mobile'       => in_array( $atts['mobile'], array( 'inline', 'launch' ), true ) ? $atts['mobile'] : 'app',
			'productMode'  => (bool) $atts['product_mode'],
			'teaser'       => (bool) $atts['teaser'],
			'homeUrl'      => home_url( '/' ),
			// White-label wording and artwork (shop config).
			'fittingNote'  => (string) pbb_shop( 'fitting_note' ),
			'carBadge'     => esc_url_raw( (string) pbb_shop( 'car_badge' ) ),
			'backing'      => pbb_shop_backing(),
		)
	);

	if ( class_exists( 'WooCommerce' ) ) {
		$config['currency'] = get_woocommerce_currency();
		$config['cart']     = array(
			'endpoint' => WC_AJAX::get_endpoint( 'pbb_add_to_cart' ),
			'cartUrl'  => wc_get_cart_url(),
			'checkoutUrl' => wc_get_checkout_url(),
			'count'    => WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
		);
	}

	ob_start();
	include PBB_PATH . 'templates/builder.php';
	return ob_get_clean();
}
add_shortcode( 'plate_builder', 'pbb_shortcode' );

/**
 * Shop products: the builder takes the place of the Product Add-Ons form, starting on the
 * product's own style (see pbb_product_presets()). The form is hidden with CSS rather than
 * removed, so page-builder templates keep their layout and nothing else on the page changes.
 */
add_action(
	'woocommerce_before_add_to_cart_form',
	function () {
		global $product;
		static $done = false;
		if ( $done || ! is_product() || ! $product || $product->get_id() !== get_queried_object_id() ) {
			return;
		}
		$preset = pbb_product_preset( $product->get_id() );
		if ( ! $preset ) {
			return;
		}
		$done = true;
		?>
		<div class="pbb-modes" role="group" aria-label="<?php esc_attr_e( 'How would you like to order?', '3d-plate-builder' ); ?>" data-pbb-modes>
			<button type="button" data-pbb-mode="3d" aria-pressed="true">
				<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" d="M12 2.5l8.5 4.75v9.5L12 21.5l-8.5-4.75v-9.5zM12 12l8.5-4.75M12 12v9.5M12 12L3.5 7.25"/></svg>
				<?php esc_html_e( 'Design in 3D', '3d-plate-builder' ); ?>
			</button>
			<button type="button" data-pbb-mode="classic" aria-pressed="false"><?php esc_html_e( 'Classic plate builder', '3d-plate-builder' ); ?></button>
		</div>
		<?php
		echo pbb_shortcode( array_merge( $preset, array( 'mobile' => 'launch', 'product_mode' => true ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template.
	},
	5
);

add_filter(
	'body_class',
	function ( $classes ) {
		if ( function_exists( 'is_product' ) && is_product() && pbb_product_preset( get_queried_object_id() ) ) {
			$classes[] = 'pbb-product-page';
		}
		return $classes;
	}
);
