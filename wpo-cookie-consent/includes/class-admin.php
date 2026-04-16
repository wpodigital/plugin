<?php
/**
 * Admin settings page for WPO Cookie Consent.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPO_Cookie_Admin {

	const OPTION_GROUP = 'wpo_cc_settings';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_menu() {
		add_options_page(
			__( 'WPO Cookie Consent', 'wpo-cookie-consent' ),
			__( 'Cookie Consent', 'wpo-cookie-consent' ),
			'manage_options',
			'wpo-cookie-consent',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		$textarea_keys = array( 'wpo_cc_banner_description' );

		$fields = array(
			'wpo_cc_gtm_id'               => array( 'label' => __( 'Google Tag Manager ID', 'wpo-cookie-consent' ),         'default' => 'GTM-XXXXXXX' ),
			'wpo_cc_cookie_policy_url'     => array( 'label' => __( 'URL Política de Cookies', 'wpo-cookie-consent' ),       'default' => '/politica-de-cookies/' ),
			'wpo_cc_privacy_policy_url'    => array( 'label' => __( 'URL Política de Privacidad', 'wpo-cookie-consent' ),    'default' => '/politica-de-privacidad/' ),
			'wpo_cc_banner_title'          => array( 'label' => __( 'Título del banner', 'wpo-cookie-consent' ),             'default' => '🍪 Usamos cookies para mejorar tu experiencia' ),
			'wpo_cc_banner_description'    => array( 'label' => __( 'Descripción del banner', 'wpo-cookie-consent' ),        'default' => 'Las cookies nos ayudan a personalizar el contenido, analizar el tráfico y ofrecerte la mejor experiencia posible en nuestra web. Puedes aceptar todas las cookies, personalizar tu selección o rechazarlas.' ),
			'wpo_cc_btn_accept_all'        => array( 'label' => __( 'Texto botón Aceptar todo', 'wpo-cookie-consent' ),      'default' => 'Aceptar todas' ),
			'wpo_cc_btn_reject_all'        => array( 'label' => __( 'Texto botón Rechazar', 'wpo-cookie-consent' ),          'default' => 'Solo necesarias' ),
			'wpo_cc_btn_customize'         => array( 'label' => __( 'Texto botón Personalizar', 'wpo-cookie-consent' ),      'default' => 'Personalizar' ),
			'wpo_cc_btn_save_prefs'        => array( 'label' => __( 'Texto botón Guardar preferencias', 'wpo-cookie-consent' ), 'default' => 'Guardar mis preferencias' ),
			'wpo_cc_color_primary'         => array( 'label' => __( 'Color primario (botón Aceptar)', 'wpo-cookie-consent' ),'default' => '#105b8c' ),
			'wpo_cc_color_secondary'       => array( 'label' => __( 'Color secundario (acento)', 'wpo-cookie-consent' ),     'default' => '#3ba1c0' ),
			'wpo_cc_color_text_on_primary' => array( 'label' => __( 'Color texto sobre primario', 'wpo-cookie-consent' ),    'default' => '#ffffff' ),
		);

		add_settings_section( 'wpo_cc_main', '', '__return_null', 'wpo-cookie-consent' );

		foreach ( $fields as $key => $data ) {
			if ( strpos( $key, 'color' ) !== false ) {
				$field_type = 'color';
			} elseif ( in_array( $key, $textarea_keys, true ) ) {
				$field_type = 'textarea';
			} else {
				$field_type = 'text';
			}

			register_setting( self::OPTION_GROUP, $key, array( 'sanitize_callback' => 'sanitize_text_field' ) );
			add_settings_field(
				$key,
				esc_html( $data['label'] ),
				array( $this, 'render_field' ),
				'wpo-cookie-consent',
				'wpo_cc_main',
				array(
					'key'     => $key,
					'default' => $data['default'],
					'type'    => $field_type,
				)
			);
		}
	}

	public function render_field( $args ) {
		$key     = $args['key'];
		$default = $args['default'];
		$type    = $args['type'];
		$value   = get_option( $key, $default );

		if ( 'color' === $type ) {
			printf(
				'<input type="color" name="%s" id="%s" value="%s" class="regular-text">',
				esc_attr( $key ),
				esc_attr( $key ),
				esc_attr( $value )
			);
		} elseif ( 'textarea' === $type ) {
			printf(
				'<textarea name="%s" id="%s" rows="3" class="large-text">%s</textarea>',
				esc_attr( $key ),
				esc_attr( $key ),
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="text" name="%s" id="%s" value="%s" class="regular-text">',
				esc_attr( $key ),
				esc_attr( $key ),
				esc_attr( $value )
			);
		}
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WPO Cookie Consent – Configuración', 'wpo-cookie-consent' ); ?></h1>
			<p><?php esc_html_e( 'Configura el banner de cookies propio con cumplimiento de Google Consent Mode v2.', 'wpo-cookie-consent' ); ?></p>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'wpo-cookie-consent' );
				submit_button( __( 'Guardar cambios', 'wpo-cookie-consent' ) );
				?>
			</form>
			<hr>
			<h2><?php esc_html_e( 'Instrucciones de uso', 'wpo-cookie-consent' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Desactiva o elimina el plugin Cookiebot antes de activar éste.', 'wpo-cookie-consent' ); ?></li>
				<li><?php esc_html_e( 'Introduce tu ID de Google Tag Manager (p.ej. GTM-54BGG2V). El plugin inyectará GTM con Consent Mode v2 automáticamente.', 'wpo-cookie-consent' ); ?></li>
				<li><?php esc_html_e( 'Si ya tienes GTM cargado por otro medio, deja el campo GTM ID en blanco para evitar duplicados.', 'wpo-cookie-consent' ); ?></li>
				<li><?php esc_html_e( 'En Google Tag Manager, activa las etiquetas de Consent Mode para Analytics y Ads (modeling de conversiones).', 'wpo-cookie-consent' ); ?></li>
			</ol>
		</div>
		<?php
	}

}
