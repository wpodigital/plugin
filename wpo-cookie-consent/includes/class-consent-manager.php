<?php
/**
 * Core consent manager: injects Consent Mode v2 defaults, enqueues assets, renders the banner.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPO_Consent_Manager {

	/** Cookie name stored in the browser. */
	const COOKIE_NAME = 'wpo_cookie_consent';

	/** Cookie lifetime in days. */
	const COOKIE_DAYS = 365;

	public function __construct() {
		// Inject Consent Mode v2 defaults as early as possible (before GTM).
		add_action( 'wp_head', array( $this, 'inject_consent_defaults' ), 1 );

		// Enqueue frontend assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Render the banner HTML at wp_footer.
		add_action( 'wp_footer', array( $this, 'render_banner' ) );

		// AJAX handler for saving consent (no-priv: public users).
		add_action( 'wp_ajax_wpo_save_consent', array( $this, 'ajax_save_consent' ) );
		add_action( 'wp_ajax_nopriv_wpo_save_consent', array( $this, 'ajax_save_consent' ) );
	}

	/**
	 * Inject Google Consent Mode v2 default state before GTM fires.
	 * This runs on wp_head priority 1 so it always precedes GTM snippets.
	 */
	public function inject_consent_defaults() {
		$gtm_id = get_option( 'wpo_cc_gtm_id', '' );
		?>
<script data-wpo-cc="ignore">
/* WPO Cookie Consent – Google Consent Mode v2 defaults */
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
	ad_personalization:      'denied',
	ad_storage:              'denied',
	ad_user_data:            'denied',
	analytics_storage:       'denied',
	functionality_storage:   'denied',
	personalization_storage: 'denied',
	security_storage:        'granted',
	wait_for_update:         500
});
gtag('set', 'ads_data_redaction', true);
<?php if ( $gtm_id ) : ?>
(function(w,d,s,l,i){
	w[l]=w[l]||[];
	w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
	var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
	j.async=true;
	j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
	f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( $gtm_id ); ?>');
<?php endif; ?>
</script>
		<?php
	}

	/**
	 * Enqueue CSS and JS for the cookie banner.
	 */
	public function enqueue_assets() {
		// Only load if consent has not yet been given.
		if ( $this->has_consent() ) {
			// Even with consent saved we need the tiny update script.
			wp_enqueue_script(
				'wpo-cookie-consent',
				WPO_CC_PLUGIN_URL . 'assets/js/cookie-banner.js',
				array(),
				WPO_CC_VERSION,
				true
			);
			$this->localize_script();
			return;
		}

		wp_enqueue_style(
			'wpo-cookie-consent',
			WPO_CC_PLUGIN_URL . 'assets/css/cookie-banner.css',
			array(),
			WPO_CC_VERSION
		);

		wp_enqueue_script(
			'wpo-cookie-consent',
			WPO_CC_PLUGIN_URL . 'assets/js/cookie-banner.js',
			array(),
			WPO_CC_VERSION,
			true
		);

		$this->localize_script();
	}

	/**
	 * Pass PHP data to JavaScript.
	 */
	private function localize_script() {
		$saved = $this->get_saved_consent();
		wp_localize_script(
			'wpo-cookie-consent',
			'wpoCookieConsent',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'wpo_cc_nonce' ),
				'cookieName'   => self::COOKIE_NAME,
				'cookieDays'   => self::COOKIE_DAYS,
				'version'      => WPO_CC_VERSION,
				'savedConsent' => $saved,
				'hasSaved'     => ! empty( $saved ),
			)
		);
	}

	/**
	 * Render cookie banner HTML in the footer.
	 */
	public function render_banner() {
		// Always render the container; JS decides whether to show it.
		include WPO_CC_PLUGIN_DIR . 'templates/cookie-banner.php';
	}

	/**
	 * AJAX: receive consent choices, validate, set cookie, return updated state.
	 */
	public function ajax_save_consent() {
		check_ajax_referer( 'wpo_cc_nonce', 'nonce' );

		$allowed_values = array( 'granted', 'denied' );

		$analytics    = isset( $_POST['analytics'] )    && in_array( $_POST['analytics'],    $allowed_values, true ) ? sanitize_text_field( $_POST['analytics'] )    : 'denied';
		$marketing    = isset( $_POST['marketing'] )    && in_array( $_POST['marketing'],    $allowed_values, true ) ? sanitize_text_field( $_POST['marketing'] )    : 'denied';
		$preferences  = isset( $_POST['preferences'] )  && in_array( $_POST['preferences'],  $allowed_values, true ) ? sanitize_text_field( $_POST['preferences'] )  : 'denied';

		$consent = array(
			'analytics'   => $analytics,
			'marketing'   => $marketing,
			'preferences' => $preferences,
			'timestamp'   => time(),
			'version'     => WPO_CC_VERSION,
		);

		// Store consent in a server-set cookie.
		// httponly is intentionally false: the consent banner JS needs to read
		// this cookie client-side to re-apply Consent Mode v2 on subsequent page loads.
		// The cookie contains only 'granted'/'denied' strings – no sensitive user data.
		$expiry = time() + ( self::COOKIE_DAYS * DAY_IN_SECONDS );
		setcookie(
			self::COOKIE_NAME,
			wp_json_encode( $consent ),
			array(
				'expires'  => $expiry,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			)
		);

		wp_send_json_success( $consent );
	}

	/**
	 * Check whether a valid consent cookie already exists.
	 */
	public function has_consent() {
		return isset( $_COOKIE[ self::COOKIE_NAME ] ) && '' !== $_COOKIE[ self::COOKIE_NAME ];
	}

	/**
	 * Return saved consent array or empty array.
	 * All values are validated against an allowlist before being returned.
	 */
	public function get_saved_consent() {
		if ( ! $this->has_consent() ) {
			return array();
		}
		$raw  = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}
		// Validate each consent value against an allowlist.
		$allowed = array( 'granted', 'denied' );
		foreach ( array( 'analytics', 'marketing', 'preferences' ) as $key ) {
			if ( ! isset( $data[ $key ] ) || ! in_array( $data[ $key ], $allowed, true ) ) {
				$data[ $key ] = 'denied';
			}
		}
		return $data;
	}
}
