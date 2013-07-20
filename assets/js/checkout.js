/*global woocommerce_moip_params*/
var wcMoIPSuccess = function(data){
    alert(woocommerce_moip_params.message_success + "\n" + JSON.stringify(data));
    window.open(data.url);
};

var wcMoIPFail = function(data) {
    alert(woocommerce_moip_params.message_fail + "\n" + JSON.stringify(data));
};

jQuery(document).ready(function($) {
    // Tabs.
    $(".woocommerce-tabs .panel").not(":eq(0)").hide();

    $(".woocommerce-tabs ul.tabs li a").on("click", function(e) {
        e.preventDefault();

        var tab = $(this),
            tabs_wrapper = tab.closest(".woocommerce-tabs");

        $("ul.tabs li", tabs_wrapper).removeClass("active");
        $("div.panel", tabs_wrapper).hide();
        $("div" + tab.attr("href")).show();
        tab.parent().addClass("active");
    });

    $("#payment-form").on('submit', function(e) {
        e.preventDefault();

        alert('enviado');
    });
});
