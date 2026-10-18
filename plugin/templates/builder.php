<?php
/**
 * Builder markup. Variables in scope: $config (array).
 *
 * @package PlateBuilder
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="pbb-builder<?php echo 'launch' === $config['mobile'] ? ' is-launch' : ''; ?><?php echo $config['teaser'] ? ' is-teaser' : ''; ?>" data-pbb-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
	<?php if ( 'launch' === $config['mobile'] ) : ?>
		<!-- Phones on product pages: one button that opens the builder full screen. -->
		<div class="pbb-launcher">
			<button type="button" class="pbb-launch" data-pbb-launch>
				<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" d="M12 2.5l8.5 4.75v9.5L12 21.5l-8.5-4.75v-9.5zM12 12l8.5-4.75M12 12v9.5M12 12L3.5 7.25"/></svg>
				<span>
					<?php if ( $config['teaser'] ) : ?>
						<strong><?php esc_html_e( 'Choose your style & order', 'plateboss-builder' ); ?></strong>
						<small><?php esc_html_e( 'Styles, sizes, badges and extras, priced as you go.', 'plateboss-builder' ); ?></small>
					<?php else : ?>
						<strong><?php esc_html_e( 'Design your plates in 3D', 'plateboss-builder' ); ?></strong>
						<small><?php esc_html_e( 'Enter your reg, pick your options and see them before you buy.', 'plateboss-builder' ); ?></small>
					<?php endif; ?>
				</span>
			</button>
		</div>
	<?php endif; ?>

	<!-- Phones only: the builder fills the screen like an app (steps.js adds .is-app). -->
	<header class="pbb-appbar">
		<a class="pbb-app-btn" href="<?php echo esc_url( $config['homeUrl'] ); ?>" data-pbb-exit>
			<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" d="M15 5l-7 7 7 7"/></svg>
			<span class="pbb-sr-only"><?php esc_html_e( 'Leave the builder', 'plateboss-builder' ); ?></span>
		</a>
		<img class="pbb-app-logo" src="<?php echo esc_url( $config['logo'] ); ?>" alt="<?php echo esc_attr( $config['supplier'] ); ?>">
		<a class="pbb-app-btn pbb-app-cart" href="<?php echo esc_url( isset( $config['cart']['cartUrl'] ) ? $config['cart']['cartUrl'] : $config['homeUrl'] ); ?>">
			<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M6 8h12l-1 12H7L6 8zM9 8V6a3 3 0 0 1 6 0v2"/></svg>
			<span class="pbb-sr-only"><?php esc_html_e( 'Basket', 'plateboss-builder' ); ?></span>
			<span class="pbb-app-count" data-pbb-cart-count><?php echo isset( $config['cart']['count'] ) && $config['cart']['count'] ? (int) $config['cart']['count'] : ''; ?></span>
		</a>
	</header>

	<div class="pbb-stage">
		<div class="pbb-viewport" data-pbb-viewport aria-label="<?php esc_attr_e( '3D number plate preview', 'plateboss-builder' ); ?>" role="img"></div>

		<div class="pbb-loader" aria-hidden="true"><span></span></div>

		<p class="pbb-hint"
			data-pbb-touch-hint="<?php esc_attr_e( 'Drag to turn · Pinch or double-tap to zoom', 'plateboss-builder' ); ?>"
			<?php /* translators: %s: Ctrl or ⌘ */ ?>
			data-pbb-wheel-hint="<?php esc_attr_e( 'Click the plate or hold %s to zoom', 'plateboss-builder' ); ?>"
		><?php esc_html_e( 'Drag to turn · Double-click to zoom', 'plateboss-builder' ); ?></p>

		<div class="pbb-toolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Preview controls', 'plateboss-builder' ); ?>">
			<div class="pbb-segmented" role="group" aria-label="<?php esc_attr_e( 'Plate side', 'plateboss-builder' ); ?>">
				<button type="button" data-pbb-side="front" aria-pressed="<?php echo 'front' === $config['side'] ? 'true' : 'false'; ?>"><?php esc_html_e( 'Front', 'plateboss-builder' ); ?></button>
				<button type="button" data-pbb-side="rear" aria-pressed="<?php echo 'rear' === $config['side'] ? 'true' : 'false'; ?>"><?php esc_html_e( 'Rear', 'plateboss-builder' ); ?></button>
			</div>
			<button type="button" class="pbb-view-btn" data-pbb-view aria-pressed="false" title="<?php esc_attr_e( 'See it on a car', 'plateboss-builder' ); ?>">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M3 15.5V13l2-4.5A2 2 0 0 1 6.8 7.3h10.4A2 2 0 0 1 19 8.5L21 13v2.5a1 1 0 0 1-1 1h-1M5 16.5H4a1 1 0 0 1-1-1M9 16.5h6M3.5 12.5h17"/><circle cx="7" cy="16.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17" cy="16.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>
				<span><?php esc_html_e( 'Car', 'plateboss-builder' ); ?></span>
			</button>
			<button type="button" class="pbb-icon-btn" data-pbb-action="reset" title="<?php esc_attr_e( 'Reset view', 'plateboss-builder' ); ?>">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/></svg>
				<span class="pbb-sr-only"><?php esc_html_e( 'Reset view', 'plateboss-builder' ); ?></span>
			</button>
		</div>

		<div class="pbb-carbar" data-pbb-carbar hidden>
			<label class="pbb-sr-only" for="pbb-car-<?php echo esc_attr( $instance_id ); ?>"><?php esc_html_e( 'Car', 'plateboss-builder' ); ?></label>
			<select id="pbb-car-<?php echo esc_attr( $instance_id ); ?>" class="pbb-car-select" data-pbb-car></select>
			<div class="pbb-paints" role="radiogroup" aria-label="<?php esc_attr_e( 'Paint colour', 'plateboss-builder' ); ?>" data-pbb-paints></div>
		</div>

		<p class="pbb-fallback"><?php esc_html_e( 'Your browser could not start the 3D preview. Please update your browser or enable hardware acceleration.', 'plateboss-builder' ); ?></p>
	</div>

	<!-- On phones the steps become swipeable cards under the pinned preview (steps.js); on desktop they stack. -->
	<div class="pbb-steps" data-pbb-steps>
		<div class="pbb-stepnav" data-pbb-stepnav></div>
		<div class="pbb-track" data-pbb-track>
			<div class="pbb-step pbb-step-start" data-pbb-step="<?php esc_attr_e( 'Your reg & style', 'plateboss-builder' ); ?>">
				<div class="pbb-panel">
					<label class="pbb-field" for="pbb-reg-<?php echo esc_attr( $instance_id ); ?>">
						<span class="pbb-label"><?php esc_html_e( 'Your registration', 'plateboss-builder' ); ?></span>
					</label>
					<input
						id="pbb-reg-<?php echo esc_attr( $instance_id ); ?>"
						class="pbb-reg-input"
						type="text"
						inputmode="text"
						autocomplete="off"
						autocapitalize="characters"
						autocorrect="off"
						spellcheck="false"
						maxlength="8"
						placeholder="<?php esc_attr_e( 'YOUR REG', 'plateboss-builder' ); ?>"
						aria-describedby="pbb-reg-status-<?php echo esc_attr( $instance_id ); ?>"
						data-pbb-input="registration"
					>
					<p class="pbb-status" id="pbb-reg-status-<?php echo esc_attr( $instance_id ); ?>" data-pbb-output="status" aria-live="polite"></p>
				</div>
				<!-- Character style sits under the reg box, so phones see both on the first card. -->
				<div class="pbb-section">
					<div class="pbb-section-head">
						<span class="pbb-label" id="pbb-style-label-<?php echo esc_attr( $instance_id ); ?>"><?php esc_html_e( 'Character style', 'plateboss-builder' ); ?></span>
						<span class="pbb-legal" data-pbb-output="legal"></span>
					</div>
					<div class="pbb-styles" role="radiogroup" aria-labelledby="pbb-style-label-<?php echo esc_attr( $instance_id ); ?>" data-pbb-styles></div>
					<p class="pbb-style-desc" data-pbb-output="style-desc" aria-live="polite"></p>
				</div>
				<div class="pbb-section" data-pbb-accent-section hidden>
					<span class="pbb-label" id="pbb-accent-label-<?php echo esc_attr( $instance_id ); ?>"><?php esc_html_e( 'Effect colour', 'plateboss-builder' ); ?></span>
					<div class="pbb-swatches" role="radiogroup" aria-labelledby="pbb-accent-label-<?php echo esc_attr( $instance_id ); ?>" data-pbb-accents></div>
				</div>
			</div>
			<div class="pbb-step" data-pbb-step="<?php esc_attr_e( 'Plate size', 'plateboss-builder' ); ?>">
				<div class="pbb-section">
					<span class="pbb-label" id="pbb-size-label-<?php echo esc_attr( $instance_id ); ?>"><?php esc_html_e( 'Plate size', 'plateboss-builder' ); ?></span>
					<div class="pbb-chips pbb-plates" role="radiogroup" aria-label="<?php esc_attr_e( 'Plates', 'plateboss-builder' ); ?>" data-pbb-plates></div>
					<div class="pbb-options" role="radiogroup" aria-labelledby="pbb-size-label-<?php echo esc_attr( $instance_id ); ?>" data-pbb-sizes></div>
				</div>
			</div>
			<div class="pbb-step" data-pbb-step="<?php esc_attr_e( 'Badge', 'plateboss-builder' ); ?>">
				<div class="pbb-section">
					<span class="pbb-label" id="pbb-badge-label-<?php echo esc_attr( $instance_id ); ?>"><?php esc_html_e( 'Badge', 'plateboss-builder' ); ?></span>
					<div class="pbb-options pbb-badges" role="radiogroup" aria-labelledby="pbb-badge-label-<?php echo esc_attr( $instance_id ); ?>" data-pbb-badges></div>
					<p class="pbb-style-desc" data-pbb-output="badge-note" aria-live="polite"></p>
				</div>
			</div>
			<div class="pbb-step" data-pbb-step="<?php esc_attr_e( 'Border', 'plateboss-builder' ); ?>">
				<div class="pbb-section">
					<span class="pbb-label" id="pbb-border-label-<?php echo esc_attr( $instance_id ); ?>"><?php esc_html_e( 'Border', 'plateboss-builder' ); ?></span>
					<div class="pbb-chips" role="radiogroup" aria-labelledby="pbb-border-label-<?php echo esc_attr( $instance_id ); ?>" data-pbb-borders></div>
				</div>
			</div>
			<div class="pbb-step" data-pbb-step="<?php esc_attr_e( 'Add-ons', 'plateboss-builder' ); ?>">
				<div class="pbb-section pbb-basket">
					<span class="pbb-label"><?php esc_html_e( 'Add-ons', 'plateboss-builder' ); ?></span>
					<!-- Fixings, holders and in-store fitting (basket.js). -->
					<div class="pbb-extras" data-pbb-extras></div>
					<div class="pbb-summary" data-pbb-summary aria-live="polite"></div>
				</div>
			</div>
		</div>
	</div>

	<div class="pbb-buybar">
		<div class="pbb-total"><span><?php esc_html_e( 'Total', 'plateboss-builder' ); ?></span><strong data-pbb-total></strong></div>
		<button type="button" class="pbb-next" data-pbb-next><?php esc_html_e( 'Next', 'plateboss-builder' ); ?></button>
		<button type="button" class="pbb-add" data-pbb-add disabled><?php esc_html_e( 'Continue to checkout', 'plateboss-builder' ); ?></button>
	</div>
	<p class="pbb-cart-msg" data-pbb-cart-msg aria-live="polite"></p>
</div>
