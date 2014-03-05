<?php
/**
 * Plugin Name: WooCommerce Moip
 * Plugin URI: https://github.com/claudiosmweb/woocommerce-moip
 * Description: Gateway de pagamento Moip para WooCommerce.
 * Author: claudiosanches
 * Author URI: http://claudiosmweb.com/
 * Version: 2.2.6
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
	 * @since 2.2.6
	 *
	 * @var   string
	 */
	const VERSION = '2.2.6';

	/**
	 * Integration id.
	 *
	 * @since 2.2.6
	 *
	 * @var   string
	 */
	protected static $gateway_id = 'moip';

	/**
	 * Plugin slug.
	 *
	 * @since 2.2.6
	 *
	 * @var   string
	 */
	protected static $plugin_slug = 'woocommerce-moip';

	/**
	 * Instance of this class.
	 *
	 * @since 2.2.6
	 *
	 * @var   object
	 */
	protected static $instance = null;

	public function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Initialize the plugin actions.
		$this->init();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since  2.2.6
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
	 * Return the plugin slug.
	 *
	 * @since  2.2.6
	 *
	 * @return string Plugin slug variable.
	 */
	public static function get_plugin_slug() {
		return self::$plugin_slug;
	}

	/**
	 * Return the gateway id/slug.
	 *
	 * @since  2.2.6
	 *
	 * @return string Gateway id/slug variable.
	 */
	public static function get_gateway_id() {
		return self::$gateway_id;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since  2.2.6
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$domain = self::$plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Initialize the plugin public actions.
	 *
	 * @since  2.2.6
	 *
	 * @return  void
	 */
	protected function init() {
		// Checks with WooCommerce is installed.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			// Include the WC_Moip_Gateway class.
			include_once 'includes/class-wc-moip-messages.php';
			include_once 'includes/class-wc-moip-gateway.php';

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );
			add_action( 'wp_ajax_woocommerce_moip_transparent_checkout', array( $this, 'transparent_checkout_ajax' ) );
			add_action( 'wp_ajax_nopriv_woocommerce_moip_transparent_checkout', array( $this, 'transparent_checkout_ajax' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @version 2.2.6
	 *
	 * @param   array $methods WooCommerce payment methods.
	 *
	 * @return  array          Payment methods with Moip.
	 */
	public function add_gateway( $methods ) {
		$methods[] = 'WC_Moip_Gateway';

		return $methods;
	}

	/**
	 * Saved by ajax the order information.
	 *
	 * @version 2.2.6
	 *
	 * @return void
	 */
	public function transparent_checkout_ajax() {
		$settings = get_option( 'woocommerce_moip_settings' );

		if ( 'tc' != $settings['api'] ) {
			die();
		}

		check_ajax_referer( 'woocommerce_moip_transparent_checkout', 'security' );

		$method   = $_POST['method'];
		$order_id = (int) $_POST['order_id'];
		$order    = new WC_Order( $order_id );
		if ( function_exists( 'WC' ) ) {
			$mailer = WC()->mailer();
		} else {
			global $woocommerce;
			$mailer = $woocommerce->mailer();
		}

		if ( 'CartaoCredito' == $method ) {
			// Add payment information.
			$status = esc_attr( WC_Moip_Messages::translate_status( $_POST['status'] ) );
			update_post_meta( $order_id, 'woocommerce_moip_method', esc_attr( $_POST['method'] ) );
			update_post_meta( $order_id, 'woocommerce_moip_code', esc_attr( $_POST['code'] ) );
			update_post_meta( $order_id, 'woocommerce_moip_status', $status );

			// Send email with payment information.
			$message_body = '<p>';
			$message_body .= WC_Moip_Messages::credit_cart_message( $status, $_POST['code'] );
			$message_body .= '</p>';

			$message = $mailer->wrap_message(
				sprintf( __( 'Order %s received', 'woocommerce-moip' ), $order->get_order_number() ),
				apply_filters( 'woocommerce_moip_thankyou_creditcard_email_message', $message_body, $order_id )
			);

			$mailer->send( $order->billing_email, sprintf( __( 'Order %s received', 'woocommerce-moip' ), $order->get_order_number() ), $message );
		} else if ( 'DebitoBancario' == $method ) {
			// Add payment information.
			update_post_meta( $order_id, 'woocommerce_moip_method', esc_attr( $_POST['method'] ) );
			update_post_meta( $order_id, 'woocommerce_moip_url', esc_url( $_POST['url'] ) );

			// Send email with payment information.
			$url = sprintf( '<p><a class="button" href="%1$s" target="_blank">%1$s</a></p>', esc_url( $_POST['url'] ) );
			$message_body = '<p>';
			$message_body .= WC_Moip_Messages::debit_email_message();
			$message_body .= '</p>';

			$message = $mailer->wrap_message(
				sprintf( __( 'Order %s received', 'woocommerce-moip' ), $order->get_order_number() ),
				apply_filters( 'woocommerce_moip_thankyou_debit_email_message', $message_body, $order_id ) . $url
			);

			$mailer->send( $order->billing_email, sprintf( __( 'Order %s received', 'woocommerce-moip' ), $order->get_order_number() ), $message );
		} else {
			// Add payment information.
			update_post_meta( $order_id, 'woocommerce_moip_method', esc_attr( $_POST['method'] ) );
			update_post_meta( $order_id, 'woocommerce_moip_url', esc_url( $_POST['url'] ) );

			// Send email with payment information.
			$url = sprintf( '<p><a class="button" href="%1$s" target="_blank">%1$s</a></p>', esc_url( $_POST['url'] ) );
			$message_body = '<p>';
			$message_body .= WC_Moip_Messages::billet_email_message();
			$message_body .= '</p>';

			$message = $mailer->wrap_message(
				sprintf( __( 'Order %s received', 'woocommerce-moip' ), $order->get_order_number() ),
				apply_filters( 'woocommerce_moip_thankyou_billet_email_message', $message_body, $order_id ) . $url
			);

			$mailer->send( $order->billing_email, sprintf( __( 'Order %s received', 'woocommerce-moip' ), $order->get_order_number() ), $message );
		}

		die();
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @version 2.2.6
	 *
	 * @return  string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Moip Gateway depends on the last version of %s to work!', self::$plugin_slug ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
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
