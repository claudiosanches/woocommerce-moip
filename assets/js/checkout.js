/* jshint undef: false */
/* global woocommerce_moip_params */
/* exported wcMoipSuccess, wcMoipFail, installmentsDisplay */

/**
 * Show the modal.
 */
function wcMoipModalShow( msg ) {
	if ( ! msg ) {
		msg = woocommerce_moip_params.processing;
	}

	jQuery( '#woocommerce-moip-modal' ).empty().append( msg );
	jQuery( '#woocommerce-moip-modal-wrap' ).show();
}

/**
 * HIde the modal.
 */
function wcMoipModalHide() {
	jQuery( '#woocommerce-moip-modal-wrap' ).hide();
}

/**
 * Countdown.
 */
function wcMoipRedirectTimer( time ) {
	if ( time > 0 ) {
		jQuery( '#redirect-timer' ).html( time );
	}
}

/**
 * Moip Success functions.
 */
var wcMoipSuccess = function( data ) {
	var method = jQuery( '#woocommerce-moip-payment-form .panel:visible' ).data( 'payment-method' ),
		message_wrap = jQuery('#woocommerce-moip-error'),
		timer = 10,
		ajax_data = {
			action: 'woocommerce_moip_transparent_checkout',
			security: woocommerce_moip_params.security,
			order_id: jQuery( '#woocommerce-moip-order-id' ).val(),
			method: method
		};

	if ( 'CartaoCredito' === method ) {
		ajax_data.code = data.CodigoMoIP;
		ajax_data.status = data.Status;
	} else {
		ajax_data.url = data.url;
	}

	jQuery.ajax({
		type: 'POST',
		url: woocommerce_moip_params.ajax_url,
		cache: false,
		data: ajax_data,
		success: function() {
			// Remove the blockUI.
			wcMoipModalHide();

			// Open new window if is billet or banking debit.
			if ( 'CartaoCredito' !== method ) {
				window.open( data.url, 'Moip', 'width=750, height=550, scrollbars=1' );
			}

			// Create a modal message.
			wcMoipModalShow( woocommerce_moip_params.redirecting );
			setInterval(function() {
				wcMoipRedirectTimer(timer--);
			}, 1000);

			// Redirect.
			setTimeout(function() {
				window.location.href=jQuery( '#woocommerce-moip-redirect' ).val();
			}, 10000);
		},
		error: function() {
			// Display de error message.
			message_wrap.empty();
			message_wrap.prepend( woocommerce_moip_params.ajax_fail );
			message_wrap.show();

			// Remove the blockUI.
			wcMoipModalHide();
		}
	});
};

/**
 * Moip Fail functions.
 */
var wcMoipFail = function( data ) {
	var message_wrap = jQuery( '#woocommerce-moip-error' ),
		is_object = function( a ) {
			return ( !!a ) && ( a.constructor === Object );
		},
		new_data = [];

	if ( is_object( data ) ) {
		new_data.push(data);
		data = new_data;
	}

	// Display de error messages.
	message_wrap.empty();
	message_wrap.prepend( '<ul style="margin: 0;"></ul>' );
	jQuery.each(data, function( key, value ) {
		jQuery( 'ul', message_wrap ).prepend( '<li>' + value.Mensagem + '</li>' );
	});
	message_wrap.show();

	// Remove the blockUI.
	wcMoipModalHide();
};

