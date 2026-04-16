<?php
/**
 * Cookie banner HTML template.
 * Variables are provided by WPO_Consent_Manager::render_banner().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Retrieve options with defaults.
$title          = get_option( 'wpo_cc_banner_title',       '🍪 Usamos cookies para mejorar tu experiencia' );
$description    = get_option( 'wpo_cc_banner_description', 'Las cookies nos ayudan a personalizar el contenido, analizar el tráfico y ofrecerte la mejor experiencia posible en nuestra web. Puedes aceptar todas las cookies, personalizar tu selección o rechazarlas.' );
$btn_accept     = get_option( 'wpo_cc_btn_accept_all',     'Aceptar todas' );
$btn_reject     = get_option( 'wpo_cc_btn_reject_all',     'Solo necesarias' );
$btn_customize  = get_option( 'wpo_cc_btn_customize',      'Personalizar' );
$btn_save_prefs = get_option( 'wpo_cc_btn_save_prefs',     'Guardar mis preferencias' );
$cookie_url     = get_option( 'wpo_cc_cookie_policy_url',  '/politica-de-cookies/' );
$privacy_url    = get_option( 'wpo_cc_privacy_policy_url', '/politica-de-privacidad/' );
$color_primary  = get_option( 'wpo_cc_color_primary',      '#105b8c' );
$color_secondary= get_option( 'wpo_cc_color_secondary',    '#3ba1c0' );
$color_text     = get_option( 'wpo_cc_color_text_on_primary', '#ffffff' );

// Sanitize colors.
$color_primary   = preg_match( '/^#[0-9a-fA-F]{3,6}$/', $color_primary )   ? $color_primary   : '#105b8c';
$color_secondary = preg_match( '/^#[0-9a-fA-F]{3,6}$/', $color_secondary ) ? $color_secondary : '#3ba1c0';
$color_text      = preg_match( '/^#[0-9a-fA-F]{3,6}$/', $color_text )      ? $color_text      : '#ffffff';
?>

<!-- WPO Cookie Consent Banner -->
<style id="wpo-cc-dynamic">
:root {
	--wpo-cc-primary:    <?php echo esc_attr( $color_primary ); ?>;
	--wpo-cc-secondary:  <?php echo esc_attr( $color_secondary ); ?>;
	--wpo-cc-text-on-primary: <?php echo esc_attr( $color_text ); ?>;
}
</style>

<div id="wpo-cc-overlay" class="wpo-cc-overlay" role="dialog" aria-modal="true" aria-labelledby="wpo-cc-title" aria-describedby="wpo-cc-desc" hidden>

	<!-- ===== Main banner ===== -->
	<div id="wpo-cc-banner" class="wpo-cc-banner">

		<div class="wpo-cc-banner__body">
			<p id="wpo-cc-title" class="wpo-cc-banner__title"><?php echo wp_kses_post( $title ); ?></p>
			<p id="wpo-cc-desc"  class="wpo-cc-banner__desc"><?php echo wp_kses_post( $description ); ?></p>
			<p class="wpo-cc-banner__links">
				<a href="<?php echo esc_url( $cookie_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Política de cookies', 'wpo-cookie-consent' ); ?></a>
				&nbsp;·&nbsp;
				<a href="<?php echo esc_url( $privacy_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Privacidad', 'wpo-cookie-consent' ); ?></a>
			</p>
		</div>

		<div class="wpo-cc-banner__actions">
			<button id="wpo-cc-btn-customize" class="wpo-cc-btn wpo-cc-btn--ghost" type="button">
				<?php echo esc_html( $btn_customize ); ?>
			</button>
			<button id="wpo-cc-btn-reject" class="wpo-cc-btn wpo-cc-btn--outline" type="button">
				<?php echo esc_html( $btn_reject ); ?>
			</button>
			<button id="wpo-cc-btn-accept" class="wpo-cc-btn wpo-cc-btn--primary" type="button">
				<span class="wpo-cc-btn__icon" aria-hidden="true">✓</span>
				<?php echo esc_html( $btn_accept ); ?>
			</button>
		</div>
	</div>

	<!-- ===== Preferences panel ===== -->
	<div id="wpo-cc-modal" class="wpo-cc-modal" hidden aria-modal="true" role="dialog" aria-labelledby="wpo-cc-modal-title">

		<div class="wpo-cc-modal__inner">
			<button class="wpo-cc-modal__close" id="wpo-cc-modal-close" type="button" aria-label="<?php esc_attr_e( 'Cerrar', 'wpo-cookie-consent' ); ?>">&#x2715;</button>

			<h2 id="wpo-cc-modal-title" class="wpo-cc-modal__title"><?php esc_html_e( 'Gestionar preferencias de cookies', 'wpo-cookie-consent' ); ?></h2>

			<!-- Necessary (always on) -->
			<div class="wpo-cc-toggle">
				<div class="wpo-cc-toggle__info">
					<strong><?php esc_html_e( 'Cookies necesarias', 'wpo-cookie-consent' ); ?></strong>
					<p><?php esc_html_e( 'Imprescindibles para el funcionamiento básico del sitio web. No pueden desactivarse.', 'wpo-cookie-consent' ); ?></p>
				</div>
				<label class="wpo-cc-switch wpo-cc-switch--disabled">
					<input type="checkbox" checked disabled aria-label="<?php esc_attr_e( 'Cookies necesarias (siempre activas)', 'wpo-cookie-consent' ); ?>">
					<span class="wpo-cc-switch__slider"></span>
					<span class="wpo-cc-switch__label"><?php esc_html_e( 'Siempre activas', 'wpo-cookie-consent' ); ?></span>
				</label>
			</div>

			<!-- Analytics -->
			<div class="wpo-cc-toggle">
				<div class="wpo-cc-toggle__info">
					<strong><?php esc_html_e( 'Cookies analíticas', 'wpo-cookie-consent' ); ?></strong>
					<p><?php esc_html_e( 'Nos permiten medir el tráfico y el comportamiento de los visitantes para mejorar nuestros servicios (Google Analytics).', 'wpo-cookie-consent' ); ?></p>
				</div>
				<label class="wpo-cc-switch">
					<input type="checkbox" id="wpo-cc-analytics" name="analytics" value="granted" aria-label="<?php esc_attr_e( 'Cookies analíticas', 'wpo-cookie-consent' ); ?>">
					<span class="wpo-cc-switch__slider"></span>
					<span class="wpo-cc-switch__label wpo-cc-switch__label--off"><?php esc_html_e( 'Desactivadas', 'wpo-cookie-consent' ); ?></span>
				</label>
			</div>

			<!-- Marketing -->
			<div class="wpo-cc-toggle">
				<div class="wpo-cc-toggle__info">
					<strong><?php esc_html_e( 'Cookies de marketing', 'wpo-cookie-consent' ); ?></strong>
					<p><?php esc_html_e( 'Usadas para mostrarte publicidad relevante en función de tus intereses (Google Ads, redes sociales).', 'wpo-cookie-consent' ); ?></p>
				</div>
				<label class="wpo-cc-switch">
					<input type="checkbox" id="wpo-cc-marketing" name="marketing" value="granted" aria-label="<?php esc_attr_e( 'Cookies de marketing', 'wpo-cookie-consent' ); ?>">
					<span class="wpo-cc-switch__slider"></span>
					<span class="wpo-cc-switch__label wpo-cc-switch__label--off"><?php esc_html_e( 'Desactivadas', 'wpo-cookie-consent' ); ?></span>
				</label>
			</div>

			<!-- Preferences -->
			<div class="wpo-cc-toggle">
				<div class="wpo-cc-toggle__info">
					<strong><?php esc_html_e( 'Cookies de preferencias', 'wpo-cookie-consent' ); ?></strong>
					<p><?php esc_html_e( 'Recuerdan tus preferencias para ofrecerte una experiencia más personalizada (idioma, región, etc.).', 'wpo-cookie-consent' ); ?></p>
				</div>
				<label class="wpo-cc-switch">
					<input type="checkbox" id="wpo-cc-preferences" name="preferences" value="granted" aria-label="<?php esc_attr_e( 'Cookies de preferencias', 'wpo-cookie-consent' ); ?>">
					<span class="wpo-cc-switch__slider"></span>
					<span class="wpo-cc-switch__label wpo-cc-switch__label--off"><?php esc_html_e( 'Desactivadas', 'wpo-cookie-consent' ); ?></span>
				</label>
			</div>

			<div class="wpo-cc-modal__footer">
				<button id="wpo-cc-modal-reject" class="wpo-cc-btn wpo-cc-btn--outline" type="button">
					<?php echo esc_html( $btn_reject ); ?>
				</button>
				<button id="wpo-cc-modal-save" class="wpo-cc-btn wpo-cc-btn--primary" type="button">
					<?php echo esc_html( $btn_save_prefs ); ?>
				</button>
				<button id="wpo-cc-modal-accept" class="wpo-cc-btn wpo-cc-btn--primary wpo-cc-btn--accept-all" type="button">
					<span class="wpo-cc-btn__icon" aria-hidden="true">✓</span>
					<?php echo esc_html( $btn_accept ); ?>
				</button>
			</div>
		</div>
	</div>

</div><!-- /#wpo-cc-overlay -->
