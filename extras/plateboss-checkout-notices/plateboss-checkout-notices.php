<?php
/**
 * Plugin Name: PlateBoss Checkout Notices Fix
 * Description: Shows classic-checkout errors again when ShopBuilder removes them. ShopBuilder's checkout_error handler deletes WooCommerce's notice group and moves it into a ShopBuilder Notice widget; the checkout template has no such widget, so customers saw nothing.
 * Version: 1.0.0
 * Author: PlateBoss
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}

	$js = <<<'JS'
jQuery(function ($) {
	$(document.body).on('checkout_error', function (event, errorMessage) {
		if (!errorMessage) {
			return;
		}
		// Run after every other checkout_error handler (ShopBuilder's included).
		setTimeout(function () {
			if ($('.woocommerce-NoticeGroup-checkout:visible').length) {
				return; // Something (WooCommerce or a ShopBuilder Notice widget) is showing it.
			}
			var $form = $('form.checkout').first();
			if (!$form.length) {
				return;
			}
			$('.pb-checkout-notices').remove();
			// ShopBuilder's CSS hides a notice group placed inside its checkout form, so put it just above.
			var $group = $('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout pb-checkout-notices" role="alert"></div>').html(errorMessage);
			$form.before($group);
			$('html, body').animate({ scrollTop: Math.max($group.offset().top - 120, 0) }, 600);
		}, 0);
	});
});
JS;

	wp_add_inline_script( 'wc-checkout', $js );
}, 20 );