jQuery(document).ready(function($) {

	/**
	 * Modal.
	 */
	$( 'body' ).append( '<div id="woocommerce-moip-modal-wrap"><div id="woocommerce-moip-modal-bg"></div><div id="woocommerce-moip-modal"></div></div>' );

	/**
	 * Hijax.
	 */
	var submit_button = $( '#woocommerce-moip-submit' );
	submit_button.replaceWith( '<button type="submit" class="button alt" id="woocommerce-moip-submit">' + submit_button.text() + '</button>' );
	$( '#woocommerce-moip-payment-form .product' ).fadeIn();

	/**
	 * Messages.
	 */
	$( '.woocommerce' ).prepend( '<div id="woocommerce-moip-error" class="woocommerce-error" style="display: none;"></div>' );

	/**
	 * Tabs.
	 */
	$( '.woocommerce-tabs .panel' ).not( ':eq(0)' ).hide();
	$( '.woocommerce-tabs .panel:eq(0)' ).show();

	$( '.woocommerce-tabs ul.tabs li a' ).on( 'click', function( e ) {
		e.preventDefault();

		var tab = $( this ),
			tabs_wrapper = tab.closest( '.woocommerce-tabs' );

		$( 'ul.tabs li', tabs_wrapper).removeClass( 'active' );
		$( 'div.panel', tabs_wrapper).hide();
		$( 'div' + tab.attr( 'href' ) ).show();
		tab.parent().addClass( 'active' );
	});

	/**
	 * Moip installments.
	 */
	$( '#woocommerce-moip-payment-form input[name="payment_institution"]' ).on( 'click', function() {
		var method = $( '#woocommerce-moip-payment-form .panel:visible' ).data( 'payment-method' ),
			creditcard_wrap = $( '#tab-credit-card .form-group-wrap' ),
			select = $( '#credit-card-installments' );

		if ( 'CartaoCredito' === method ) {
			creditcard_wrap.fadeOut();
			creditcard_wrap.fadeIn();

			// Displays the installments.
			installmentsDisplay = function( data ) {
				if ( 0 === data.parcelas.length ) {
					return;
				}

				select.empty();
				$.each(data.parcelas, function( key, value ) {
					price = value.valor.replace( '.', ',' );
					total = value.valor_total.replace( '.', ',' );

					if ( '1' === value.quantidade ) {
						option = '<option value="' + value.quantidade + '">R$ ' + price + ' ' + woocommerce_moip_params.at_sight + '</option>';
					} else {
						option = '<option value="' + value.quantidade + '">' + value.quantidade + 'x ' + woocommerce_moip_params.of + ' R$ ' + price  + ' (R$ ' + total + ')</option>';
					}

					select.append(option);
				});
			};

			// Calculates the installments.
			MoipUtil.calcularParcela({
				instituicao: $( this ).val(),
				callback: 'installmentsDisplay'
			});
		}
	});

	/**
	 * Moip Submit.
	 */
	$( '#woocommerce-moip-payment-form' ).on( 'submit', function( e ) {
		e.preventDefault();

		var method = $( '#woocommerce-moip-payment-form .panel:visible' ).data( 'payment-method' ),
			institution = $( '#woocommerce-moip-payment-form input[name="payment_institution"]:checked' ).val(),
			message_wrap = jQuery( '#woocommerce-moip-error' ),
			settings = {};

		message_wrap.hide();

		if ( institution ) {
			if ( 'CartaoCredito' === method ) {
				settings.Forma = 'CartaoCredito';
				settings.Instituicao = institution;
				settings.Parcelas = $( '#credit-card-installments' ).val();
				settings.CartaoCredito = {
					Numero: $( '#credit-card-number' ).val(),
					Expiracao: $( '#credit-card-expiration-month' ).val() + '/' + $( '#credit-card-expiration-year' ).val(),
					CodigoSeguranca: $( '#credit-card-security-code' ).val(),
					Portador: {
						Nome: $( '#credit-card-name' ).val().toUpperCase(),
						DataNascimento: $( '#credit-card-birthdate-day' ).val() + '/' + $( '#credit-card-birthdate-month' ).val() + '/' + $( '#credit-card-birthdate-year' ).val(),
						Telefone: $( '#credit-card-phone' ).val(),
						Identidade: $( '#credit-card-cpf' ).val()
					}
				};
			} else if ( 'DebitoBancario' === method ) {
				settings.Forma = 'DebitoBancario';
				settings.Instituicao = institution;
			} else {
				settings.Forma = 'BoletoBancario';
			}

			// Create a modal message.
			wcMoipModalShow();

			// Process the Moip transparent checkout.
			new MoipWidget( settings );
		} else {
			// Display de error messages.
			message_wrap.empty();
			message_wrap.prepend( woocommerce_moip_params.method_empty );
			message_wrap.show();
		}
	});
});
