<?php
/**
 * Plugin Name: WooCommerce Moip
 * Plugin URI: https://github.com/claudiosmweb/woocommerce-moip
 * Description: Gateway de pagamento Moip para WooCommerce.
 * Author: Claudio Sanches
 * Author URI: http://claudiosmweb.com/
 * Version: 2.2.11
 * License: GPLv2 or later
 * Text Domain: woocommerce-moip
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Moip' ) ) :

/**
 * WooCommerce Moip main class.
 */
class WC_Moip {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '2.2.11';

	/**
	 * Integration id.
	 *
	 * @var string
	 */
	protected static $gateway_id = 'moip';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin actions.
	 */
	public function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Checks with WooCommerce is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			$this->includes();

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Return the gateway id/slug.
	 *
	 * @return string Gateway id/slug variable.
	 */
	public static function get_gateway_id() {
		return self::$gateway_id;
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-moip' );

		load_textdomain( 'woocommerce-moip', trailingslashit( WP_LANG_DIR ) . 'woocommerce-moip/woocommerce-moip-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-moip', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array          Payment methods with Moip.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_Moip_Gateway';

		return $methods;
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once 'includes/class-wc-moip-messages.php';
		include_once 'includes/class-wc-moip-ajax.php';
		include_once 'includes/class-wc-moip-gateway.php';
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Moip Gateway depends on the last version of %s to work!', 'woocommerce-moip' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
	}
}

add_action( 'plugins_loaded', array( 'WC_Moip', 'get_instance' ), 0 );

endif;

/**
 * Adds support to legacy IPN.
 *
 * @return void
 */
function wcmoip_legacy_ipn() {
	if ( isset( $_POST['cod_moip'] ) && ! isset( $_GET['wc-api'] ) ) {
		global $woocommerce;

		$woocommerce->payment_gateways();

		do_action( 'woocommerce_api_wc_moip_gateway' );
	}
}

add_action( 'init', 'wcmoip_legacy_ipn' );
