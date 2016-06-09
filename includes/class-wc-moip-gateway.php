<?php
/**
 * WC Moip Gateway Class.
 *
 * Built the Moip method.
 */
class WC_Moip_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 *
	 * @return void
	 */
	public function __construct() {

		$this->id             = 'moip';
		$this->icon           = apply_filters( 'woocommerce_moip_icon', plugins_url( 'assets/images/moip.png', plugin_dir_path( __FILE__ ) ) );
		$this->has_fields     = false;
		$this->method_title   = __( 'Moip', 'woocommerce-moip' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Display options.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		// Gateway options.
		$this->login          = $this->get_option( 'login' );
		$this->invoice_prefix = $this->get_option( 'invoice_prefix', 'WC-' );

		// API options.
		$this->api   = $this->get_option( 'api', 'html' );
		$this->token = $this->get_option( 'token' );
		$this->key   = $this->get_option( 'key' );

		// Payment methods.
		$this->billet_banking    = $this->get_option( 'billet_banking' );
		$this->credit_card       = $this->get_option( 'credit_card' );
		$this->debit_card        = $this->get_option( 'debit_card' );
		$this->moip_wallet       = $this->get_option( 'moip_wallet' );
		$this->banking_debit     = $this->get_option( 'banking_debit' );
		$this->financing_banking = $this->get_option( 'financing_banking' );

		// Installments options.
		$this->installments          = $this->get_option( 'installments', 'no' );
		$this->installments_mininum  = $this->get_option( 'installments_mininum', 2 );
		$this->installments_maxium   = $this->get_option( 'installments_maxium', 12 );
		$this->installments_receipt  = $this->get_option( 'installments_receipt', 'AVista' );
		$this->installments_interest = $this->get_option( 'installments_interest', 0 );
		$this->installments_rehearse = $this->get_option( 'installments_rehearse', 'no' );

		// Billet options.
		$this->billet                   = $this->get_option( 'billet', 'no' );
		$this->billet_type_term         = $this->get_option( 'billet_type_term', 'no' );
		$this->billet_number_days       = $this->get_option( 'billet_number_days', '7' );
		$this->billet_instruction_line1 = $this->get_option( 'billet_instruction_line1' );
		$this->billet_instruction_line2 = $this->get_option( 'billet_instruction_line2' );
		$this->billet_instruction_line3 = $this->get_option( 'billet_instruction_line3' );
		$this->billet_logo              = $this->get_option( 'billet_logo' );

		// Debug options.
		$this->sandbox = $this->get_option( 'sandbox' );
		$this->debug   = $this->get_option( 'debug' );

		// Actions.
		add_action( 'woocommerce_api_wc_moip_gateway', array( $this, 'check_ipn_response' ) );
		add_action( 'valid_moip_ipn_request', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_receipt_moip', array( $this, 'receipt_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ), 9999 );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Custom Thank You message.
		if ( 'tc' == $this->api ) {
			add_action( 'woocommerce_thankyou_moip', array( $this, 'transparent_checkout_thankyou_page' ) );
		}

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $this->woocommerce_instance()->logger();
			}
		}

		// Display admin notices.
		$this->admin_notices();
	}

	/**
	 * Backwards compatibility with version prior to 2.1.
	 *
	 * @return object Returns the main instance of WooCommerce class.
	 */
	protected function woocommerce_instance() {
		if ( function_exists( 'WC' ) ) {
			return WC();
		} else {
			global $woocommerce;
			return $woocommerce;
		}
	}

	/**
	 * Displays notifications when the admin has something wrong with the configuration.
	 *
	 * @return void
	 */
	protected function admin_notices() {
		if ( is_admin() ) {
			// Valid for use.
			if ( 'html' != $this->api ) {
				// Checks if token is not empty.
				if ( empty( $this->token ) ) {
					add_action( 'admin_notices', array( $this, 'token_missing_message' ) );
				}

				// Checks if key is not empty.
				if ( empty( $this->key ) ) {
					add_action( 'admin_notices', array( $this, 'key_missing_message' ) );
				}

			} else {
				// Checks if login is not empty.
				if ( empty( $this->login ) ) {
					add_action( 'admin_notices', array( $this, 'login_missing_message' ) );
				}
			}

			// Checks that the currency is supported
			if ( ! $this->using_supported_currency() ) {
				add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
			}
		}
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return ( 'BRL' == get_woocommerce_currency() );
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		if ( 'html' != $this->api ) {
			$api = ( ! empty( $this->token ) && ! empty( $this->key ) );
		} else {
			$api = ( ! empty( $this->login ) );
		}

		$available = ( 'yes' == $this->settings['enabled'] ) && $api && $this->using_supported_currency();

		return $available;
	}

	/**
	 * Call plugin scripts in front-end.
	 *
	 * @return void
	 */
	public function scripts() {
		if ( 'tc' == $this->api && is_checkout() ) {
			wp_enqueue_style( 'wc-moip-checkout', plugins_url( 'assets/css/checkout.css', plugin_dir_path( __FILE__ ) ), array(), '', 'all' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-blockui' );
			wp_enqueue_script( 'wc-moip-checkout', plugins_url( 'assets/js/checkout.min.js', plugin_dir_path( __FILE__ ) ), array( 'jquery', 'jquery-blockui' ), '', true );
			wp_localize_script(
				'wc-moip-checkout',
				'woocommerce_moip_params',
				array(
					'method_empty' => __( 'Please select a payment method.', 'woocommerce-moip' ),
					'processing' => __( 'Wait a few moments, your transaction is being processed...', 'woocommerce-moip' ),
					'redirecting' => sprintf( __( 'Thank you for your order, we will complete your order in %s seconds...', 'woocommerce-moip' ), '<span id="redirect-timer">10</span>' ),
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'security' => wp_create_nonce( 'woocommerce_moip_transparent_checkout' ),
					'ajax_fail' => __( 'There was an error in the request, please cancel the order and contact us to place your order.', 'woocommerce-moip' ),
					'at_sight' => __( 'at sight', 'woocommerce-moip' ),
					'of' => __( 'of', 'woocommerce-moip' )
				)
			);
		}
	}

	/**
	 * Admin Panel Options.
	 */
	public function admin_options() {
		wp_enqueue_script( 'wc-moip', plugins_url( 'assets/js/admin.min.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), '', true );

		echo '<h3>' . __( 'Moip standard', 'woocommerce-moip' ) . '</h3>';
		echo '<p>' . __( 'Moip standard works by sending the user to Moip to enter their payment information.', 'woocommerce-moip' ) . '</p>';

		// Generate the HTML For the settings form.
		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Moip standard', 'woocommerce-moip' ),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => __( 'Moip', 'woocommerce-moip' )
			),
			'description' => array(
				'title' => __( 'Description', 'woocommerce-moip' ),
				'type' => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-moip' ),
				'default' => __( 'Pay via Moip', 'woocommerce-moip' )
			),
			'login' => array(
				'title' => __( 'Moip Login', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'Please enter your Moip email address or username; this is needed in order to take payment.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => ''
			),
			'invoice_prefix' => array(
				'title' => __( 'Invoice Prefix', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your Moip account for multiple stores ensure this prefix is unqiue as Moip will not allow orders with the same invoice number.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => 'WC-'
			),
			'api_section' => array(
				'title' => __( 'Payment API', 'woocommerce-moip' ),
				'type' => 'title',
				'description' => '',
			),
			'api' => array(
				'title' => __( 'Moip Payment API', 'woocommerce-moip' ),
				'type' => 'select',
				'description' => sprintf( __( 'The XML and Checkout Transparent requires Access Token and Access Key. %sHere\'s how to get this information%s.', 'woocommerce-moip' ), '<a href="https://labs.moip.com.br/blog/pergunta-do-usuario-como-obter-o-token-e-a-chave-de-acesso-da-api-do-moip/" target="_blank">', '</a>' ),
				'default' => 'form',
				'options' => array(
					'html' => __( 'HTML - Basic and less safe', 'woocommerce-moip' ),
					'xml' => __( 'XML - Safe and with more options', 'woocommerce-moip' ),
					'tc' => __( 'Transparent Checkout', 'woocommerce-moip' )
				)
			),
			'token' => array(
				'title' => __( 'Access Token', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'Please enter your Access Token; this is needed in order to take payment.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => ''
			),
			'key' => array(
				'title' => __( 'Access Key', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'Please enter your Access Key; this is needed in order to take payment.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => ''
			),
			'payment_section' => array(
				'title' => __( 'Payment Settings', 'woocommerce-moip' ),
				'type' => 'title',
				'description' => __( 'These options need to be available to you in your Moip account.', 'woocommerce-moip' ),
			),
			'billet_banking' => array(
				'title' => __( 'Billet Banking', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Billet Banking', 'woocommerce-moip' ),
				'default' => 'yes'
			),
			'credit_card' => array(
				'title' => __( 'Credit Card', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Credit Card', 'woocommerce-moip' ),
				'default' => 'yes'
			),
			'debit_card' => array(
				'title' => __( 'Debit Card', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Debit Card', 'woocommerce-moip' ),
				'description' => __( 'Not available for Transparent Checkout.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => 'yes'
			),
			'moip_wallet' => array(
				'title' => __( 'Moip Wallet', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Moip Wallet', 'woocommerce-moip' ),
				'description' => __( 'Not available for Transparent Checkout.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => 'yes'
			),
			'banking_debit' => array(
				'title' => __( 'Banking Debit', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Banking Debit', 'woocommerce-moip' ),
				'default' => 'yes'
			),
			'financing_banking' => array(
				'title' => __( 'Financing Banking', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Financing Banking', 'woocommerce-moip' ),
				'description' => __( 'Not available for Transparent Checkout.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => 'yes'
			),
			'installments_section' => array(
				'title' => __( 'Credit Card Installments Settings', 'woocommerce-moip' ),
				'type' => 'title',
				'description' => '',
			),
			'installments' => array(
				'title' => __( 'Installments settings', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Installments settings', 'woocommerce-moip' ),
				'default' => 'no'
			),
			'installments_mininum' => array(
				'title' => __( 'Minimum Installment', 'woocommerce-moip' ),
				'type' => 'select',
				'description' => __( 'Indicate the minimum installments.', 'woocommerce-moip' ),
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
				'title' => __( 'Maximum Installment', 'woocommerce-moip' ),
				'type' => 'select',
				'description' => __( 'Indicate the Maximum installments.', 'woocommerce-moip' ),
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
				'title' => __( 'Receipt', 'woocommerce-moip' ),
				'type' => 'select',
				'description' => __( 'If the installment payment will in at sight (subject to additional costs) in your account Moip (in one installment) or if it will be split (credited in the same number of parcels chosen by the payer).', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => 'yes',
				'options' => array(
					'AVista' => __( 'At Sight', 'woocommerce-moip' ),
					'Parcelado' => __( 'Installments', 'woocommerce-moip' ),
				)
			),
			'installments_interest' => array(
				'title' => __( 'Interest', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'Interest to be applied to the installment.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'placeholder' => '0.00',
				'default' => ''
			),
			'installments_rehearse' => array(
				'title' => __( 'Rehearse', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Rehearse', 'woocommerce-moip' ),
				'default' => 'no',
				'description' => __( 'Defines if the installment will be paid by the payer.', 'woocommerce-moip' ),
			),
			'billet_section' => array(
				'title' => __( 'Billet Settings', 'woocommerce-moip' ),
				'type' => 'title',
				'description' => '',
			),
			'billet' => array(
				'title' => __( 'Billet settings', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Billet settings', 'woocommerce-moip' ),
				'default' => 'no'
			),
			'billet_type_term' => array(
				'title' => __( 'Type of Term', 'woocommerce-moip' ),
				'type' => 'select',
				'description' => '',
				'default' => 'no',
				'options' => array(
					'no' => __( 'Default', 'woocommerce-moip' ),
					'Corridos' => __( 'Calendar Days', 'woocommerce-moip' ),
					'Uteis' => __( 'Working Days', 'woocommerce-moip' )
				)
			),
			'billet_number_days' => array(
				'title' => __( 'Number of Days', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'Days of expiry of the billet after printed.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'placeholder' => '7',
				'default' => '7'
			),
			'billet_instruction_line1' => array(
				'title' => __( 'Instruction Line 1', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'First line instruction for the billet.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => ''
			),
			'billet_instruction_line2' => array(
				'title' => __( 'Instruction Line 2', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'Second line instruction for the billet.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => ''
			),
			'billet_instruction_line3' => array(
				'title' => __( 'Instruction Line 3', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'Third line instruction for the billet.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => ''
			),
			'billet_logo' => array(
				'title' => __( 'Custom Logo URL', 'woocommerce-moip' ),
				'type' => 'text',
				'description' => __( 'URL of the logo image to be shown on the billet.', 'woocommerce-moip' ),
				'desc_tip' => true,
				'default' => ''
			),
			'testing' => array(
				'title' => __( 'Gateway Testing', 'woocommerce-moip' ),
				'type' => 'title',
				'description' => '',
			),
			'sandbox' => array(
				'title' => __( 'Moip sandbox', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Moip sandbox', 'woocommerce-moip' ),
				'default' => 'no',
				'description' => sprintf( __( 'Moip sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'woocommerce-moip' ), 'http://labs.moip.com.br/' ),
			),
			'debug' => array(
				'title' => __( 'Debug Log', 'woocommerce-moip' ),
				'type' => 'checkbox',
				'label' => __( 'Enable logging', 'woocommerce-moip' ),
				'default' => 'no',
				'description' => sprintf( __( 'Log Moip events, such as API requests, inside %s', 'woocommerce-moip' ), '<code>woocommerce/logs/moip-' . sanitize_file_name( wp_hash( 'moip' ) ) . '.txt</code>' ),
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
	protected function register_error( $message ) {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			wc_add_notice( $message, 'error' );
		} else {
			$this->woocommerce_instance()->add_error( $message );
		}
	}

	/**
	 * Generate the args to form.
	 *
	 * @param  object $order Order data.
	 *
	 * @return array         Form arguments.
	 */
	public function get_form_args( $order ) {

		$args = array(
			'id_carteira'         => $this->login,
			'valor'               => str_replace( array( ',', '.' ), '', $order->order_total ),
			'nome'                => sanitize_text_field( get_bloginfo( 'name' ) ),

			// Sender info.
			'pagador_nome'        => $order->billing_first_name . ' ' . $order->billing_last_name,
			'pagador_email'       => $order->billing_email,
			'pagador_telefone'    => str_replace( array( '(', '-', ' ', ')' ), '', $order->billing_phone ),
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
				if ( $item['qty'] ) {
					$item_names[] = $item['name'] . ' x ' . $item['qty'];
				}
			}
		}

		$args['descricao'] = sprintf( __( 'Order %s', 'woocommerce-moip' ), $order->get_order_number() ) . ' - ' . implode( ', ', $item_names );

		// Shipping Cost item.
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			$shipping_total = $order->get_total_shipping();
		} else {
			$shipping_total = $order->get_shipping();
		}

		if ( $shipping_total > 0 ) {
			$args['descricao'] .= ', ' . __( 'Shipping via', 'woocommerce-moip' ) . ' ' . ucwords( $order->shipping_method_title );
		}

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
		// Include the WC_Moip_Gateway class.
		require_once plugin_dir_path( __FILE__ ) . 'class-wc-moip-simplexml.php';

		$data = $this->get_form_args( $order );

		$number = isset( $data['pagador_numero'] ) && ! empty( $data['pagador_numero'] ) ? $data['pagador_numero'] : 0;
		$neighborhood = isset( $data['pagador_bairro'] ) && ! empty( $data['pagador_bairro'] ) ? $data['pagador_bairro'] : __( 'Not contained', 'woocommerce-moip' );

		$xml = new WC_Moip_SimpleXML( '<?xml version="1.0" encoding="utf-8" ?><EnviarInstrucao></EnviarInstrucao>' );
		$instruction = $xml->addChild( 'InstrucaoUnica' );
		if ( 'tc' == $this->api ) {
			$instruction->addAttribute( 'TipoValidacao', 'Transparente' );
		}
		$instruction->addChild( 'Razao' )->addCData( $data['descricao'] );
		$values = $instruction->addChild( 'Valores' );
		$values->addChild( 'Valor', $order->order_total );
		$values->addAttribute( 'moeda', 'BRL' );
		$instruction->addChild( 'IdProprio', $data['id_transacao'] );

		// Payer.
		$payer = $instruction->addChild( 'Pagador' );
		$payer->addChild( 'Nome' )->addCData( $data['pagador_nome'] );
		$payer->addChild( 'Email', $data['pagador_email'] );
		$payer->addChild( 'IdPagador', $data['pagador_email'] );

		// Address.
		$address = $payer->addChild( 'EnderecoCobranca' );
		$address->addChild( 'Logradouro' )->addCData( $data['pagador_logradouro'] );
		$address->addChild( 'Numero', $number );
		$address->addChild( 'Bairro' )->addCData( $neighborhood );
		$address->addChild( 'Complemento' )->addCData( $data['pagador_complemento'] );
		$address->addChild( 'Cidade' )->addCData( $data['pagador_cidade'] );
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

				if ( ! empty( $this->billet_instruction_line1 ) ) {
					$billet->addChild( 'Instrucao1' )->addCData( $this->billet_instruction_line1 );
				}

				if ( ! empty( $this->billet_instruction_line2 ) ) {
					$billet->addChild( 'Instrucao2' )->addCData( $this->billet_instruction_line2 );
				}

				if ( ! empty( $this->billet_instruction_line3 ) ) {
					$billet->addChild( 'Instrucao3' )->addCData( $this->billet_instruction_line3 );
				}

				if ( ! empty( $this->billet_logo ) ) {
					$billet->addChild( 'URLLogo', $this->billet_logo );
				}
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
				$installment->addChild( 'Recebimento', $this->installments_receipt );

				if ( ! empty( $this->installments_interest ) && $this->installments_interest > 0 ) {
					$installment->addChild( 'Juros', str_replace( ',', '.', $this->installments_interest ) );
				}

				if ( 'AVista' == $this->installments_receipt ) {
					$rehearse = ( 'yes' == $this->installments_rehearse ) ? 'true' : 'false';
					$installment->addChild( 'Repassar', $rehearse );
				}
			}
		}

		if ( 'yes' == $this->debit_card ) {
			$payment->addChild( 'FormaPagamento', 'CartaoDebito' );
		}

		if ( 'yes' == $this->moip_wallet ) {
			$payment->addChild( 'FormaPagamento', 'CarteiraMoip' );
		}

		if ( 'yes' == $this->banking_debit ) {
			$payment->addChild( 'FormaPagamento', 'DebitoBancario' );
		}

		if ( 'yes' == $this->financing_banking ) {
			$payment->addChild( 'FormaPagamento', 'FinanciamentoBancario' );
		}

		// Notification URL.
		$instruction->addChild( 'URLNotificacao', home_url( '/?wc-api=WC_Moip_Gateway' ) );

		// Return URL.
		$instruction->addChild( 'URLRetorno' )->addCData( $this->get_return_url( $order ) );

		$xml = apply_filters( 'woocommerce_moip_xml', $xml, $order );

		return $xml->asXML();
	}

	/**
	 * Gets the Moip Payment Token
	 *
	 * @param  object $order Order data.
	 *
	 * @return string        Payment token.
	 */
	protected function create_payment_token( $order ) {
		$xml = $this->get_payment_xml( $order );

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'moip', 'Requesting token for order ' . $order->get_order_number() . ' with the following data: ' . $xml );
		}

		if ( 'yes' == $this->sandbox ) {
			$url = 'https://desenvolvedor.moip.com.br/sandbox/ws/alpha/EnviarInstrucao/Unica';
		} else {
			$url = 'https://www.moip.com.br/ws/alpha/EnviarInstrucao/Unica';
		}

		$params = array(
			'method'     => 'POST',
			'body'       => $xml,
			'sslverify'  => false,
			'timeout'    => 60,
			'headers'    => array(
				'Expect' => '',
				'Content-Type' => 'application/xml;charset=UTF-8',
				'Authorization' => 'Basic ' . base64_encode( $this->token . ':' . $this->key )
			)
		);

		$response = wp_remote_post( $url, $params );

		if ( is_wp_error( $response ) ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'moip', 'WP_Error: ' . $response->get_error_message() );
			}
		} elseif ( $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$body = new SimpleXmlElement( $response['body'], LIBXML_NOCDATA );

			if ( 'Sucesso' == $body->Resposta->Status ) {
				if ( 'yes' == $this->debug ) {
					$this->log->add( 'moip', 'Moip Payment Token created with success! The Token is: ' . $body->Resposta->Token );
				}

				return esc_attr( (string) $body->Resposta->Token );
			} else {
				if ( 'yes' == $this->debug ) {
					$this->log->add( 'moip', 'Failed to generate the Moip Payment Token: ' . print_r( $body->Resposta->Erro, true ) );
				}

				foreach ( $body->Resposta->Erro as $error ) {
					$this->register_error( '<strong>Moip</strong>: ' . esc_attr( (string) $error ) );
				}
			}

		} else {
			if ( 'yes' == $this->debug ) {
				$this->log->add( 'moip', 'Failed to generate the Moip Payment Token, the status was: ' . $response['response']['code'] . ' - ' . $response['response']['message'] . '. With the content: ' . $response['body'] );
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
		$order = new WC_Order( $order_id );

		$args = $this->get_form_args( $order );

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'moip', 'Payment arguments for order ' . $order->get_order_number() . ': ' . print_r( $args, true ) );
		}

		$args_array = array();

		foreach ( $args as $key => $value ) {
			$args_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}

		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			wc_enqueue_js( '
				jQuery.blockUI({
					message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to Moip to make payment.', 'woocommerce-moip' ) ) . '",
					baseZ: 99999,
					overlayCSS: {
						background: "#fff",
						opacity: 0.6
					},
					css: {
						padding:        "20px",
						zindex:         "9999999",
						textAlign:      "center",
						color:          "#555",
						border:         "3px solid #aaa",
						backgroundColor:"#fff",
						cursor:         "wait",
						lineHeight:		"24px",
					}
				});
				jQuery("#submit-payment-form").click();
			' );
		} else {
			$this->woocommerce_instance()->add_inline_js( '
				jQuery("body").block({
					message: "' . esc_js( __( 'Thank you for your order. We are now redirecting you to Moip to make payment.', 'woocommerce-moip' ) ) . '",
					overlayCSS: {
						background: "#fff",
						opacity: 0.6
					},
					css: {
						padding: "20px",
						zIndex: "9999999",
						textAlign: "center",
						color: "#555",
						border: "3px solid #aaa",
						backgroundColor: "#fff",
						cursor: "wait",
						lineHeight: "24px",
					}
				});
				jQuery("#submit-payment-form").click();
			' );
		}

		// Payment URL or Sandbox URL.
		if ( 'yes' == $this->sandbox ) {
			$payment_url = 'https://desenvolvedor.moip.com.br/sandbox/PagamentoMoIP.do';
		} else {
			$payment_url = 'https://www.moip.com.br/PagamentoMoIP.do';
		}

		$html = '<p>' . __( 'Thank you for your order, please click the button below to pay with Moip.', 'woocommerce-moip' ) . '</p>';

		$html .= '<form action="' . esc_url( $payment_url ) . '" method="post" id="payment-form" accept-charset="ISO-8859-1" target="_top">
				' . implode( '', $args_array ) . '
				<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce-moip' ) . '</a> <input type="submit" class="button alt" id="submit-payment-form" value="' . __( 'Pay via Moip', 'woocommerce-moip' ) . '" />
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
		$order = new WC_Order( $order_id );

		$token = $this->create_payment_token( $order );

		if ( 'yes' == $this->debug ) {
			$this->log->add( 'moip', 'Generating transparent checkout for order ' . $order->get_order_number() );
		}

		if ( $token ) {
			$holder_default = apply_filters( 'woocommerce_moip_holder_data', array(
				'name' => $order->billing_first_name . ' ' . $order->billing_last_name,
				'birthdate_day' => '',
				'birthdate_month' => '',
				'birthdate_year' => '',
				'phone' => $order->billing_phone,
				'cpf' => ''
			), $order );

			if ( 'yes' == $this->sandbox ) {
				$url = 'https://desenvolvedor.moip.com.br/sandbox/Instrucao.do?token=' . $token;
			} else {
				$url = 'https://www.moip.com.br/Instrucao.do?token=' . $token;
			}

			ob_start();
			include plugin_dir_path( dirname( __FILE__ ) ) . 'templates/transparent-checkout.php';
			$html = ob_get_clean();

			return $html;
		} else {
			// Display message if a problem occurs.
			$html = '<p>' . __( 'An error has occurred while processing your payment, please try again. Or contact us for assistance.', 'woocommerce-moip' ) . '</p>';
			$html .= '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Click to try again', 'woocommerce-moip' ) . '</a>';

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
				if ( 'yes' == $this->sandbox ) {
					$url = 'https://desenvolvedor.moip.com.br/sandbox/Instrucao.do?token=' . $token;
				} else {
					$url = 'https://www.moip.com.br/Instrucao.do?token=' . $token;
				}

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
		if ( 'tc' == $this->api ) {
			echo $this->generate_transparent_checkout( $order );
		} else {
			echo $this->generate_form( $order );
		}
	}

	/**
	 * Check API Response.
	 *
	 * @return void
	 */
	public function check_ipn_response() {
		@ob_clean();

		if ( isset( $_POST['id_transacao'] ) ) {
			header( 'HTTP/1.0 200 OK' );
			do_action( 'valid_moip_ipn_request', stripslashes_deep( $_POST ) );
		} else {
			wp_die( __( 'Moip Request Failure', 'woocommerce-moip' ) );
		}
	}

	/**
	 * Successful Payment!
	 *
	 * @param array $posted Moip post data.
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

				if ( 'yes' == $this->debug ) {
					$this->log->add( 'moip', 'Payment status from order ' . $order->get_order_number() . ': ' . $posted['status_pagamento'] );
				}

				switch ( $posted['status_pagamento'] ) {
					case '1':
						// Order details.
						if ( ! empty( $posted['cod_moip'] ) ) {
							update_post_meta(
								$order_id,
								__( 'Moip Transaction ID', 'woocommerce-moip' ),
								$posted['cod_moip']
							);
						}
						if ( ! empty( $posted['email_consumidor'] ) ) {
							update_post_meta(
								$order_id,
								__( 'Payer email', 'woocommerce-moip' ),
								$posted['email_consumidor']
							);
						}
						if ( ! empty( $posted['tipo_pagamento'] ) ) {
							update_post_meta(
								$order_id,
								__( 'Payment type', 'woocommerce-moip' ),
								$posted['tipo_pagamento']
							);
						}
						if ( ! empty( $posted['parcelas'] ) ) {
							update_post_meta(
								$order_id,
								__( 'Number of parcels', 'woocommerce-moip' ),
								$posted['parcelas']
							);
						}

						// Payment completed.
						$order->add_order_note( __( 'Moip: Payment has already been made but not yet credited to Carteira Moip.', 'woocommerce-moip' ) );
						$order->payment_complete();

						break;
					case '2':
						$order->update_status( 'on-hold', __( 'Moip: Payment under review.', 'woocommerce-moip' ) );

						break;
					case '3':
						$order->update_status( 'on-hold', __( 'Moip: Billet was printed and has not been paid yet.', 'woocommerce-moip' ) );

						break;
					case '4':
						$order->add_order_note( __( 'Moip: Payment completed and credited in your Carteira Moip.', 'woocommerce-moip' ) );

						break;
					case '5':
						$order->update_status( 'cancelled', __( 'Moip: Payment canceled.', 'woocommerce-moip' ) );

						break;
					case '6':
						$order->update_status( 'on-hold', __( 'Moip: Payment under review.', 'woocommerce-moip' ) );

						break;
					case '7':
						$order->update_status( 'refunded', __( 'Moip: Payment was reversed by the payer, payee, payment institution or Moip.', 'woocommerce-moip' ) );

						break;

					default:
						// No action xD.
						break;
				}

				do_action( 'woocommerce_moip_after_successful_request', $order, $posted );
			}
		}
	}

	/**
	 * Transparent checkout custom Thank You message.
	 *
	 * @return void
	 */
	public function transparent_checkout_thankyou_page() {
		$order_id = woocommerce_get_order_id_by_order_key( esc_attr( $_GET['key'] ) );
		$method = get_post_meta( $order_id, 'woocommerce_moip_method', true );

		switch ( $method ) {
			case 'CartaoCredito':

				$html = '<div class="woocommerce-message">';
				$message = WC_Moip_Messages::credit_cart_message( get_post_meta( $order_id, 'woocommerce_moip_status', true ), get_post_meta( $order_id, 'woocommerce_moip_code', true ) );
				$html .= apply_filters( 'woocommerce_moip_thankyou_creditcard_message', $message, $order_id );
				$html .= '</div>';

				break;
			case 'DebitoBancario':

				$html = '<div class="woocommerce-message">';
				$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', get_post_meta( $order_id, 'woocommerce_moip_url', true ), __( 'Pay the order &rarr;', 'woocommerce-moip' ) );
				$message = WC_Moip_Messages::debit_message();
				$html .= apply_filters( 'woocommerce_moip_thankyou_debit_message', $message, $order_id );
				$html .= '</div>';

				break;
			case 'BoletoBancario':

				$html = '<div class="woocommerce-message">';
				$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', get_post_meta( $order_id, 'woocommerce_moip_url', true ), __( 'Print the billet &rarr;', 'woocommerce-moip' ) );
				$message = WC_Moip_Messages::billet_message();
				$html .= apply_filters( 'woocommerce_moip_thankyou_billet_message', $message, $order_id );
				$html .= '</div>';

				break;

			default:
				$html = '';
				break;
		}

		echo $html;
	}

	/**
	 * Gets the admin url.
	 *
	 * @return string
	 */
	protected function admin_url() {
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '>=' ) ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_moip_gateway' );
		}

		return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Moip_Gateway' );
	}

	/**
	 * Adds error message when not configured the email or username.
	 *
	 * @return string Error Mensage.
	 */
	public function login_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'Moip Disabled', 'woocommerce-moip' ) . '</strong>: ' . sprintf( __( 'You should inform your email address. %s', 'woocommerce-moip' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'woocommerce-moip' ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when not configured the token.
	 *
	 * @return string Error Mensage.
	 */
	public function token_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'Moip Disabled', 'woocommerce-moip' ) . '</strong>: ' . sprintf( __( 'You should inform your Access Token. %s', 'woocommerce-moip' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'woocommerce-moip' ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when not configured the key.
	 *
	 * @return string Error Mensage.
	 */
	public function key_missing_message() {
		echo '<div class="error"><p><strong>' . __( 'Moip Disabled', 'woocommerce-moip' ) . '</strong>: ' . sprintf( __( 'You should inform your Access Key. %s', 'woocommerce-moip' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'woocommerce-moip' ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Adds error message when an unsupported currency is used.
	 *
	 * @return string Error Mensage.
	 */
	public function currency_not_supported_message() {
		echo '<div class="error"><p><strong>' . __( 'Moip Disabled', 'woocommerce-moip' ) . '</strong>: ' . sprintf( __( 'Currency <code>%s</code> is not supported. Works only with <code>BRL</code> (Brazilian Real).', 'woocommerce-moip' ), get_woocommerce_currency() ) . '</p></div>';
	}
}
