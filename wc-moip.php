<?php
/**
 * Plugin Name: WooCommerce MoIP
 * Plugin URI: https://github.com/claudiosmweb/woocommerce-moip
 * Description: Gateway de pagamento MoIP para WooCommerce.
 * Author: claudiosanches
 * Author URI: http://claudiosmweb.com/
 * Version: 1.4.0
 * License: GPLv2 or later
 * Text Domain: wcmoip
 * Domain Path: /languages/
 */

/**
 * WooCommerce fallback notice.
 */
function wcmoip_woocommerce_fallback_notice() {
    echo '<div class="error"><p>' . sprintf( __( 'WooCommerce MoIP Gateway depends on the last version of %s to work!', 'wcmoip' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>' ) . '</p></div>';
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
     * @param array $methods
     *
     * @return array
     */
    add_filter( 'woocommerce_payment_gateways', 'wcmoip_add_gateway' );

    function wcmoip_add_gateway( $methods ) {
        $methods[] = 'WC_MOIP_Gateway';

        return $methods;
    }

    // Include the WC_MOIP_Gateway class.
    require_once plugin_dir_path( __FILE__ ) . 'class-wc-moip-gateway.php';
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
            admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_MOIP_Gateway' ),
            __( 'Settings', 'wcmoip' )
        )
    );

    return array_merge( $settings, $links );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wcmoip_action_links' );
