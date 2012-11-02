<?php
/**
 * Plugin Name: WooCommerce MoIP
 * Plugin URI: http://claudiosmweb.com/plugins/moip-para-woocommerce/
 * Description: Gateway de pagamento MoIP para WooCommerce.
 * Author: claudiosanches
 * Author URI: http://www.claudiosmweb.com/
 * Version: 1.0
 * License: GPLv2 or later
 * Text Domain: wcmoip
 * Domain Path: /languages/
 */

/**
 * WooCommerce fallback notice.
 */
function wcmoip_woocommerce_fallback_notice() {
    $message = '<div class="error">';
        $message .= '<p>' . __( 'WooCommerce MoIP Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> to work!' , 'wcmoip' ) . '</p>';
    $message .= '</div>';

    echo $message;
}

/**
 * Load functions.
 */
add_action( 'plugins_loaded', 'wcmoip_gateway_load', 0 );

function wcmoip_gateway_load() {

    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', 'wcmoip_woocommerce_fallback_notice' );

        return;
    }

    /**
     * Load textdomain.
     */
    load_plugin_textdomain( 'wcmoip', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    /**
     * Add the gateway to MoIP.
     *
     * @access public
     * @param array $methods
     * @return array
     */
    add_filter( 'woocommerce_payment_gateways', 'wcmoip_add_gateway' );

    function wcmoip_add_gateway( $methods ) {
        $methods[] = 'WC_MOIP_Gateway';
        return $methods;
    }

    /**
     * WC MoIP Gateway Class.
     *
     * Built the MoIP method.
     */
    class WC_MOIP_Gateway extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         *
         * @return void
         */
        public function __construct() {
            global $woocommerce;

            $this->id            = 'moip';
            $this->icon          = plugins_url( 'images/moip.png', __FILE__ );
            $this->has_fields    = false;

            // Sandbox URL.
            // $this->moip_url      = 'https://desenvolvedor.moip.com.br/sandbox/PagamentoMoIP.do';

            // Payment URL.
            $this->moip_url      = 'https://www.moip.com.br/PagamentoMoIP.do';

            $this->method_title  = __( 'MoIP', 'wcmoip' );

            // Load the form fields.
            $this->init_form_fields();

            // Load the settings.
            $this->init_settings();

            // Define user set variables.
            $this->title            = $this->settings['title'];
            $this->description      = $this->settings['description'];
            $this->login            = $this->settings['login'];
            $this->invoice_prefix   = !empty( $this->settings['invoice_prefix'] ) ? $this->settings['invoice_prefix'] : 'WC-';

            // Actions.
            add_action( 'init', array( &$this, 'check_moip_ipn_response' ) );
            add_action( 'valid_moip_ipn_request', array( &$this, 'successful_request' ) );
            add_action( 'woocommerce_receipt_moip', array( &$this, 'receipt_page' ) );
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

            // Valid for use.
            $this->enabled = ( 'yes' == $this->settings['enabled'] ) && !empty( $this->login );

            // Checks if email is not empty.
            $this->login == '' ? add_action( 'admin_notices', array( &$this, 'login_missing_message' ) ) : '';

        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis.
         *
         * @since 1.0.0
         */
        public function admin_options() {

            ?>
            <h3><?php _e( 'MoIP standard', 'wcmoip' ); ?></h3>
            <p><?php _e( 'MoIP standard works by sending the user to MoIP to enter their payment information.', 'wcmoip' ); ?></p>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table><!--/.form-table-->
            <?php
        }

        /**
         * Initialise Gateway Settings Form Fields.
         *
         * @return void
         */
        public function init_form_fields() {

            $this->form_fields = array(
                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'wcmoip' ),
                    'type' => 'checkbox',
                    'label' => __( 'Enable MoIP standard', 'wcmoip' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __( 'Title', 'wcmoip' ),
                    'type' => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'wcmoip' ),
                    'default' => __( 'MoIP', 'wcmoip' )
                ),
                'description' => array(
                    'title' => __( 'Description', 'wcmoip' ),
                    'type' => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'wcmoip' ),
                    'default' => __( 'Pay via MoIP', 'wcmoip' )
                ),
                'login' => array(
                    'title' => __( 'MoIP Login', 'wcmoip' ),
                    'type' => 'text',
                    'description' => __( 'Please enter your MoIP email address or username; this is needed in order to take payment.', 'wcmoip' ),
                    'default' => ''
                ),
                'invoice_prefix' => array(
                    'title' => __( 'Invoice Prefix', 'wcmoip' ),
                    'type' => 'text',
                    'description' => __( 'Please enter a prefix for your invoice numbers. If you use your MoIP account for multiple stores ensure this prefix is unqiue as MoIP will not allow orders with the same invoice number.', 'wcmoip' ),
                    'default' => 'WC-'
                )
            );

        }

        /**
         * Get MoIP Args.
         *
         * @param mixed $order
         * @return array
         */
        public function get_moip_args( $order ) {
            global $woocommerce;

            $order_id = $order->id;

            // Fixed phone number.
            $order->billing_phone = str_replace( array( '(', '-', ' ', ')' ), '', $order->billing_phone );

            // MoIP Args.
            $moip_args = array(
                'id_carteira'         => $this->login,
                'valor'               => str_replace( array( ',', '.' ) , '', $order->order_total ),
                'nome'                => sanitize_text_field( get_bloginfo( 'name' ) ),

                // Sender info.
                'pagador_nome'        => $order->billing_first_name . ' ' . $order->billing_last_name,
                'pagador_email'       => $order->billing_email,
                'pagador_telefone'    => $order->billing_phone,
                //'pagador_cpf'
                //'pagador_celular'
                //'pagador_sexo'
                //'pagador_data_nascimento'

                // Address info.
                'pagador_logradouro'  => $order->billing_address_1,
                //'pagador_numero'
                'pagador_complemento' => $order->billing_address_2,
                //'pagador_bairro'
                'pagador_cep'         => $order->billing_postcode,
                'pagador_cidade'      => $order->billing_city,
                'pagador_estado'      => $order->billing_state,
                //'pagador_pais'        => $order->billing_country,

                // Payment Info.
                'id_transacao'        => $this->invoice_prefix . $order_id,

                // Shipping info.
                //'frete'
                //'peso_compra'

                // Return
                'url_retorno'         => $this->get_return_url( $order ),
            );

            // Cart Contents.
            $item_names = array();

            if ( sizeof( $order->get_items() ) > 0 ) {
                foreach ( $order->get_items() as $item ) {
                    if ( $item['qty'] ) {
                        $item_names[] = $item['name'] . ' x ' . $item['qty'];
                    }
                }
            }

            $moip_args['descricao'] = sprintf( __( 'Order %s' , 'wcmoip' ), $order->get_order_number() ) . " - " . implode( ', ', $item_names );

            // Shipping Cost item.
            if ( $order->get_shipping() > 0 ) {
                $moip_args['descricao'] .= ', ' . __( 'Shipping via', 'wcmoip' ) . ' ' . ucwords( $order->shipping_method_title );
            }

            $moip_args = apply_filters( 'woocommerce_moip_args', $moip_args );

            return $moip_args;
        }

        /**
         * Generate the MoIP button link.
         *
         * @param mixed $order_id
         * @return string
         */
        public function generate_moip_form( $order_id ) {
            global $woocommerce;

            $order = new WC_Order( $order_id );

            $moip_adr = $this->moip_url;

            $moip_args = $this->get_moip_args( $order );

            $moip_args_array = array();

            foreach ( $moip_args as $key => $value ) {
                $moip_args_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
            }

            $woocommerce->add_inline_js( '
                jQuery("body").block({
                        message: "<img src=\"' . esc_url( $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif' ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />'.__( 'Thank you for your order. We are now redirecting you to MoIP to make payment.', 'wcmoip' ).'",
                        overlayCSS:
                        {
                            background: "#fff",
                            opacity:    0.6
                        },
                        css: {
                            padding:         20,
                            textAlign:       "center",
                            color:           "#555",
                            border:          "3px solid #aaa",
                            backgroundColor: "#fff",
                            cursor:          "wait",
                            lineHeight:      "32px",
                            zIndex:          "9999"
                        }
                    });
                jQuery("#submit_moip_payment_form").click();
            ' );

            return '<form action="' . esc_url( $moip_adr ) . '" method="post" id="moip_payment_form" accept-charset="ISO-8859-1" target="_top">
                    ' . implode( '', $moip_args_array ) . '
                    <input type="submit" class="button alt" id="submit_moip_payment_form" value="' . __( 'Pay via MoIP', 'wcmoip' ).'" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'wcmoip' ) . '</a>
                </form>';

        }

        /**
         * Process the payment and return the result.
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {

            $order = new WC_Order( $order_id );

            return array(
                'result'    => 'success',
                'redirect'  => add_query_arg( 'order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink( woocommerce_get_page_id( 'pay' ) ) ) )
            );

        }

        /**
         * Output for the order received page.
         *
         * @return void
         */
        public function receipt_page( $order ) {
            global $woocommerce;

            echo '<p>' . __( 'Thank you for your order, please click the button below to pay with MoIP.', 'wcmoip' ).'</p>';

            echo $this->generate_moip_form( $order );

            // Remove cart.
            $woocommerce->cart->empty_cart();
        }

        /**
         * Check MoIP API Response.
         *
         * @return void
         */
        public function check_moip_ipn_response() {

            if ( isset( $_POST['cod_moip'] ) ) {

                @ob_clean();

                $posted = stripslashes_deep( $_POST );

                if ( isset( $_POST['id_transacao'] ) ) {

                    header( 'HTTP/1.0 200 OK' );

                    do_action( 'valid_moip_ipn_request', $posted );

                } else {

                    header( 'HTTP/1.0 404 Not Found' );

                }

            }

        }

        /**
         * Successful Payment!
         *
         * @param array $posted
         * @return void
         */
        public function successful_request( $posted ) {
            global $woocommerce;

            if ( !empty( $posted['id_transacao'] ) ) {
                $order_key = $posted['id_transacao'];
                $order_id = (int) str_replace( $this->invoice_prefix, '', $order_key );

                $order = new WC_Order( $order_id );

                // Checks whether the invoice number matches the order.
                // If true processes the payment.
                if ( $order->id === $order_id ) {

                    switch ( $posted['status_pagamento'] ) {
                        case '1':
                            $order->update_status( 'on-hold', __( 'Payment has already been made but not yet credited to Carteira MoIP.', 'wcmoip' ) );

                            break;
                        case '2':
                            $order->update_status( 'on-hold', __( 'Payment under review by MoIP.', 'wcmoip' ) );

                            break;
                        case '3':
                            $order->update_status( 'on-hold', __( 'Billet was printed and has not been paid yet.', 'wcmoip' ) );

                            break;
                        case '4':

                            // Order details.
                            if ( !empty( $posted['cod_moip'] ) ) {
                                update_post_meta(
                                    $order_id,
                                    __( 'MoIP Transaction ID', 'wcmoip' ),
                                    $posted['cod_moip']
                                );
                            }
                            if ( !empty( $posted['email_consumidor'] ) ) {
                                update_post_meta(
                                    $order_id,
                                    __( 'Payer email', 'wcmoip' ),
                                    $posted['email_consumidor']
                                );
                            }
                            if ( !empty( $posted['tipo_pagamento'] ) ) {
                                update_post_meta(
                                    $order_id,
                                    __( 'Payment type', 'wcmoip' ),
                                    $posted['tipo_pagamento']
                                );
                            }
                            if ( !empty( $posted['parcelas'] ) ) {
                                update_post_meta(
                                    $order_id,
                                    __( 'Number of parcels', 'wcmoip' ),
                                    $posted['parcelas']
                                );
                            }

                            // Payment completed.
                            $order->add_order_note( __( 'Payment completed and credited.', 'wcmoip' ) );
                            $order->payment_complete();

                            break;
                        case '5':
                            $order->update_status( 'cancelled', __( 'Payment canceled by MoIP.', 'wcmoip' ) );

                            break;
                        case '6':
                            $order->update_status( 'on-hold', __( 'Payment under review by MoIP.', 'wcmoip' ) );

                            break;
                        case '7':
                            $order->update_status( 'refunded', __( 'Payment was reversed by the payer, payee, payment institution or MoIP.', 'wcmoip' ) );

                            break;

                        default:
                            // No action xD.
                            break;
                    }
                }
            }
        }

        /**
         * Adds error message when not configured the MoIP email or username.
         *
         * @return string Error Mensage.
         */
        public function login_missing_message() {
            $message = '<div class="error">';
                $message .= '<p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should inform your email address in MoIP. %sClick here to configure!%s' , 'wcmoip' ), '<a href="' . get_admin_url() . 'admin.php?page=woocommerce_settings&amp;tab=payment_gateways">', '</a>' ) . '</p>';
            $message .= '</div>';

            echo $message;
        }

    } // class WC_MOIP_Gateway.
} // function wcmoip_gateway_load.
