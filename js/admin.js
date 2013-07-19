jQuery(document).ready(function($) {
    var api = $("#woocommerce_moip_api"),
        installments = $("#woocommerce_moip_installments"),
        receipt = $("#woocommerce_moip_receipt"),
        billet = $("#woocommerce_moip_billet"),
        billet_time = $("#woocommerce_moip_billet_time");

    // API Fields.
    function apiFieldsDisplay(api) {
        var api_fields = $(".form-table:eq(1) tr"),
            payment_fields = $(".form-table:eq(2), #mainform h4:eq(1), #mainform h4:eq(1) + p, .form-table:eq(3), #mainform h4:eq(2), .form-table:eq(4), #mainform h4:eq(3)");

        if ('xml' == api) {
            api_fields.show();
            payment_fields.show();
        } else {
            api_fields.not("tr:eq(0)").hide();
            payment_fields.not("tr:eq(0)").hide();
        }
    }
    apiFieldsDisplay(api.val());

    api.on("change", function() {
        apiFieldsDisplay($(this).val());
    });

    // Installments Rehearse field.
    function rehearseDisplay(receipt) {
        var field = $(".form-table:eq(3) tr:eq(5)");

        if ("AVista" == receipt) {
            field.show();
        } else {
            field.hide();
        }
    }

    receipt.on("change", function() {
        rehearseDisplay($(this).val());
    });

    // Installments fields.
    function installmentsDisplay() {
        var fields = $(".form-table:eq(3) tr");

        if (installments.is(":checked")) {
            fields.show();

            rehearseDisplay(receipt.val());
        } else {
            fields.not("tr:eq(0)").hide();
        }
    }
    installmentsDisplay();

    installments.on("click", function() {
        installmentsDisplay();
    });

    // Billet Time field.
    function billetTimeDisplay(billet_time) {
        var field = $(".form-table:eq(4) tr:eq(2)");

        if ("no" != billet_time) {
            field.show();
        } else {
            field.hide();
        }
    }

    billet_time.on("change", function() {
        billetTimeDisplay($(this).val());
    });

    // Billet fields.
    function billetDisplay() {
        var fields = $(".form-table:eq(4) tr");

        if (billet.is(":checked")) {
            fields.show();

            billetTimeDisplay(billet_time.val());
        } else {
            fields.not("tr:eq(0)").hide();
        }
    }
    billetDisplay();

    billet.on("click", function() {
        billetDisplay();
    });
});
