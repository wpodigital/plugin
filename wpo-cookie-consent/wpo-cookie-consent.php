<?php
/**
 * Plugin Name:       WPO Cookie Consent
 * Plugin URI:        https://wpodigital.com/
 * Description:       Gestor de consentimiento de cookies propio con cumplimiento de Google Consent Mode v2. Sustituye a Cookiebot sin dependencias de terceros.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            WPO Digital
 * Author URI:        https://wpodigital.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpo-cookie-consent
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPO_CC_VERSION', '1.0.0' );
define( 'WPO_CC_PLUGIN_FILE', __FILE__ );
define( 'WPO_CC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPO_CC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once WPO_CC_PLUGIN_DIR . 'includes/class-consent-manager.php';
require_once WPO_CC_PLUGIN_DIR . 'includes/class-admin.php';

/**
 * Main plugin instance.
 */
function wpo_cookie_consent() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new WPO_Consent_Manager();
	}
	return $instance;
}

add_action( 'plugins_loaded', 'wpo_cookie_consent' );

if ( is_admin() ) {
	new WPO_Cookie_Admin();
}
