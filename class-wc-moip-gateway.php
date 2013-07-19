<?php
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

        $this->id             = 'moip';
        $this->icon           = plugins_url( 'images/moip.png', __FILE__ );
        $this->has_fields     = false;

        $this->method_title   = __( 'MoIP', 'wcmoip' );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables.
        $this->title          = $this->settings['title'];
        $this->description    = $this->settings['description'];
        $this->login          = $this->settings['login'];
        $this->token          = $this->settings['token'];
        $this->key            = $this->settings['key'];
        $this->invoice_prefix = ! empty( $this->settings['invoice_prefix'] ) ? $this->settings['invoice_prefix'] : 'WC-';
        $this->sandbox        = $this->settings['sandbox'];
        $this->debug          = $this->settings['debug'];

        // Actions.
        add_action( 'woocommerce_api_wc_moip_gateway', array( &$this, 'check_ipn_response' ) );
        add_action( 'valid_moip_ipn_request', array( &$this, 'successful_request' ) );
        add_action( 'woocommerce_receipt_moip', array( &$this, 'receipt_page' ) );
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) )
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
        else
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

        // Valid for use.
        $this->enabled = ( 'yes' == $this->settings['enabled'] ) && ! empty( $this->login ) && $this->is_valid_for_use();

        // Checks if login is not empty.
        if ( empty( $this->login ) )
            add_action( 'admin_notices', array( &$this, 'login_missing_message' ) );

        // Active logs.
        if ( 'yes' == $this->debug )
            $this->log = $woocommerce->logger();
    }

    /**
     * Check if this gateway is enabled and available in the user's country.
     *
     * @return bool
     */
    public function is_valid_for_use() {
        if ( ! in_array( get_woocommerce_currency(), array( 'BRL' ) ) )
            return false;

        return true;
    }

    /**
     * Admin Panel Options.
     */
    public function admin_options() {
        ?>
        <h3><?php _e( 'MoIP standard', 'wcmoip' ); ?></h3>
        <p><?php _e( 'MoIP standard works by sending the user to MoIP to enter their payment information.', 'wcmoip' ); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
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
                'desc_tip' => true,
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
                'desc_tip' => true,
                'default' => ''
            ),
            'token' => array(
                'title' => __( 'MoIP API Token', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Please enter your MoIP API Token; this is needed in order to take payment.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => ''
            ),
            'key' => array(
                'title' => __( 'MoIP API Key', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Please enter your MoIP API Key; this is needed in order to take payment.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => ''
            ),
            'invoice_prefix' => array(
                'title' => __( 'Invoice Prefix', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Please enter a prefix for your invoice numbers. If you use your MoIP account for multiple stores ensure this prefix is unqiue as MoIP will not allow orders with the same invoice number.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => 'WC-'
            ),
            'testing' => array(
                'title' => __( 'Gateway Testing', 'wcmoip' ),
                'type' => 'title',
                'description' => '',
            ),
            'sandbox' => array(
                'title' => __( 'MoIP sandbox', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable MoIP sandbox', 'wcmoip' ),
                'default' => 'no',
                'description' => sprintf( __( 'MoIP sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'wcmoip' ), 'http://labs.moip.com.br/' ),
            ),
            'debug' => array(
                'title' => __( 'Debug Log', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable logging', 'wcmoip' ),
                'default' => 'no',
                'description' => sprintf( __( 'Log MoIP events, such as API requests, inside %s', 'wcmoip' ), '<code>woocommerce/logs/moip' . sanitize_file_name( wp_hash( 'moip' ) ) . '.txt</code>' ),
            )
        );
    }

    /**
     * Generate the args to form.
     *
     * @param  array $order Order data.
     *
     * @return array
     */
    public function get_form_args( $order ) {

        // Fixed phone number.
        $order->billing_phone = str_replace( array( '(', '-', ' ', ')' ), '', $order->billing_phone );

        $args = array(
            'id_carteira'         => $this->login,
            'valor'               => str_replace( array( ',', '.' ), '', $order->order_total ),
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
            'id_transacao'        => $this->invoice_prefix . $order->id,

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
                if ( $item['qty'] )
                    $item_names[] = $item['name'] . ' x ' . $item['qty'];
            }
        }

        $args['descricao'] = sprintf( __( 'Order %s', 'wcmoip' ), $order->get_order_number() ) . ' - ' . implode( ', ', $item_names );

        // Shipping Cost item.
        if ( $order->get_shipping() > 0 )
            $args['descricao'] .= ', ' . __( 'Shipping via', 'wcmoip' ) . ' ' . ucwords( $order->shipping_method_title );

        $args = apply_filters( 'woocommerce_moip_args', $args, $order );

        return $args;
    }

    /**
     * Generate the form.
     *
     * @param mixed $order_id
     *
     * @return string
     */
    public function generate_form( $order_id ) {
        global $woocommerce;

        $order = new WC_Order( $order_id );

        $args = $this->get_form_args( $order );

        if ( 'yes' == $this->debug )
            $this->log->add( 'moip', 'Payment arguments for order #' . $order_id . ': ' . print_r( $args, true ) );

        $args_array = array();

        foreach ( $args as $key => $value )
            $args_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';


        if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
            $woocommerce->get_helper( 'inline-javascript' )->add_inline_js( '
                $.blockUI({
                        message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to MoIP to make payment.', 'wcmoip' ) ) . '",
                        baseZ: 99999,
                        overlayCSS:
                        {
                            background: "#fff",
                            opacity: 0.6
                        },
                        css: {
                            padding:        "20px",
                            zIndex:         "9999999",
                            textAlign:      "center",
                            color:          "#555",
                            border:         "3px solid #aaa",
                            backgroundColor:"#fff",
                            cursor:         "wait",
                            lineHeight:     "24px",
                        }
                    });
                jQuery("#submit-payment-form").click();
            ' );
        } else {
            $woocommerce->add_inline_js( '
                jQuery("body").block({
                        message: "<img src=\"' . esc_url( $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif' ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />' . __( 'Thank you for your order. We are now redirecting you to MoIP to make payment.', 'wcmoip' ) . '",
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
                jQuery("#submit-payment-form").click();
            ' );
        }

        // Payment URL or Sandbox URL.
        if ( 'yes' == $this->sandbox )
            $payment_url = 'https://desenvolvedor.moip.com.br/sandbox/PagamentoMoIP.do';
        else
            $payment_url = 'https://www.moip.com.br/PagamentoMoIP.do';

        return '<form action="' . esc_url( $payment_url ) . '" method="post" id="payment-form" accept-charset="ISO-8859-1" target="_top">
                ' . implode( '', $args_array ) . '
                <input type="submit" class="button alt" id="submit-payment-form" value="' . __( 'Pay via MoIP', 'wcmoip' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'wcmoip' ) . '</a>
            </form>';
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment( $order_id ) {

        $order = new WC_Order( $order_id );

        if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
            return array(
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url( true )
            );
        } else {
            return array(
                'result'   => 'success',
                'redirect' => add_query_arg( 'order', $order->id, add_query_arg( 'key', $order->order_key, get_permalink( woocommerce_get_page_id( 'pay' ) ) ) )
            );
        }
    }

    /**
     * Output for the order received page.
     *
     * @return void
     */
    public function receipt_page( $order ) {
        global $woocommerce;

        echo '<p>' . __( 'Thank you for your order, please click the button below to pay with MoIP.', 'wcmoip' ) . '</p>';

        echo $this->generate_form( $order );
    }

    /**
     * Check API Response.
     *
     * @return void
     */
    public function check_ipn_response() {

        @ob_clean();

        $posted = stripslashes_deep( $_POST );

        if ( isset( $_POST['id_transacao'] ) ) {

            header( 'HTTP/1.0 200 OK' );

            do_action( 'valid_moip_ipn_request', $posted );

        } else {
            wp_die( __( 'MoIP Request Failure', 'wcmoip' ) );
        }
    }

    /**
     * Successful Payment!
     *
     * @param array $posted
     *
     * @return void
     */
    public function successful_request( $posted ) {

        if ( ! empty( $posted['id_transacao'] ) ) {
            $order_key = $posted['id_transacao'];
            $order_id = (int) str_replace( $this->invoice_prefix, '', $order_key );

            $order = new WC_Order( $order_id );

            // Checks whether the invoice number matches the order.
            // If true processes the payment.
            if ( $order->id === $order_id ) {

                if ( 'yes' == $this->debug )
                    $this->log->add( 'moip', 'Payment status from order ' . $order->get_order_number() . ': ' . $posted['status_pagamento'] );

                switch ( $posted['status_pagamento'] ) {
                    case '1':
                        // Order details.
                        if ( ! empty( $posted['cod_moip'] ) ) {
                            update_post_meta(
                                $order_id,
                                __( 'MoIP Transaction ID', 'wcmoip' ),
                                $posted['cod_moip']
                            );
                        }
                        if ( ! empty( $posted['email_consumidor'] ) ) {
                            update_post_meta(
                                $order_id,
                                __( 'Payer email', 'wcmoip' ),
                                $posted['email_consumidor']
                            );
                        }
                        if ( ! empty( $posted['tipo_pagamento'] ) ) {
                            update_post_meta(
                                $order_id,
                                __( 'Payment type', 'wcmoip' ),
                                $posted['tipo_pagamento']
                            );
                        }
                        if ( ! empty( $posted['parcelas'] ) ) {
                            update_post_meta(
                                $order_id,
                                __( 'Number of parcels', 'wcmoip' ),
                                $posted['parcelas']
                            );
                        }

                        // Payment completed.
                        $order->add_order_note( __( 'Payment has already been made but not yet credited to Carteira MoIP.', 'wcmoip' ) );
                        $order->payment_complete();

                        break;
                    case '2':
                        $order->update_status( 'on-hold', __( 'Payment under review by MoIP.', 'wcmoip' ) );

                        break;
                    case '3':
                        $order->update_status( 'on-hold', __( 'Billet was printed and has not been paid yet.', 'wcmoip' ) );

                        break;
                    case '4':
                        $order->add_order_note( __( 'Payment completed and credited in your Carteira MoIP.', 'wcmoip' ) );

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
     * Adds error message when not configured the email or username.
     *
     * @return string Error Mensage.
     */
    public function login_missing_message() {
        echo '<div class="error"><p>' . sprintf( __( '<strong>Gateway Disabled</strong> You should inform your email address in MoIP. %sClick here to configure!%s', 'wcmoip' ), '<a href="' . get_admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_MOIP_Gateway' ) . '">', '</a>' ) . '</p></div>';
    }
}
