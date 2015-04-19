<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Moip Ajax actions.
 */
class WC_Moip_Ajax {

	/**
	 * Initialize the ajax actions.
	 */
	public function __construct() {
		add_action( 'wp_ajax_woocommerce_moip_transparent_checkout', array( $this, 'transparent_checkout_ajax' ) );
		add_action( 'wp_ajax_nopriv_woocommerce_moip_transparent_checkout', array( $this, 'transparent_checkout_ajax' ) );
	}

	/**
	 * Saved by ajax the order information.
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
}

new WC_Moip_Ajax();
