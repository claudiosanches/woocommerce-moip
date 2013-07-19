jQuery(document).ready(function($) {
    var api = $('#woocommerce_moip_api'),
        receipt = $('#woocommerce_moip_receipt');

    function apiFieldsDisplay() {
        var api_fields = $('.form-table:eq(1) tr'),
            payment_fields = $('.form-table:eq(2), #mainform h4:eq(1), #mainform h4:eq(1) + p, .form-table:eq(3)');

        if (api.is(':checked')) {
            api_fields.show();
            payment_fields.show();
        } else {
            api_fields.not('tr:eq(0)').hide();
            payment_fields.not('tr:eq(0)').hide();
        }
    }
    apiFieldsDisplay();

    api.on('click', function() {
        apiFieldsDisplay();
    });

    function rehearseDisplay(receipt) {
        var field = $('.form-table:eq(3) tr:eq(4)');

        if ('AVista' == receipt) {
            field.show();
        } else {
            field.hide();
        }
    }
    rehearseDisplay(receipt.val());

    receipt.on('change', function() {
        rehearseDisplay($(this).val());
    });

});
