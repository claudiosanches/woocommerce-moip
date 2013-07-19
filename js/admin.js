jQuery(document).ready(function($) {
    var api = $('#woocommerce_moip_api');

    function apiFieldsDisplay() {
        var api_fields = $('.form-table:eq(1) tr'),
            payment_fields = $('.form-table:eq(2), #mainform h4:eq(1), #mainform h4:eq(1) + p');

        if ( api.is(':checked') ) {
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
});
