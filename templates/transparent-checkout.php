<?php
/**
 * Transparent Checkout template.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p><?php echo apply_filters( 'woocommerce_moip_transparent_checkout_message', __( 'This payment will be processed by Moip Payments S/A.', 'woocommerce-moip' ) ); ?></p>

<form action="" method="post" id="woocommerce-moip-payment-form">
	<div class="product">
		<div class="woocommerce-tabs">
			<ul class="tabs">
				<?php if ( 'yes' == $this->credit_card ) : ?>
					<li class="active"><a href="#tab-credit-card"><?php _e( 'Credit Card', 'woocommerce-moip' ); ?></a></li>
				<?php endif; ?>

				<?php if ( 'yes' == $this->banking_debit ) : ?>
					<li><a href="#tab-banking-debit"><?php _e( 'Banking Debit', 'woocommerce-moip' ); ?></a></li>
				<?php endif; ?>

				<?php if ( 'yes' == $this->billet_banking ) : ?>
					<li><a href="#tab-billet"><?php _e( 'Billet Banking', 'woocommerce-moip' ); ?></a></li>
				<?php endif; ?>
			</ul>

			<?php if ( 'yes' == $this->credit_card ) : ?>
				<div id="tab-credit-card" class="panel entry-content" data-payment-method="CartaoCredito">
					<ul>
						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/mastercard.png', plugin_dir_path( __FILE__ ) ) ), __( 'Master Card', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="Mastercard" />
							</label>
						</li>
						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/visa.png', plugin_dir_path( __FILE__ ) ) ), __( 'Visa', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="Visa" />
							</label>
						</li>
						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/americanexpress.png', plugin_dir_path( __FILE__ ) ) ), __( 'American Express', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="AmericanExpress" />
							</label>
						</li>
						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/diners.png', plugin_dir_path( __FILE__ ) ) ), __( 'Diners', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="Diners" />
							</label>
						</li>
						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/hipercard.png', plugin_dir_path( __FILE__ ) ) ), __( 'Hipercard', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="Hipercard" />
							</label>
						</li>
						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/elo.png', plugin_dir_path( __FILE__ ) ) ), __( 'Hipercard', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="Elo" />
							</label>
						</li>
					</ul>
					<div class="form-group-wrap">
						<div class="form-group">
							<label for="credit-card-number"><?php _e( 'Credit card number', 'woocommerce-moip' ); ?></label>
							<input type="text" name="credit_card_number" id="credit-card-number" />
							<span class="description"><?php _e( 'Only digits', 'woocommerce-moip' ); ?></span>
						</div>
						<div class="form-group">
							<label for="credit-card-expiration-month"><?php _e( 'Expiration', 'woocommerce-moip' ); ?></label>
							<select name="credit_card_expiration_month" id="credit-card-expiration-month">
								<?php
									for ( $expiration_month = 1; $expiration_month <= 12; $expiration_month++ ) {
										echo sprintf( '<option value="%1$s">%1$s</option>', zeroise( $expiration_month, 2 ) );
									}
								?>
							</select>
							<select name="credit_card_expiration_year" id="credit-card-expiration-year">
								<?php
									for ( $expiration_year = date( 'Y' ); $expiration_year < ( date( 'Y' ) + 15 ); $expiration_year++ ) {
										echo sprintf( '<option value="%1$s">%1$s</option>', $expiration_year );
									}
								?>
							</select>
						</div>
						<div class="form-group">
							<label for="credit-card-security-code"><?php _e( 'Security code', 'woocommerce-moip' ); ?></label>
							<input type="text" name="credit_card_security_code" id="credit-card-security-code" size="5" />
						</div>
					</div>
					<div class="form-group-wrap">
						<div class="form-group">
							<label for="credit-card-name"><?php _e( 'Holder name', 'woocommerce-moip' ); ?></label>
							<input type="text" name="credit_card_name" id="credit-card-name" value="<?php echo esc_attr( $holder_default['name'] ); ?>" />
							<span class="description"><?php _e( 'As recorded on this card', 'woocommerce-moip' ); ?></span>
						</div>
						<div class="form-group">
							<label for="credit-card-birthdate-day"><?php _e( 'Holder birth date', 'woocommerce-moip' ); ?></label>
							<select name="credit_card_birthdate_day" id="credit-card-birthdate-day">
								<?php
									for ( $birthdate_day = 1; $birthdate_day <= 31; $birthdate_day++ ) {
										$birthdate_day = zeroise( $birthdate_day, 2 );

										echo sprintf( '<option value="%1$s"%2$s>%1$s</option>', $birthdate_day, selected( esc_attr( $holder_default['birthdate_day'] ), $birthdate_day, false ) );
									}
								?>
							</select>
							<select name="credit_card_birthdate_month" id="credit-card-birthdate-month">
								<?php
									for ( $birthdate_month = 1; $birthdate_month <= 12; $birthdate_month++ ) {
										$birthdate_month = zeroise( $birthdate_month, 2 );

										echo sprintf( '<option value="%1$s"%2$s>%1$s</option>', $birthdate_month, selected( esc_attr( $holder_default['birthdate_month'] ), $birthdate_month, false ) );
									}
								?>
							</select>
							<select name="credit_card_birthdate_year" id="credit-card-birthdate-year">
								<?php
									for ( $birthdate_year = ( date( 'Y' ) - 15 ); $birthdate_year > ( date( 'Y' ) - 100 ); $birthdate_year-- ) {
										echo sprintf( '<option value="%1$s"%2$s>%1$s</option>', $birthdate_year, selected( esc_attr( $holder_default['birthdate_year'] ), $birthdate_year, false ) );
									}
								?>
							</select>
						</div>
					</div>
					<div class="form-group-wrap">
						<div class="form-group">
							<label for="credit-card-phone"><?php _e( 'Holder phone', 'woocommerce-moip' ); ?></label>
							<input type="text" name="credit_card_phone" id="credit-card-phone" value="<?php echo esc_attr( $holder_default['phone'] ); ?>" />
						</div>
						<div class="form-group">
							<label for="credit-card-cpf"><?php _e( 'Holder CPF', 'woocommerce-moip' ); ?></label>
							<input type="text" name="credit_card_cpf" id="credit-card-cpf" value="<?php echo esc_attr( $holder_default['cpf'] ); ?>" />
						</div>
					</div>
					<div class="form-group-wrap">
						<div class="form-group">
							<label for="credit-card-installments"><?php _e( 'Installments in', 'woocommerce-moip' ); ?></label>
							<select name="credit_card_installments" id="credit-card-installments">
								<option value="1"><?php echo sprintf( __( '$ %s at sight', 'woocommerce-moip' ), str_replace( '.', ',', $order->order_total ) ); ?></option>
							</select>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( 'yes' == $this->banking_debit ) : ?>
				<div id="tab-banking-debit" class="panel entry-content" data-payment-method="DebitoBancario">
					<ul>
						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/bancodobrasil.png', plugin_dir_path( __FILE__ ) ) ), __( 'Banco do Brasil', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="BancoDoBrasil" />
							</label>
						</li>
						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/bradesco.png', plugin_dir_path( __FILE__ ) ) ), __( 'Bradesco', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="Bradesco" />
							</label>
						</li>

						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/itau.png', plugin_dir_path( __FILE__ ) ) ), __( 'Itau', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="Itau" />
							</label>
						</li>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( 'yes' == $this->billet_banking ) : ?>
				<div id="tab-billet" class="panel entry-content" data-payment-method="BoletoBancario">
					<ul>
						<li>
							<label>
								<?php echo sprintf( '<img src="%1$s" alt="%2$s" title="%2$s" />', apply_filters( 'woocommerce_moip_icon_mastercard', plugins_url( 'assets/images/boleto.png', plugin_dir_path( __FILE__ ) ) ), __( 'Billet Banking', 'woocommerce-moip' ) ); ?>
								<input type="radio" name="payment_institution" value="BoletoBancario" />
							</label>
						</li>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div id="MoipWidget" data-token="<?php echo $token; ?>" callback-method-success="wcMoipSuccess" callback-method-error="wcMoipFail"></div>
	<input type="hidden" name="order_id" id="woocommerce-moip-order-id" value="<?php echo intval( $order->id ); ?>" />
	<input type="hidden" name="redirect" id="woocommerce-moip-redirect" value="<?php echo $this->get_return_url( $order ); ?>" />

	<p>
		<a class="button cancel" href="<?php echo esc_url( $order->get_cancel_order_url() ) ?>"><?php _e( 'Cancel order &amp; restore cart', 'woocommerce-moip' ) ?></a>
		<span> </span>
		<a class="button alt" id="woocommerce-moip-submit" href="<?php echo $url; ?>"><?php _e( 'Pay order', 'woocommerce-moip' ) ?></a>
	</p>
</form>

<?php if ( 'yes' == $this->sandbox ) : ?>
	<script type="text/javascript" src="https://desenvolvedor.moip.com.br/sandbox/transparente/MoipWidget-v2.js" charset="UFT-8"></script>
<?php else : ?>
	<script type="text/javascript" src="https://www.moip.com.br/transparente/MoipWidget-v2.js" charset="UFT-8"></script>
<?php endif; ?>
