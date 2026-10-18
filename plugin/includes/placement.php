<?php
/**
 * Where the builder shows up around the site, beyond shop product pages:
 *   - a "Design Your Plates" page, created on first admin visit;
 *   - the full builder on featured pages (home and Hounslow), after their opening section;
 *   - a "3D Plate Builder" item in the main menu;
 *   - a floating "Design in 3D" button on other pages.
 *
 * @package PlateBoss
 */

defined( 'ABSPATH' ) || exit;

/** The builder page, created (published, with the shortcode) the first time an admin loads the dashboard. */
function pbb_builder_page_id() {
	$id = (int) get_option( 'pbb_builder_page_id' );
	return $id && 'publish' === get_post_status( $id ) ? $id : 0;
}

add_action(
	'admin_init',
	function () {
		if ( pbb_builder_page_id() || ! current_user_can( 'publish_pages' ) ) {
			return;
		}
		$existing = get_page_by_path( 'design-your-plates' );
		$id       = $existing ? $existing->ID : wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Design Your Plates', 'plateboss-builder' ),
				'post_name'    => 'design-your-plates',
				'post_content' => '[plateboss_builder]',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_option( 'pbb_builder_page_id', (int) $id, false );
		}
	}
);

/** Link to the builder, with an optional registration to start from. */
function pbb_builder_url( $registration = '' ) {
	$id  = pbb_builder_page_id();
	$url = $id ? get_permalink( $id ) : home_url( '/design-your-plates/' );
	return $registration ? add_query_arg( 'reg', rawurlencode( $registration ), $url ) : $url;
}

/**
 * Landing pages that show the full builder after their opening section, each starting on its own
 * style or size, with the words for its heading. Filter `pbb_landing_pages`.
 */
function pbb_landing_pages() {
	$pages = array(
		(int) get_option( 'page_on_front' ) => array(),
		// Location pages.
		29573 => array(), // Hounslow
		34376 => array(), // London
		34392 => array(), // Manchester
		34414 => array(), // Birmingham
		// Style pages.
		32138 => array( 'style' => 'gel-3d', 'name' => '3D gel' ),
		32194 => array( 'style' => 'acrylic-4d-3', 'name' => '4D' ),
		32207 => array( 'style' => 'retro', 'name' => 'retro' ),
		32244 => array( 'style' => 'printed', 'name' => 'replacement' ),
		32258 => array( 'size' => 'short', 'style' => 'acrylic-4d-3', 'name' => 'short' ),
		32382 => array( 'badge' => 'ev', 'style' => 'acrylic-4d-3', 'name' => 'EV' ),
		32491 => array( 'size' => 'hex', 'style' => 'acrylic-4d-3', 'name' => 'hex' ),
	);
	return array_filter( (array) apply_filters( 'pbb_landing_pages', $pages ), 'is_array' );
}

/** Pages with the full builder in them (landing pages). Filter `pbb_featured_pages`. */
function pbb_featured_pages() {
	return array_filter( array_map( 'intval', (array) apply_filters( 'pbb_featured_pages', array_keys( pbb_landing_pages() ) ) ) );
}

function pbb_is_featured_page() {
	return is_page() && in_array( get_queried_object_id(), pbb_featured_pages(), true );
}

/**
 * How landing pages promote the builder: 'button' (a heading and Design in 3D button in the
 * page's hero, linking to the builder) or 'builder' (the full builder in the page). Filter `pbb_landing_mode`.
 */
function pbb_landing_mode() {
	return 'builder' === apply_filters( 'pbb_landing_mode', 'button' ) ? 'builder' : 'button';
}

/** A landing page that has the full builder in it. */
function pbb_page_has_builder() {
	return pbb_is_featured_page() && 'builder' === pbb_landing_mode();
}

/** Where this landing page's Design in 3D links go: the builder, on the page's style. */
function pbb_landing_link() {
	$preset = pbb_landing_pages()[ get_queried_object_id() ] ?? array();
	return pbb_builder_link( $preset );
}

/** Builder link for a category or ad, opening on a style/size/badge. */
function pbb_builder_link( array $preset = array() ) {
	return add_query_arg( array_intersect_key( $preset, array_flip( array( 'style', 'size', 'badge' ) ) ), pbb_builder_url() );
}

/**
 * Featured pages: the builder goes in after the page's first Elementor section (the top banner),
 * or at the top of the content on non-Elementor pages.
 */
