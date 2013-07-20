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
        $this->icon           = apply_filters( 'woocommerce_moip_icon', plugins_url( 'assets/images/moip.png', __FILE__ ) );
        $this->has_fields     = false;

        $this->method_title   = __( 'MoIP', 'wcmoip' );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Display options.
        $this->title       = $this->settings['title'];
        $this->description = $this->settings['description'];

        // Gateway options.
        $this->login          = $this->settings['login'];
        $this->invoice_prefix = ! empty( $this->settings['invoice_prefix'] ) ? $this->settings['invoice_prefix'] : 'WC-';

        // API options.
        $this->api   = isset( $this->settings['api'] ) ? $this->settings['api'] : 'html';
        $this->token = isset( $this->settings['token'] ) ? $this->settings['token'] : '';
        $this->key   = isset( $this->settings['key'] ) ? $this->settings['key'] : '';

        // Payment methods.
        $this->billet_banking    = isset( $this->settings['billet_banking'] ) ? $this->settings['billet_banking'] : 'yes';
        $this->credit_card       = isset( $this->settings['credit_card'] ) ? $this->settings['credit_card'] : 'yes';
        $this->debit_card        = isset( $this->settings['debit_card'] ) ? $this->settings['debit_card'] : 'yes';
        $this->moip_wallet       = isset( $this->settings['moip_wallet'] ) ? $this->settings['moip_wallet'] : 'yes';
        $this->banking_debit     = isset( $this->settings['banking_debit'] ) ? $this->settings['banking_debit'] : 'yes';
        $this->financing_banking = isset( $this->settings['financing_banking'] ) ? $this->settings['financing_banking'] : 'no';

        // Installments options.
        $this->installments          = isset( $this->settings['installments'] ) ? $this->settings['installments'] : 'no';
        $this->installments_mininum  = isset( $this->settings['installments_mininum'] ) ? $this->settings['installments_mininum'] : 2;
        $this->installments_maxium   = isset( $this->settings['installments_maxium'] ) ? $this->settings['installments_maxium'] : 12;
        $this->installments_receipt  = isset( $this->settings['installments_receipt'] ) ? $this->settings['installments_receipt'] : 'AVista';
        $this->installments_interest = isset( $this->settings['installments_interest'] ) ? $this->settings['installments_interest'] : 0;
        $this->installments_rehearse = isset( $this->settings['installments_rehearse'] ) ? $this->settings['installments_rehearse'] : 'no';

        // Billet options.
        $this->billet                   = isset( $this->settings['billet'] ) ? $this->settings['billet'] : 'no';
        $this->billet_type_term         = isset( $this->settings['billet_type_term'] ) ? $this->settings['billet_type_term'] : 'no';
        $this->billet_number_days       = isset( $this->settings['billet_number_days'] ) ? $this->settings['billet_number_days'] : '7';
        $this->billet_instruction_line1 = isset( $this->settings['billet_instruction_line1'] ) ? $this->settings['billet_instruction_line1'] : '';
        $this->billet_instruction_line2 = isset( $this->settings['billet_instruction_line2'] ) ? $this->settings['billet_instruction_line2'] : '';
        $this->billet_instruction_line3 = isset( $this->settings['billet_instruction_line3'] ) ? $this->settings['billet_instruction_line3'] : '';
        $this->billet_logo              = isset( $this->settings['billet_logo'] ) ? $this->settings['billet_logo'] : '';

        // Debug options.
        $this->sandbox = $this->settings['sandbox'];
        $this->debug   = $this->settings['debug'];

        // Actions.
        add_action( 'woocommerce_api_wc_moip_gateway', array( &$this, 'check_ipn_response' ) );
        add_action( 'valid_moip_ipn_request', array( &$this, 'successful_request' ) );
        add_action( 'woocommerce_receipt_moip', array( &$this, 'receipt_page' ) );
        add_action( 'wp_enqueue_scripts', array( &$this, 'scripts' ) );
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) )
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
        else
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );

        // Valid for use.
        if ( 'html' == $this->api ) {
            $this->enabled = ( 'yes' == $this->settings['enabled'] ) && ! empty( $this->token ) && ! empty( $this->key ) && $this->is_valid_for_use();

            // Checks if token is not empty.
            if ( empty( $this->token ) )
                add_action( 'admin_notices', array( &$this, 'token_missing_message' ) );

            // Checks if key is not empty.
            if ( empty( $this->key ) )
                add_action( 'admin_notices', array( &$this, 'key_missing_message' ) );

        } else {
            $this->enabled = ( 'yes' == $this->settings['enabled'] ) && ! empty( $this->login ) && $this->is_valid_for_use();

            // Checks if login is not empty.
            if ( empty( $this->login ) )
                add_action( 'admin_notices', array( &$this, 'login_missing_message' ) );
        }

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
     * Call plugin scripts in front-end.
     *
     * @return void
     */
    public function scripts() {
        if ( 'tc' == $this->api && is_checkout() ) {
            wp_enqueue_style( 'wc-moip-checkout', plugins_url( 'assets/css/checkout.css', __FILE__ ), array(), '', 'all' );
            wp_enqueue_script( 'wc-moip-checkout', plugins_url( 'assets/js/checkout.min.js', __FILE__ ), array( 'jquery' ), '', true );
            wp_localize_script(
                'wc-moip-checkout',
                'woocommerce_moip_params',
                array(
                    'message_success' => __( 'Success', 'wcmoip' ),
                    'message_fail' => __( 'Fail', 'wcmoip' )
                )
            );
        }
    }

    /**
     * Admin Panel Options.
     */
    public function admin_options() {
        wp_enqueue_script( 'wc-correios', plugins_url( 'assets/js/admin.min.js', __FILE__ ), array( 'jquery' ), '', true );
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
            'invoice_prefix' => array(
                'title' => __( 'Invoice Prefix', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Please enter a prefix for your invoice numbers. If you use your MoIP account for multiple stores ensure this prefix is unqiue as MoIP will not allow orders with the same invoice number.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => 'WC-'
            ),
            'api_section' => array(
                'title' => __( 'Payment API', 'wcmoip' ),
                'type' => 'title',
                'description' => '',
            ),
            'api' => array(
                'title' => __( 'MoIP Payment API', 'wcmoip' ),
                'type' => 'select',
                'description' => sprintf( __( 'The XML API requires Access Token and Access Key. %sHere\'s how to get this information%s.', 'wcmoip' ), '<a href="https://labs.moip.com.br/blog/pergunta-do-usuario-como-obter-o-token-e-a-chave-de-acesso-da-api-do-moip/" target="_blank">', '</a>' ),
                'default' => 'form',
                'options' => array(
                    'html' => __( 'HTML - Basic and less safe', 'wcmoip' ),
                    'xml' => __( 'XML - Safe and with more options', 'wcmoip' ),
                    'tc' => __( 'Transparent Checkout', 'wcmoip' )
                )
            ),
            'token' => array(
                'title' => __( 'Access Token', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Please enter your Access Token; this is needed in order to take payment.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => ''
            ),
            'key' => array(
                'title' => __( 'Access Key', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Please enter your Access Key; this is needed in order to take payment.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => ''
            ),
            'payment_section' => array(
                'title' => __( 'Payment Settings', 'wcmoip' ),
                'type' => 'title',
                'description' => __( 'These options need to be available to you in your MoIP account.', 'wcmoip' ),
            ),
            'billet_banking' => array(
                'title' => __( 'Billet Banking', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Billet Banking', 'wcmoip' ),
                'default' => 'yes'
            ),
            'credit_card' => array(
                'title' => __( 'Credit Card', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Credit Card', 'wcmoip' ),
                'default' => 'yes'
            ),
            'debit_card' => array(
                'title' => __( 'Debit Card', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Debit Card', 'wcmoip' ),
                'default' => 'yes'
            ),
            'moip_wallet' => array(
                'title' => __( 'MoIP Wallet', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable MoIP Wallet', 'wcmoip' ),
                'default' => 'yes'
            ),
            'banking_debit' => array(
                'title' => __( 'Banking Debit', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Banking Debit', 'wcmoip' ),
                'default' => 'yes'
            ),
            'financing_banking' => array(
                'title' => __( 'Financing Banking', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Financing Banking', 'wcmoip' ),
                'default' => 'yes'
            ),
            'installments_section' => array(
                'title' => __( 'Credit Card Installments Settings', 'wcmoip' ),
                'type' => 'title',
                'description' => '',
            ),
            'installments' => array(
                'title' => __( 'Installments settings', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Installments settings', 'wcmoip' ),
                'default' => 'no'
            ),
            'installments_mininum' => array(
                'title' => __( 'Minimum Installment', 'wcmoip' ),
                'type' => 'select',
                'description' => __( 'Indicate the minimum installments.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => '2',
                'options' => array(
                    2 => '2',
                    3 => '3',
                    4 => '4',
                    5 => '5',
                    6 => '6',
                    7 => '7',
                    8 => '8',
                    9 => '9',
                    10 => '10',
                    11 => '11',
                    12 => '12'
                )
            ),
            'installments_maxium' => array(
                'title' => __( 'Maximum Installment', 'wcmoip' ),
                'type' => 'select',
                'description' => __( 'Indicate the Maximum installments.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => '12',
                'options' => array(
                    2 => '2',
                    3 => '3',
                    4 => '4',
                    5 => '5',
                    6 => '6',
                    7 => '7',
                    8 => '8',
                    9 => '9',
                    10 => '10',
                    11 => '11',
                    12 => '12'
                )
            ),
            'installments_receipt' => array(
                'title' => __( 'Receipt', 'wcmoip' ),
                'type' => 'select',
                'description' => __( 'If the installment payment will in at sight (subject to additional costs) in your account MoIP (in one installment) or if it will be split (credited in the same number of parcels chosen by the payer).', 'wcmoip' ),
                'desc_tip' => true,
                'default' => 'yes',
                'options' => array(
                    'AVista' => __( 'At Sight', 'wcmoip' ),
                    'Parcelado' => __( 'Installments', 'wcmoip' ),
                )
            ),
            'installments_interest' => array(
                'title' => __( 'Interest', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Interest to be applied to the installment.', 'wcmoip' ),
                'desc_tip' => true,
                'placeholder' => '0.00',
                'default' => ''
            ),
            'installments_rehearse' => array(
                'title' => __( 'Rehearse', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Rehearse', 'wcmoip' ),
                'default' => 'no',
                'description' => __( 'Defines if the installment will be paid by the payer.', 'wcmoip' ),
            ),
            'billet_section' => array(
                'title' => __( 'Billet Settings', 'wcmoip' ),
                'type' => 'title',
                'description' => '',
            ),
            'billet' => array(
                'title' => __( 'Billet settings', 'wcmoip' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Billet settings', 'wcmoip' ),
                'default' => 'no'
            ),
            'billet_type_term' => array(
                'title' => __( 'Type of Term', 'wcmoip' ),
                'type' => 'select',
                'description' => '',
                'default' => 'no',
                'options' => array(
                    'no' => __( 'Default', 'wcmoip' ),
                    'Corridos' => __( 'Calendar Days', 'wcmoip' ),
                    'Uteis' => __( 'Working Days', 'wcmoip' )
                )
            ),
            'billet_number_days' => array(
                'title' => __( 'Number of Days', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Days of expiry of the billet after printed.', 'wcmoip' ),
                'desc_tip' => true,
                'placeholder' => '7',
                'default' => '7'
            ),
            'billet_instruction_line1' => array(
                'title' => __( 'Instruction Line 1', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'First line instruction for the billet.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => ''
            ),
            'billet_instruction_line2' => array(
                'title' => __( 'Instruction Line 2', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Second line instruction for the billet.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => ''
            ),
            'billet_instruction_line3' => array(
                'title' => __( 'Instruction Line 3', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'Third line instruction for the billet.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => ''
            ),
            'billet_logo' => array(
                'title' => __( 'Custom Logo URL', 'wcmoip' ),
                'type' => 'text',
                'description' => __( 'URL of the logo image to be shown on the billet.', 'wcmoip' ),
                'desc_tip' => true,
                'default' => ''
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
     * Add error message in checkout.
     *
     * @param string $message Error message.
     *
     * @return string         Displays the error message.
     */
    protected function add_error( $message ) {
        global $woocommerce;

        if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) )
            wc_add_error( $message );
        else
            $woocommerce->add_error( $message );
    }

    /**
     * Generate the args to form.
     *
     * @param  object $order Order data.
     *
     * @return array         Form arguments.
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
            // 'pagador_bairro'
            'pagador_cep'         => $order->billing_postcode,
            'pagador_cidade'      => $order->billing_city,
            'pagador_estado'      => $order->billing_state,
            // 'pagador_pais'        => $order->billing_country,

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
     * Generate XML payment args.
     *
     * @param  object $order Order data.
     *
     * @return string        Payment xml.
     */
    protected function get_payment_xml( $order ) {
        $data = $this->get_form_args( $order );

        $number = isset( $data['pagador_numero'] ) ? $data['pagador_numero'] : 0;
        $neighborhood = isset( $data['pagador_bairro'] ) ? $data['pagador_bairro'] : __( 'Not contained', 'wcmoip' );

        $xml = new SimpleXmlElement( '<?xml version="1.0" encoding="utf-8" ?><EnviarInstrucao></EnviarInstrucao>' );
        $instruction = $xml->addChild( 'InstrucaoUnica' );
        if ( 'tc' == $this->api )
            $instruction->addAttribute( 'TipoValidacao', 'Transparente' );
        $instruction->addChild( 'Razao', $data['descricao'] );
        $values = $instruction->addChild( 'Valores' );
        $values->addChild( 'Valor', $order->order_total );
        $values->addAttribute( 'moeda', 'BRL' );
        $instruction->addChild( 'IdProprio', $data['id_transacao'] );

        // Payer.
        $payer = $instruction->addChild( 'Pagador' );
        $payer->addChild( 'Nome', $data['pagador_nome'] );
        $payer->addChild( 'Email', $data['pagador_email'] );
        $payer->addChild( 'IdPagador', $data['pagador_email'] );

        // Address.
        $address = $payer->addChild( 'EnderecoCobranca' );
        $address->addChild( 'Logradouro', $data['pagador_logradouro'] );
        $address->addChild( 'Numero', $number );
        $address->addChild( 'Bairro', $neighborhood );
        $address->addChild( 'Complemento', $data['pagador_complemento'] );
        $address->addChild( 'Cidade', $data['pagador_cidade'] );
        $address->addChild( 'Estado', $data['pagador_estado'] );
        $address->addChild( 'Pais', 'BRA' );
        $address->addChild( 'CEP', $data['pagador_cep'] );
        $address->addChild( 'TelefoneFixo', $data['pagador_telefone'] );

        // Payment info.
        $payment = $instruction->addChild( 'FormasPagamento' );

        if ( 'yes' == $this->billet_banking ) {
            $payment->addChild( 'FormaPagamento', 'BoletoBancario' );

            // Billet settings.
            if ( 'yes' == $this->billet ) {
                $billet = $instruction->addChild( 'Boleto' );
                if ( 'no' != $billet->billet_type_term && ! empty( $this->billet_number_days ) ) {
                    $days = $billet->addChild( 'DiasExpiracao', (int) $this->billet_number_days );
                    $days->addAttribute( 'Tipo', $this->billet_type_term );
                }

                if ( ! empty( $this->billet_instruction_line1 ) )
                    $billet->addChild( 'Instrucao1', $this->billet_instruction_line1 );
                if ( ! empty( $this->billet_instruction_line2 ) )
                    $billet->addChild( 'Instrucao2', $this->billet_instruction_line2 );
                if ( ! empty( $this->billet_instruction_line3 ) )
                    $billet->addChild( 'Instrucao3', $this->billet_instruction_line3 );
                if ( ! empty( $this->billet_logo ) )
                    $billet->addChild( 'URLLogo', $this->billet_logo );
            }
        }

        if ( 'yes' == $this->credit_card ) {
            $payment->addChild( 'FormaPagamento', 'CartaoCredito' );

            // Installments info.
            if ( 'yes' == $this->installments ) {
                $installments = $instruction->addChild( 'Parcelamentos' );
                $installment = $installments->addChild( 'Parcelamento' );
                $installment->addChild( 'MinimoParcelas', $this->installments_mininum );
                $installment->addChild( 'MaximoParcelas', $this->installments_maxium );
                $installment->addChild( 'Recebimento', $this->receipt );
                if ( ! empty( $this->installments_interest ) && $this->installments_interest > 0 )
                    $installment->addChild( 'Juros', str_replace( ',', '.', $this->installments_interest ) );
                if ( 'AVista' == $this->installments_receipt ) {
                    $rehearse = ( 'yes' == $this->installments_rehearse ) ? 'true' : 'false';
                    $installment->addChild( 'Recebimento', $this->installments_rehearse );
                }
            }
        }

        if ( 'yes' == $this->debit_card )
            $payment->addChild( 'FormaPagamento', 'CartaoDebito' );
        if ( 'yes' == $this->moip_wallet )
            $payment->addChild( 'FormaPagamento', 'CarteiraMoIP' );
        if ( 'yes' == $this->banking_debit )
            $payment->addChild( 'FormaPagamento', 'DebitoBancario' );
        if ( 'yes' == $this->financing_banking )
            $payment->addChild( 'FormaPagamento', 'FinanciamentoBancario' );

        // Notification URL.
        $instruction->addChild( 'URLNotificacao', home_url( '/?wc-api=WC_MOIP_Gateway' ) );

        // Return URL.
        $instruction->addChild( 'URLRetorno', $this->get_return_url( $order ) );

        $xml = apply_filters( 'woocommerce_moip_xml', $xml, $order );

        return $xml->asXML();
    }

    /**
     * Gets the MoIP Payment Token
     *
     * @param  object $order Order data.
     *
     * @return string        Payment token.
     */
    protected function create_payment_token( $order ) {
        $xml = $this->get_payment_xml( $order );

        if ( 'yes' == $this->debug )
            $this->log->add( 'moip', 'Requesting token for order ' . $order->get_order_number() );

        if ( 'yes' == $this->sandbox )
            $url = 'https://desenvolvedor.moip.com.br/sandbox/ws/alpha/EnviarInstrucao/Unica';
        else
            $url = 'https://www.moip.com.br/ws/alpha/EnviarInstrucao/Unica';

        $params = array(
            'method'     => 'POST',
            'body'       => $xml,
            'sslverify'  => false,
            'timeout'    => 30,
            'headers'    => array(
                'Expect' => '',
                'Content-Type' => 'application/xml;charset=UTF-8',
                'Authorization' => 'Basic ' . base64_encode( $this->token . ':' . $this->key )
            )
        );

        $response = wp_remote_post( $url, $params );

        if ( is_wp_error( $response ) ) {
            if ( 'yes' == $this->debug )
                $this->log->add( 'moip', 'WP_Error: ' . $response->get_error_message() );
        } elseif ( $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
            $body = new SimpleXmlElement( $response['body'], LIBXML_NOCDATA );

            if ( 'Sucesso' == $body->Resposta->Status ) {
                if ( 'yes' == $this->debug )
                    $this->log->add( 'moip', 'MoIP Payment Token created with success! The Token is: ' . $body->Resposta->Token );

                return esc_attr( (string) $body->Resposta->Token );
            } else {
                if ( 'yes' == $this->debug )
                    $this->log->add( 'moip', 'Failed to generate the MoIP Payment Token: ' . print_r( $body->Resposta->Erro, true ) );

                foreach ( $body->Resposta->Erro as $error )
                    $this->add_error( '<strong>MoIP</strong>: ' . esc_attr( (string) $error ) );
            }

        } else {
            if ( 'yes' == $this->debug ) {
                $error = new SimpleXmlElement( $response['body'], LIBXML_NOCDATA );

                $this->log->add( 'moip', 'Failed to generate the MoIP Payment Token: ' . $response['response']['code'] . ' - ' . $response['response']['message'] );
            }
        }

        return false;
    }

    /**
     * Generate the form.
     *
     * @param int     $order_id Order ID.
     *
     * @return string           Payment form.
     */
    protected function generate_form( $order_id ) {
        global $woocommerce;

        $order = new WC_Order( $order_id );

        $args = $this->get_form_args( $order );

        if ( 'yes' == $this->debug )
            $this->log->add( 'moip', 'Payment arguments for order ' . $order->get_order_number() . ': ' . print_r( $args, true ) );

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

        $html = '<p>' . __( 'Thank you for your order, please click the button below to pay with MoIP.', 'wcmoip' ) . '</p>';

        $html .= '<form action="' . esc_url( $payment_url ) . '" method="post" id="payment-form" accept-charset="ISO-8859-1" target="_top">
                ' . implode( '', $args_array ) . '
                <input type="submit" class="button alt" id="submit-payment-form" value="' . __( 'Pay via MoIP', 'wcmoip' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'wcmoip' ) . '</a>
            </form>';

        return $html;
    }

    /**
     * Generate the form.
     *
     * @param int     $order_id Order ID.
     *
     * @return string           Payment form.
     */
    protected function generate_transparent_checkout( $order_id ) {
        // global $woocommerce;

        $order = new WC_Order( $order_id );

        $token = $this->create_payment_token( $order );

        if ( 'yes' == $this->debug )
            $this->log->add( 'moip', 'Generating transparent checkout for order ' . $order->get_order_number() );

        // if ( $token ) {
        if ( true ) {

            // Display the transparent checkout.
            $html = '<p>' . apply_filters( 'woocommerce_moip_transparent_checkout_message', __( 'This payment will be processed by MoIP Payments.', 'wcmoip' ) ) . '</p>';

            $html .= '<form action="" method="post" id="payment-form">';
                $html .= '<div class="product" id="woocommerce-moip-tc">';
                    $html .= '<div class="woocommerce-tabs">';
                        $html .= '<ul class="tabs">';
                            $html .= '<li class="active"><a href="#tab-credit-card">' .  __( 'Credit Card', 'wcmoip' ) . '</a></li>';
                            $html .= '<li><a href="#tab-banking-debit">' .  __( 'Banking Debit', 'wcmoip' ) . '</a></li>';
                            $html .= '<li><a href="#tab-billet">' .  __( 'Billet Banking', 'wcmoip' ) . '</a></li>';
                        $html .= '</ul>';
                        $html .= '<div id="tab-credit-card" class="panel entry-content" data-payment-method="CartaoCredito">';
                            $html .= '<ul>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="Mastercard" /> ' . __( 'Master Card', 'wcmoip' ) . '</label></li>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="Visa" /> ' . __( 'Visa', 'wcmoip' ) . '</label></li>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="AmericanExpress" /> ' . __( 'American Express', 'wcmoip' ) . '</label></li>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="Diners" /> ' . __( 'Diners', 'wcmoip' ) . '</label></li>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="Hipercard" /> ' . __( 'Hipercard', 'wcmoip' ) . '</label></li>';
                            $html .= '</ul>';
                        $html .= '</div>';
                        $html .= '<div id="tab-banking-debit" class="panel entry-content" data-payment-method="DebitoBancario">';
                            $html .= '<ul>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="BancoDoBrasil" /> ' . __( 'Banco do Brasil', 'wcmoip' ) . '</label></li>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="Bradesco" /> ' . __( 'Bradesco', 'wcmoip' ) . '</label></li>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="Banrisul" /> ' . __( 'Banrisul', 'wcmoip' ) . '</label></li>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="Itau" /> ' . __( 'Itau', 'wcmoip' ) . '</label></li>';
                            $html .= '</ul>';
                        $html .= '</div>';
                        $html .= '<div id="tab-billet" class="panel entry-content" data-payment-method="BoletoBancario">';
                            $html .= '<ul>';
                                $html .= '<li><label><input type="radio" name="payment_method" value="BoletoBancario" /> ' . __( 'Billet Banking', 'wcmoip' ) . '</label></li>';
                            $html .= '</ul>';
                        $html .= '</div>';
                    $html .= '</div>';
                $html .= '</div>';
                $html .= '<div id="MoipWidget" data-token="' . $token . '" callback-method-success="wcMoIPSuccess" callback-method-error="wcMoIPFail"></div>';
                $html .= '<input type="submit" class="button alt" id="submit-payment-form" value="' . __( 'Pay order', 'wcmoip' ) . '" /> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'wcmoip' ) . '</a>';
            $html .= '</form>';

            // Add MoIP Transparent Checkout JS.
            if ( 'yes' == $this->sandbox )
                $html .= '<script type="text/javascript" src="https://desenvolvedor.moip.com.br/sandbox/transparente/MoipWidget-v2.js" charset="ISO-8859-1"></script>';
            else
                $html .= '<script type="text/javascript" src="https://www.moip.com.br/transparente/MoipWidget-v2.js" charset="ISO-8859-1"></script>';

            return $html;
        } else {
            // Display message if a problem occurs.
            $html = '<p>' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'wcmoip' ) . '</p>';

            $html .= '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Click to try again', 'wcmoip' ) . '</a>';

            return $html;
        }
    }

    /**
     * Process the payment and return the result.
     *
     * @param int    $order_id Order ID.
     *
     * @return array           Redirect.
     */
    public function process_payment( $order_id ) {

        $order = new WC_Order( $order_id );

        if ( 'xml' == $this->api ) {

            $token = $this->create_payment_token( $order );

            if ( $token ) {
                if ( 'yes' == $this->sandbox )
                    $url = 'https://desenvolvedor.moip.com.br/sandbox/Instrucao.do?token=' . $token;
                else
                    $url = 'https://www.moip.com.br/Instrucao.do?token=' . $token;

                return array(
                    'result'   => 'success',
                    'redirect' => $url
                );
            }
        } else {
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
    }

    /**
     * Output for the order received page.
     *
     * @param  object $order Order data.
     *
     * @return void
     */
    public function receipt_page( $order ) {
        if ( 'tc' == $this->api )
            echo $this->generate_transparent_checkout( $order );
        else
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
     * @param array $posted MoIP post data.
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
        echo '<div class="error"><p>' . sprintf( __( '<strong>MoIP Disabled</strong> You should inform your email address in MoIP. %sClick here to configure!%s', 'wcmoip' ), '<a href="' . get_admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_MOIP_Gateway' ) . '">', '</a>' ) . '</p></div>';
    }

    /**
     * Adds error message when not configured the token.
     *
     * @return string Error Mensage.
     */
    public function token_missing_message() {
        echo '<div class="error"><p>' . sprintf( __( '<strong>MoIP Disabled</strong> You should inform your Access Token. %sClick here to configure!%s', 'wcmoip' ), '<a href="' . get_admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_MOIP_Gateway' ) . '">', '</a>' ) . '</p></div>';
    }

    /**
     * Adds error message when not configured the key.
     *
     * @return string Error Mensage.
     */
    public function key_missing_message() {
        echo '<div class="error"><p>' . sprintf( __( '<strong>MoIP Disabled</strong> You should inform your Access Key. %sClick here to configure!%s', 'wcmoip' ), '<a href="' . get_admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_MOIP_Gateway' ) . '">', '</a>' ) . '</p></div>';
    }
}
