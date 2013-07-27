<?php
/**
 * Plugin Name: WooCommerce Moip
 * Plugin URI: https://github.com/claudiosmweb/woocommerce-moip
 * Description: Gateway de pagamento Moip para WooCommerce.
 * Author: claudiosanches
 * Author URI: http://claudiosmweb.com/
 * Version: 2.1.0
 * License: GPLv2 or later
 * Text Domain: wcmoip
 * Domain Path: /languages/
 */

define( 'WOO_MOIP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOO_MOIP_URL', plugin_dir_url( __FILE__ ) );

/**
 * WooCommerce fallback notice.
 */
function wcmoip_woocommerce_fallback_notice() {
    echo '<div class="error"><p>' . sprintf( __( 'WooCommerce Moip Gateway depends on the last version of %s to work!', 'wcmoip' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
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
    load_plugin_textdomain( 'wcmoip', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

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

    // Include the WC_Moip_Gateway class.
    require_once WOO_MOIP_PATH . 'includes/class-wc-moip-gateway.php';
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
 * Adds custom settings url in plugins page.
 *
 * @param  array $links Default links.
 *
 * @return array        Default links and settings link.
 */
function wcmoip_action_links( $links ) {

    $settings = array(
        'settings' => sprintf(
            '<a href="%s">%s</a>',
            admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Moip_Gateway' ),
            __( 'Settings', 'wcmoip' )
        )
    );

    return array_merge( $settings, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wcmoip_action_links' );

/**
 * Processes the Moip status message.
 *
 * @param  string $status Moip status message.
 *
 * @return string         Status message.
 */
function wcmoip_status( $status ) {
    switch ( $status ) {
        case 'Autorizado':
            return __( 'Authorized', 'wcmoip' );
            break;
        case 'Iniciado':
            return __( 'Initiate', 'wcmoip' );
            break;
        case 'BoletoImpresso':
            return __( 'Billet Printed', 'wcmoip' );
            break;
        case 'Concluido':
            return __( 'Concluded', 'wcmoip' );
            break;
        case 'Cancelado':
            return __( 'Canceled', 'wcmoip' );
            break;
        case 'EmAnalise':
            return __( 'In Review', 'wcmoip' );
            break;
        case 'Estornado':
            return __( 'Reversed', 'wcmoip' );
            break;
        case 'Reembolsado':
            return __( 'Refunded', 'wcmoip' );
            break;
        default:
            break;
    }
}

/**
 * Saved by ajax the order information.
 *
 * @return void
 */
function wcmoip_transparent_checkout_ajax() {
    global $woocommerce;

    $settings = get_option( 'woocommerce_moip_settings' );

    if ( 'tc' != $settings['api'] )
        die();

    check_ajax_referer( 'woocommerce_moip_transparent_checkout', 'security' );

    $method = $_POST['method'];
    $order_id = (int) $_POST['order_id'];
    $order = new WC_Order( $order_id );
    $mailer = $woocommerce->mailer();

    if ( 'CartaoCredito' == $method ) {
        // Add payment information.
        update_post_meta( $order_id, 'woocommerce_moip_method', esc_attr( $_POST['method'] ) );
        update_post_meta( $order_id, 'woocommerce_moip_code', esc_attr( $_POST['code'] ) );
        update_post_meta( $order_id, 'woocommerce_moip_status', wcmoip_status( esc_attr( $_POST['status'] ) ) );

        // Send email with payment information.
        $message_body = '<p>';
        $message_body .= __( 'Your transaction has been processed by Moip Payments S/A.', 'wcmoip' ) . '<br />';
        $message_body .= sprintf( __( 'The status of your transaction is %s and the MoIP code is', 'wcmoip' ), '<strong>' . esc_attr( $_POST['code'] ) . '</strong>' ) . ' <strong>' . wcmoip_status( esc_attr( $_POST['status'] ) ) . '</strong>.<br />';
        $message_body .= __( 'If you have any questions regarding the transaction, please contact the Moip.', 'wcmoip' ) . '<br />';
        $message_body .= '</p>';

        $message = $mailer->wrap_message(
            sprintf( __( 'Order %s received', 'wcmoip' ), $order->get_order_number() ),
            apply_filters( 'woocommerce_moip_thankyou_creditcard_email_message', $message_body, $order_id )
        );

        $mailer->send( $order->billing_email, sprintf( __( 'Order %s received', 'wcmoip' ), $order->get_order_number() ), $message );
    } else if ( 'DebitoBancario' == $method ) {
        // Add payment information.
        update_post_meta( $order_id, 'woocommerce_moip_method', esc_attr( $_POST['method'] ) );
        update_post_meta( $order_id, 'woocommerce_moip_url', esc_url( $_POST['url'] ) );

        // Send email with payment information.
        $url = sprintf( '<p><a class="button" href="%1$s" target="_blank">%1$s</a></p>', esc_url( $_POST['url'] ) );
        $message_body = '<p>';
        $message_body .= __( 'Your transaction has been processed by Moip Payments S/A.', 'wcmoip' ) . '<br />';
        $message_body .= __( 'If you have not made ​​the payment, please use the link below to pay.', 'wcmoip' ) . '<br />';
        $message_body .= __( 'If you have any questions regarding the transaction, please contact the Moip.', 'wcmoip' );
        $message_body .= '</p>';

        $message = $mailer->wrap_message(
            sprintf( __( 'Order %s received', 'wcmoip' ), $order->get_order_number() ),
            apply_filters( 'woocommerce_moip_thankyou_debit_email_message', $message_body, $order_id ) . $url
        );

        $mailer->send( $order->billing_email, sprintf( __( 'Order %s received', 'wcmoip' ), $order->get_order_number() ), $message );
    } else {
        // Add payment information.
        update_post_meta( $order_id, 'woocommerce_moip_method', esc_attr( $_POST['method'] ) );
        update_post_meta( $order_id, 'woocommerce_moip_url', esc_url( $_POST['url'] ) );

        // Send email with payment information.
        $url = sprintf( '<p><a class="button" href="%1$s" target="_blank">%1$s</a></p>', esc_url( $_POST['url'] ) );
        $message_body = '<p>';
        $message_body .= __( 'Your transaction has been processed by Moip Payments S/A.', 'wcmoip' ) . '<br />';
        $message_body .= __( 'If you have not yet received the billet, please use the link below to print it.', 'wcmoip' ) . '<br />';
        $message_body .= __( 'If you have any questions regarding the transaction, please contact the Moip.', 'wcmoip' );
        $message_body .= '</p>';

        $message = $mailer->wrap_message(
            sprintf( __( 'Order %s received', 'wcmoip' ), $order->get_order_number() ),
            apply_filters( 'woocommerce_moip_thankyou_billet_email_message', $message_body, $order_id ) . $url
        );

        $mailer->send( $order->billing_email, sprintf( __( 'Order %s received', 'wcmoip' ), $order->get_order_number() ), $message );
    }

    die();
}

add_action( 'wp_ajax_woocommerce_moip_transparent_checkout', 'wcmoip_transparent_checkout_ajax' );
add_action( 'wp_ajax_nopriv_woocommerce_moip_transparent_checkout', 'wcmoip_transparent_checkout_ajax' );