add_filter(
	'the_content',
	function ( $content ) {
		static $done = false;
		if ( $done || is_admin() || ! in_the_loop() || ! is_main_query() || ! pbb_page_has_builder() ) {
			return $content;
		}
		$done = true;

		$preset = pbb_landing_pages()[ get_queried_object_id() ] ?? array();
		/* translators: %s: plate style, e.g. "4D" */
		$title = ! empty( $preset['name'] ) ? sprintf( __( 'Design your %s plates in 3D', 'plateboss-builder' ), $preset['name'] ) : __( 'Design your plates in 3D', 'plateboss-builder' );
		$atts  = array_merge( array( 'mobile' => 'launch', 'teaser' => true ), array_intersect_key( $preset, array_flip( array( 'style', 'size', 'badge' ) ) ) );

		ob_start();
		?>
		<section class="pbb-feature" id="design-your-plates">
			<div class="pbb-feature-head">
				<span class="pbb-new"><?php esc_html_e( 'New', 'plateboss-builder' ); ?></span>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php esc_html_e( 'Enter your reg, choose your style and see exactly what you\'re getting, then order and pay right here.', 'plateboss-builder' ); ?></p>
				<ul class="pbb-feature-points">
					<li><?php esc_html_e( 'See it in 3D, front and rear', 'plateboss-builder' ); ?></li>
					<li><?php esc_html_e( 'Legal spacing checked as you type', 'plateboss-builder' ); ?></li>
					<li><?php esc_html_e( 'Live price, Apple Pay & Google Pay', 'plateboss-builder' ); ?></li>
				</ul>
			</div>
			<?php echo pbb_shortcode( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template. ?>
		</section>
		<?php
		$block = ob_get_clean();

		// Elementor's top-level containers carry "e-parent"; slot in before the second one.
		if ( preg_match_all( '/<div[^>]+class="[^"]*\be-parent\b[^"]*"/', $content, $m, PREG_OFFSET_CAPTURE ) && count( $m[0] ) > 1 ) {
			$at = $m[0][1][1];
			return substr( $content, 0, $at ) . $block . substr( $content, $at );
		}
		return $block . $content;
	},
	999
);

/** Main menu: "3D Plate Builder" after the first item (Home). Filter `pbb_menu_slugs` for other menus. */
add_filter(
	'wp_nav_menu_objects',
	function ( $items, $args ) {
		$menu = isset( $args->menu ) ? wp_get_nav_menu_object( $args->menu ) : false;
		if ( ! $menu || ! in_array( $menu->slug, (array) apply_filters( 'pbb_menu_slugs', array( 'main-menu-left' ) ), true ) ) {
			return $items;
		}
		$item                   = new stdClass();
		$item->ID               = 999999001;
		$item->db_id            = 999999001;
		$item->menu_item_parent = 0;
		$item->object_id        = pbb_builder_page_id();
		$item->object           = 'page';
		$item->type             = 'custom';
		$item->title            = __( '3D Plate Builder', 'plateboss-builder' );
		$item->url              = pbb_builder_url();
		$item->target           = '';
		$item->attr_title       = '';
		$item->description      = '';
		$item->xfn              = '';
		$item->classes          = array( 'menu-item', 'menu-item-type-custom', 'pbb-menu-item' );
		$item->current          = is_page( pbb_builder_page_id() );
		if ( $item->current ) {
			$item->classes[] = 'current-menu-item';
		}
		$item->menu_order = 0;

		$top = array_values( array_filter( $items, fn ( $i ) => ! $i->menu_item_parent ) );
		$pos = 0;
		foreach ( $items as $i => $existing ) {
			if ( $top && $existing === $top[0] ) {
				$pos = $i;
				break;
			}
		}
		array_splice( $items, $pos + 1, 0, array( $item ) );
		foreach ( array_values( $items ) as $order => $i ) {
			$i->menu_order = $order + 1;
		}
		return $items;
	},
	10,
	2
);

/** Floating "Design in 3D" button: everywhere except where the builder already is, and basket/checkout/account. */
add_action(
	'wp_footer',
	function () {
		if ( is_admin() || ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) ) {
			return;
		}
		if ( is_page( pbb_builder_page_id() ) || pbb_page_has_builder() ) {
			return;
		}
		if ( function_exists( 'is_product' ) && is_product() && pbb_product_preset( get_queried_object_id() ) ) {
			return;
		}
		?>
		<a class="pbb-fab" href="<?php echo esc_url( pbb_builder_url() ); ?>">
			<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" d="M12 2.5l8.5 4.75v9.5L12 21.5l-8.5-4.75v-9.5zM12 12l8.5-4.75M12 12v9.5M12 12L3.5 7.25"/></svg>
			<span><?php esc_html_e( 'Design in 3D', 'plateboss-builder' ); ?></span>
		</a>
		<style>
			.pbb-fab{position:fixed;left:16px;bottom:calc(20px + env(safe-area-inset-bottom));z-index:9990;display:inline-flex;align-items:center;gap:8px;padding:12px 18px 12px 14px;border-radius:999px;background:#111418;color:#fff!important;font-size:15px;font-weight:700;line-height:1;text-decoration:none!important;box-shadow:0 8px 24px rgba(0,0,0,.25);transition:transform .2s ease}
			.pbb-fab svg{color:#f5c400}
			.pbb-fab:hover{transform:translateY(-2px)}
			@media (max-width:1024px){body.sticky-toolbar-on .pbb-fab{bottom:calc(72px + env(safe-area-inset-bottom))}}
			@media (max-width:420px){.pbb-fab{padding:11px 15px 11px 12px;font-size:14px}}
		</style>
		<?php
	}
);

/* -------------------------------------------------------------------------- */
/* Promotion: "New" strip across the top, and a band on category pages        */
/* -------------------------------------------------------------------------- */

/** Where the promos stay out of the way: basket, checkout, account and the builder page. */
function pbb_promo_hidden() {
	if ( is_admin() || ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) ) {
		return true;
	}
	return is_page( pbb_builder_page_id() ) || ! empty( $_GET['pbb_embed'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/** Category → where the builder should start. Filter `pbb_category_presets`. */
function pbb_category_presets() {
	return (array) apply_filters(
		'pbb_category_presets',
		array(
			'3d-gel-number-plates'   => array( 'style' => 'gel-3d', 'name' => '3D gel' ),
			'4d-gel-plates'          => array( 'style' => 'gel-4d-3', 'name' => '4D gel' ),
			'4d-plates'              => array( 'style' => 'acrylic-4d-3', 'name' => '4D' ),
			'ev-plates'              => array( 'badge' => 'ev', 'style' => 'acrylic-4d-3', 'name' => 'EV' ),
			'hex-plates'             => array( 'size' => 'hex', 'style' => 'acrylic-4d-3', 'name' => 'hex' ),
			'retro-number-plates'    => array( 'style' => 'retro', 'name' => 'retro' ),
			'short-number-plates'    => array( 'size' => 'short', 'style' => 'acrylic-4d-3', 'name' => 'short' ),
			'standard-number-plates' => array( 'style' => 'printed', 'name' => 'standard' ),
			'oversized-plates'       => array( 'style' => 'acrylic-4d-3', 'name' => 'oversized' ),
		)
	);
}

// Strip across the top of every other page.
add_action(
	'wp_body_open',
	function () {
		if ( pbb_promo_hidden() ) {
			return;
		}
		$href = pbb_page_has_builder() ? '#design-your-plates' : ( pbb_is_featured_page() ? pbb_landing_link() : pbb_builder_url() );
		?>
		<div class="pbb-strip" data-pbb-strip hidden>
			<a class="pbb-strip-link" href="<?php echo esc_url( $href ); ?>">
				<span class="pbb-strip-new"><?php esc_html_e( 'New', 'plateboss-builder' ); ?></span>
				<span class="pbb-strip-text"><?php esc_html_e( 'See your plates in 3D before you order', 'plateboss-builder' ); ?></span>
				<span class="pbb-strip-cta"><?php esc_html_e( 'Try the 3D builder', 'plateboss-builder' ); ?> &rarr;</span>
			</a>
			<button type="button" class="pbb-strip-close" data-pbb-strip-close aria-label="<?php esc_attr_e( 'Hide this message', 'plateboss-builder' ); ?>">&times;</button>
		</div>
		<script>
		(function () {
			var strip = document.querySelector('[data-pbb-strip]');
			var closed = false;
			try { closed = localStorage.getItem('pbb-strip-closed') === '1'; } catch (e) {}
			if (!strip || closed) return;
			strip.hidden = false;
			strip.querySelector('[data-pbb-strip-close]').addEventListener('click', function () {
				strip.hidden = true;
				try { localStorage.setItem('pbb-strip-closed', '1'); } catch (e) {}
			});
		})();
		</script>
		<?php
	}
);

// Category pages: a band above the products, opening the builder on that category's style.
add_action(
	'woocommerce_before_shop_loop',
	function () {
		if ( pbb_promo_hidden() || ! is_product_category() ) {
			return;
		}
		$term   = get_queried_object();
		$preset = pbb_category_presets()[ $term->slug ?? '' ] ?? null;
		if ( null === $preset ) {
			return;
		}
		/* translators: %s: plate style, e.g. "4D" */
		$title = sprintf( __( 'Design your %s plates in 3D', 'plateboss-builder' ), $preset['name'] );
		?>
		<a class="pbb-band" href="<?php echo esc_url( pbb_builder_link( $preset ) ); ?>">
			<span class="pbb-band-plate" aria-hidden="true">YOUR REG</span>
			<span class="pbb-band-copy">
				<span class="pbb-strip-new"><?php esc_html_e( 'New', 'plateboss-builder' ); ?></span>
				<strong><?php echo esc_html( $title ); ?></strong>
				<span><?php esc_html_e( 'Type your reg, turn it round, see every detail, then order and pay in the builder.', 'plateboss-builder' ); ?></span>
			</span>
			<span class="pbb-band-cta"><?php esc_html_e( 'Start designing', 'plateboss-builder' ); ?> &rarr;</span>
		</a>
		<?php
	},
	5
);

add_action(
	'wp_head',
	function () {
		if ( pbb_promo_hidden() ) {
			return;
		}
		?>
		<style id="pbb-promo-css">
			.pbb-strip{position:relative;z-index:100;display:flex;align-items:center;background:#0D0E10;color:#fff;font-size:14px;line-height:1.3}
			.pbb-strip[hidden]{display:none}
			.pbb-strip-link{flex:1;display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:6px 12px;padding:9px 44px 9px 16px;color:#fff!important;text-decoration:none!important}
			.pbb-strip-new{display:inline-block;padding:3px 8px;border-radius:999px;background:#F7D333;color:#0D0E10;font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
			.pbb-strip-text{font-weight:500}
			.pbb-strip-cta{font-weight:800;color:#F7D333;text-decoration:underline;text-underline-offset:3px}
			.pbb-strip-close{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:36px;height:36px;border:0;background:none;color:#cfcfcf;font-size:22px;line-height:1;cursor:pointer}
			.pbb-hero-cta{width:100%;max-width:520px;margin:22px 0 4px;padding:18px 20px;border-radius:16px;background:rgba(13,14,16,.78);border:1px solid rgba(247,211,51,.45);color:#fff;text-align:left;box-shadow:0 18px 40px -20px rgba(0,0,0,.8);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}
			.pbb-hero-cta.is-centred{margin-left:auto;margin-right:auto;text-align:center}
			.pbb-hero-cta-title{margin:10px 0 0!important;color:#fff!important;font-size:24px!important;font-weight:800!important;line-height:1.15!important}
			.pbb-hero-cta-text{margin:6px 0 0!important;color:#d9d7cf!important;font-size:15px!important;line-height:1.45!important}
			.pbb-hero-cta-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;margin-top:16px;min-height:54px;padding:0 26px;border-radius:12px;background:#F7D333;color:#0D0E10!important;font-weight:800;font-size:17px;letter-spacing:.05em;text-transform:uppercase;text-decoration:none!important;box-shadow:0 8px 20px -8px rgba(247,211,51,.7);transition:transform .15s ease}
			.pbb-hero-cta-btn:hover{transform:translateY(-1px);background:#ffe066}
			@media (max-width:640px){.pbb-hero-cta{padding:16px}.pbb-hero-cta-title{font-size:20px!important}.pbb-hero-cta-btn{width:100%}}
			.pbb-band{display:flex;align-items:center;gap:18px;margin:0 0 24px;padding:16px 20px;border-radius:16px;background:#0D0E10;color:#fff!important;text-decoration:none!important;box-shadow:0 10px 30px -18px rgba(0,0,0,.6)}
			.pbb-band:hover .pbb-band-cta{background:#ffe066}
			.pbb-band-plate{flex:none;padding:8px 14px;border-radius:6px;background:#F9D71C;color:#111;font-weight:900;font-size:22px;letter-spacing:2px;transform:perspective(400px) rotateY(-18deg) rotateX(8deg);box-shadow:-6px 10px 18px rgba(0,0,0,.5)}
			.pbb-band-copy{flex:1;display:flex;flex-direction:column;gap:4px;font-size:14px;color:#d9d7cf}
			.pbb-band-copy strong{font-size:20px;line-height:1.15;color:#fff}
			.pbb-band-copy .pbb-strip-new{align-self:flex-start}
			.pbb-band-cta{flex:none;padding:12px 18px;border-radius:12px;background:#F7D333;color:#0D0E10;font-weight:800;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}
			@media (max-width:640px){
				.pbb-strip{font-size:13px}
				.pbb-strip-text{display:none}
				.pbb-band{flex-wrap:wrap;gap:12px;padding:14px}
				.pbb-band-plate{font-size:18px}
				.pbb-band-copy strong{font-size:17px}
				.pbb-band-cta{width:100%;text-align:center}
			}
		</style>
		<?php
	}
);

/**
 * Landing pages (button mode): a "New" heading and Design in 3D button in the page's hero,
 * right under the hero's own buttons. The hero is built in Elementor, so this slots in from the
 * browser: after the last button in the first section that has one.
 */
add_action(
	'wp_footer',
	function () {
		if ( pbb_promo_hidden() || ! pbb_is_featured_page() || pbb_page_has_builder() ) {
			return;
		}
		$preset = pbb_landing_pages()[ get_queried_object_id() ] ?? array();
		/* translators: %s: plate style, e.g. "4D" */
		$title = ! empty( $preset['name'] ) ? sprintf( __( 'See your %s plates in 3D before you order', 'plateboss-builder' ), $preset['name'] ) : __( 'See your plates in 3D before you order', 'plateboss-builder' );
		?>
		<template id="pbb-hero-cta">
			<div class="pbb-hero-cta">
				<span class="pbb-strip-new"><?php esc_html_e( 'New', 'plateboss-builder' ); ?></span>
				<p class="pbb-hero-cta-title"><?php echo esc_html( $title ); ?></p>
				<p class="pbb-hero-cta-text"><?php esc_html_e( 'Type your reg, pick your style and turn it round. Then order and pay right there.', 'plateboss-builder' ); ?></p>
				<a class="pbb-hero-cta-btn" href="<?php echo esc_url( pbb_landing_link() ); ?>">
					<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round" d="M12 2.5l8.5 4.75v9.5L12 21.5l-8.5-4.75v-9.5zM12 12l8.5-4.75M12 12v9.5M12 12L3.5 7.25"/></svg>
					<?php esc_html_e( 'Design in 3D', 'plateboss-builder' ); ?> <span aria-hidden="true">&rarr;</span>
				</a>
			</div>
		</template>
		<script>
		(function () {
			var tpl = document.getElementById('pbb-hero-cta');
			if (!tpl) return;
			var sections = document.querySelectorAll('.e-parent, .elementor-section.elementor-top-section');
			var hero = null;
			for (var i = 0; i < sections.length && !hero; i++) {
				if (sections[i].querySelector('.elementor-widget-wd_button, .elementor-widget-button')) hero = sections[i];
			}
			if (!hero) return;
			var buttons = hero.querySelectorAll('.elementor-widget-wd_button, .elementor-widget-button');
			var after = buttons[buttons.length - 1];
			// Centred hero (the buttons sit in the middle): centre the block too.
			var br = buttons[0].getBoundingClientRect(), hr = hero.getBoundingClientRect();
			var centred = Math.abs((br.left + br.right) / 2 - (hr.left + hr.right) / 2) < 60;
			// Buttons often sit side by side in a row: go up to the row so the block starts its own line.
			while (after.parentElement && after.parentElement !== hero) {
				var ps = getComputedStyle(after.parentElement);
				if (ps.display.indexOf('flex') === -1 || ps.flexDirection.indexOf('row') !== 0) break;
				after = after.parentElement;
			}
			var cta = tpl.content.firstElementChild.cloneNode(true);
			if (centred) cta.classList.add('is-centred');
			after.after(cta);
		})();
		</script>
		<?php
	},
	5
);
