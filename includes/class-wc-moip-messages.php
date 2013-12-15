<?php
/**
 * WC Moip Messages Class.
 */
class WC_Moip_Messages {

	/**
	 * Processes the Moip status message to translate.
	 *
	 * @param  string $status Moip status message.
	 *
	 * @return string         Translated status message.
	 */
	public static function translate_status( $status ) {
		switch ( $status ) {
			case 'Autorizado':
				return __( 'Authorized', 'woocommerce-moip' );
				break;
			case 'Iniciado':
				return __( 'Initiate', 'woocommerce-moip' );
				break;
			case 'BoletoImpresso':
				return __( 'Billet Printed', 'woocommerce-moip' );
				break;
			case 'Concluido':
				return __( 'Concluded', 'woocommerce-moip' );
				break;
			case 'Cancelado':
				return __( 'Canceled', 'woocommerce-moip' );
				break;
			case 'EmAnalise':
				return __( 'In Review', 'woocommerce-moip' );
				break;
			case 'Estornado':
				return __( 'Reversed', 'woocommerce-moip' );
				break;
			case 'Reembolsado':
				return __( 'Refunded', 'woocommerce-moip' );
				break;
			default:
				break;
		}
	}

	/**
	 * Beginning of message
	 *
	 * @return string
	 */
	protected static function message_before() {
		return __( 'Your transaction has been processed by Moip Payments S/A.', 'woocommerce-moip' ) . '<br />';
	}

	/**
	 * End of message
	 *
	 * @return string
	 */
	protected static function message_after() {
		return __( 'If you have any questions regarding the transaction, please contact us or the Moip.', 'woocommerce-moip' );
	}

	/**
	 * Credit cart message.
	 *
	 * @param  string $status Moip transaction status.
	 * @param  int    $code   Moip transaction code.
	 *
	 * @return string
	 */
	public static function credit_cart_message( $status, $code ) {
		$message = self::message_before();
		$message .= sprintf( __( 'The status of your transaction is %s and the MoIP code is %s.', 'woocommerce-moip' ), '<strong>' . esc_attr( $status ) . '</strong>', '<strong>' . esc_attr( $code ) . '</strong>' ) . '<br />';
		$message .= self::message_after();

		return $message;
	}

	/**
	 * Debit message.
	 *
	 * @return string
	 */
	public static function debit_message() {
		$message = self::message_before();
		$message .= __( 'If you have not made ​​the payment, please click the button to your left to pay.', 'woocommerce-moip' ) . '<br />';
		$message .= self::message_after();

		return $message;
	}

	/**
	 * Debit email message.
	 *
	 * @return string
	 */
	public static function debit_email_message() {
		$message = self::message_before();
		$message .= __( 'If you have not made ​​the payment, please use the link below to pay.', 'woocommerce-moip' ) . '<br />';
		$message .= self::message_after();

		return $message;
	}

	/**
	 * Billet message.
	 *
	 * @return string
	 */
	public static function billet_message() {
		$message = self::message_before();
		$message .= __( 'If you have not yet received the billet, please click the button to the left to print it.', 'woocommerce-moip' ) . '<br />';
		$message .= self::message_after();

		return $message;
	}

	/**
	 * Billet email message.
	 *
	 * @return string
	 */
	public static function billet_email_message() {
		$message = self::message_before();
		$message .= __( 'If you have not yet received the billet, please use the link below to print it.', 'woocommerce-moip' ) . '<br />';
		$message .= self::message_after();

		return $message;
	}
}
