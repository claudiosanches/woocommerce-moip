<?php
/**
 * Plugin Name: WooCommerce Moip
 * Plugin URI: https://github.com/claudiosmweb/woocommerce-moip
 * Description: Gateway de pagamento Moip para WooCommerce.
 * Author: claudiosanches
 * Author URI: http://claudiosmweb.com/
 * Version: 2.2.5
 * License: GPLv2 or later
 * Text Domain: woocommerce-moip
 * Domain Path: /languages/
 */

/**
 * WooCommerce fallback notice.
 */
function wcmoip_woocommerce_fallback_notice() {
	echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Moip Gateway depends on the last version of %s to work!', 'woocommerce-moip' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
}

/**
 * Load functions.
 */
function wcmoip_gateway_load() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		add_action( 'admin_notices', 'wcmoip_woocommerce_fallback_notice' );

		return;
	}

	/**
	 * Load textdomain.
	 */
	load_plugin_textdomain( 'woocommerce-moip', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param array $methods Default methods.
	 *
	 * @return array         Methods with Moip gateway.
	 */
	function wcmoip_add_gateway( $methods ) {
		$methods[] = 'WC_Moip_Gateway';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'wcmoip_add_gateway' );

	// Include the plugin classes.
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-moip-messages.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-moip-gateway.php';
}

add_action( 'plugins_loaded', 'wcmoip_gateway_load', 0 );

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

/**
 * Saved by ajax the order information.
 *
 * @return void
 */
function wcmoip_transparent_checkout_ajax() {
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

add_action( 'wp_ajax_woocommerce_moip_transparent_checkout', 'wcmoip_transparent_checkout_ajax' );
add_action( 'wp_ajax_nopriv_woocommerce_moip_transparent_checkout', 'wcmoip_transparent_checkout_ajax' );
