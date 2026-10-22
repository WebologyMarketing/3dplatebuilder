<?php
/**
 * Title: Pro: home page
 * Slug: 3dpb-shop/pro-home
 * Categories: 3dpb
 * Description: The Pro home page: a hero, the seven styles, car view, why us and how ordering works.
 * Template Types: front-page
 * Inserter: true
 *
 * @package 3dpb-shop
 */

$pbb_img = get_stylesheet_directory_uri() . '/assets/img/';
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"2.5rem"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center">
<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">See your plates before you buy</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"pbb-muted","fontSize":"large"} -->
<p class="pbb-muted has-large-font-size">Type your reg and turn it in 3D. Pick a style, size and badge, see it on a car, then collect today or we'll post them.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/design-your-plates/">Design your plates</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#styles">Browse styles</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- wp:paragraph {"className":"pbb-muted","fontSize":"small"} -->
<p class="pbb-muted has-small-font-size">From [pbb_from] a pair · Made to BS AU 145e</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"full","className":"pbb-media"} -->
<figure class="wp-block-image size-full pbb-media"><img src="<?php echo esc_url( $pbb_img . 'shop-builder.webp' ); ?>" alt="The 3D plate builder showing a front plate"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"anchor":"styles","align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div id="styles" class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
<!-- wp:heading -->
<h2 class="wp-block-heading">Choose your style</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"pbb-muted"} -->
<p class="pbb-muted">Prices for a pair, standard size.</p>
<!-- /wp:paragraph -->
<!-- wp:group {"layout":{"type":"grid","minimumColumnWidth":"14rem"},"style":{"spacing":{"blockGap":"0.875rem"}}} -->
<div class="wp-block-group">
<?php
$pbb_styles = array(
	'printed'      => array( 'Printed', '' ),
	'gel-3d'       => array( '3D Gel', 'is-gel' ),
	'acrylic-4d-3' => array( '4D 3mm', 'is-white is-raised' ),
	'acrylic-4d-6' => array( '4D 6mm', 'is-raised' ),
	'gel-4d-3'     => array( '4D Gel 3mm', 'is-raised' ),
	'gel-4d-6'     => array( '4D Gel 6mm', 'is-white is-raised' ),
	'retro'        => array( 'Retro', 'is-retro' ),
);
foreach ( $pbb_styles as $pbb_id => list( $pbb_name, $pbb_look ) ) :
	$pbb_slug = sanitize_title( $pbb_name ) . '-number-plates';
	?>
<!-- wp:group {"className":"pbb-style pbb-style-<?php echo esc_attr( $pbb_id ); ?>","layout":{"type":"default"}} -->
<div class="wp-block-group pbb-style pbb-style-<?php echo esc_attr( $pbb_id ); ?>"><!-- wp:paragraph {"className":"pbb-plate <?php echo esc_attr( $pbb_look ); ?>"} -->
<p class="pbb-plate <?php echo esc_attr( $pbb_look ); ?>"><?php echo esc_html( strtoupper( $pbb_name ) ); ?></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/<?php echo esc_attr( $pbb_slug ); ?>/"><?php echo esc_html( $pbb_name ); ?></a></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>From [pbb_from style="<?php echo esc_attr( $pbb_id ); ?>"]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endforeach; ?>
<!-- wp:group {"className":"pbb-style is-dark","layout":{"type":"default"}} -->
<div class="wp-block-group pbb-style is-dark"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><a href="/design-your-plates/">Not sure?</a></h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Try every style on your own reg in the builder.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"2.5rem"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center">
<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"full","className":"pbb-media"} -->
<figure class="wp-block-image size-full pbb-media"><img src="<?php echo esc_url( $pbb_img . 'car-view.webp' ); ?>" alt="Car view: a car wearing the plates"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading -->
<h2 class="wp-block-heading">See them on a car</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"pbb-muted","fontSize":"large"} -->
<p class="pbb-muted has-large-font-size">Switch to car view to see your plates fitted, front and back. Change the paint colour to match yours.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/design-your-plates/">Try car view</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
<!-- wp:heading -->
<h2 class="wp-block-heading">Why buy from us</h2>
<!-- /wp:heading -->
<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column {"className":"pbb-card"} -->
<div class="wp-block-column pbb-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Made here</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Your workshop, how long you've made plates and what you're known for.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column {"className":"pbb-card"} -->
<div class="wp-block-column pbb-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Road legal</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Made to BS AU 145e by a registered number plate supplier, with our name and postcode on every plate.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column {"className":"pbb-card"} -->
<div class="wp-block-column pbb-card"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Collect or delivery</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Collect today, fitted while you wait, or tracked UK delivery.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"3dpb-shop/how-it-works"} /-->

<!-- wp:pattern {"slug":"3dpb-shop/questions"} /-->

<!-- wp:pattern {"slug":"3dpb-shop/find-us"} /-->
