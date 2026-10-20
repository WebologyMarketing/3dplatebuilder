<?php
/**
 * Title: Pro: style page
 * Slug: 3dpb-shop/style-page
 * Categories: 3dpb
 * Description: A landing page for one character style, with the builder opened on it. Setup makes one for each style.
 * Inserter: true
 *
 * @package 3dpb-shop
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50)">
<!-- wp:paragraph {"className":"pbb-muted","fontSize":"small"} -->
<p class="pbb-muted has-small-font-size"><a href="/">Home</a> / <a href="/#styles">Number plates</a> / 4D Gel 3mm</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p><span class="pbb-pill">4D Gel 3mm plates</span></p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">4D Gel 3mm number plates</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"pbb-muted","fontSize":"large"} -->
<p class="pbb-muted has-large-font-size">Design yours below: the builder opens with 4D Gel 3mm already picked. From [pbb_from style="gel-4d-3"] a pair.</p>
<!-- /wp:paragraph -->
<!-- wp:shortcode -->
[plate_builder style="gel-4d-3"]
<!-- /wp:shortcode -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"2.5rem"}}}} -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading -->
<h2 class="wp-block-heading">What makes them 4D Gel 3mm</h2>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>How you make them, the materials and finish, and why customers choose them.</p>
<!-- /wp:paragraph -->
<!-- wp:paragraph -->
<p>Every plate is made to BS AU 145e, with our supplier name and postcode.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"sizeSlug":"full","className":"pbb-media"} -->
<figure class="wp-block-image size-full pbb-media"><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/shop-builder.webp' ); ?>" alt="Replace with your photo of these plates"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1180px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
<!-- wp:heading -->
<h2 class="wp-block-heading">Other styles</h2>
<!-- /wp:heading -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline pbb-style-printed"} -->
<div class="wp-block-button is-style-outline pbb-style-printed"><a class="wp-block-button__link wp-element-button" href="/printed-number-plates/">Printed</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline pbb-style-gel-3d"} -->
<div class="wp-block-button is-style-outline pbb-style-gel-3d"><a class="wp-block-button__link wp-element-button" href="/3d-gel-number-plates/">3D Gel</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline pbb-style-acrylic-4d-3"} -->
<div class="wp-block-button is-style-outline pbb-style-acrylic-4d-3"><a class="wp-block-button__link wp-element-button" href="/4d-3mm-number-plates/">4D 3mm</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline pbb-style-acrylic-4d-6"} -->
<div class="wp-block-button is-style-outline pbb-style-acrylic-4d-6"><a class="wp-block-button__link wp-element-button" href="/4d-6mm-number-plates/">4D 6mm</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline pbb-style-gel-4d-3"} -->
<div class="wp-block-button is-style-outline pbb-style-gel-4d-3"><a class="wp-block-button__link wp-element-button" href="/4d-gel-3mm-number-plates/">4D Gel 3mm</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline pbb-style-gel-4d-6"} -->
<div class="wp-block-button is-style-outline pbb-style-gel-4d-6"><a class="wp-block-button__link wp-element-button" href="/4d-gel-6mm-number-plates/">4D Gel 6mm</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline pbb-style-retro"} -->
<div class="wp-block-button is-style-outline pbb-style-retro"><a class="wp-block-button__link wp-element-button" href="/retro-number-plates/">Retro</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"3dpb-shop/questions"} /-->
