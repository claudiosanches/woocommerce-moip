(function ( $ ) {
	'use strict';

	$(function () {
		var api = $( '#woocommerce_moip_api' ),
			creditcard = $( '#woocommerce_moip_credit_card' ),
			installments = $( '#woocommerce_moip_installments' ),
			receipt = $( '#woocommerce_moip_installments_receipt' ),
			billet = $( '#woocommerce_moip_billet_banking' ),
			billetActive = $( '#woocommerce_moip_billet' ),
			billetTime = $( '#woocommerce_moip_billet_type_term' );

		// API Fields.
		function apiFieldsDisplay( api ) {
			var apiFields = $( '.form-table:eq(1) tr' ),
				paymentFields = $( '.form-table:eq(2), #mainform h4:eq(1), #mainform h4:eq(1) + p, .form-table:eq(3), #mainform h4:eq(2), .form-table:eq(4), #mainform h4:eq(3)' );

			if ( 'html' !== api ) {
				apiFields.show();
				paymentFields.show();
				installmentsSectionDisplay();
				billetSectionDisplay();
			} else {
				apiFields.not( 'tr:eq(0)' ).hide();
				paymentFields.not( 'tr:eq(0)' ).hide();
			}
		}
		apiFieldsDisplay( api.val() );

		api.on( 'change', function () {
			apiFieldsDisplay( $( this ).val() );
		});

		// Installments Rehearse field.
		function rehearseDisplay( receipt ) {
			var field = $( '.form-table:eq(3) tr:eq(5)' );

			if ( 'AVista' === receipt ) {
				field.show();
			} else {
				field.hide();
			}
		}

		receipt.on( 'change', function () {
			rehearseDisplay( $( this ).val() );
		});

		// Installments fields.
		function installmentsDisplay() {
			var fields = $( '.form-table:eq(3) tr' );

			if ( installments.is( ':checked' ) ) {
				fields.show();

				rehearseDisplay( receipt.val() );
			} else {
				fields.not( 'tr:eq(0)' ).hide();
			}
		}
		installmentsDisplay();

		installments.on( 'click', function () {
			installmentsDisplay();
		});

		// Installments section.
		function installmentsSectionDisplay() {
			var fields = $( '.form-table:eq(3), #mainform h4:eq(2)' );

			if ( creditcard.is( ':checked' ) && 'html' !== api.val() ) {
				fields.show();

				installmentsDisplay();
			} else {
				fields.not( 'tr:eq(0)' ).hide();
			}
		}
		installmentsSectionDisplay();

		creditcard.on( 'click', function () {
			installmentsSectionDisplay();
		});

		// Billet Time field.
		function billetTimeDisplay( billetTime ) {
			var field = $( '.form-table:eq(4) tr:eq(2)' );

			if ( 'no' !== billetTime ) {
				field.show();
			} else {
				field.hide();
			}
		}

		billetTime.on( 'change', function () {
			billetTimeDisplay( $( this ).val() );
		});

		// Billet fields.
		function billetDisplay() {
			var fields = $( '.form-table:eq(4) tr' );

			if ( billetActive.is( ':checked' ) ) {
				fields.show();

				billetTimeDisplay( billetTime.val() );
			} else {
				fields.not( 'tr:eq(0)' ).hide();
			}
		}
		billetDisplay();

		billetActive.on( 'click', function () {
			billetDisplay();
		});

		// Billet section.
		function billetSectionDisplay() {
			var fields = $( '.form-table:eq(4) tr, #mainform h4:eq(3)' );

			if ( billet.is( ':checked' ) && 'html' !== api.val() ) {
				fields.show();

				billetDisplay();
			} else {
				fields.hide();
			}
		}
		billetSectionDisplay();

		billet.on( 'click', function () {
			billetSectionDisplay();
		});
	});

}(jQuery));
