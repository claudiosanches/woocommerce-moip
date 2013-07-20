/* global woocommerce_moip_params */

/**
 * Display a message.
 */
function blockMessage(msg) {
    if (!msg) {
        msg = woocommerce_moip_params.processing;
    }

    jQuery("body").block({
        message: '<img src="' + woocommerce_moip_params.loader + '" alt="' + woocommerce_moip_params.processing + '" style="float: left; margin: 5px 10px; 0 0; display: block;" />' + msg,
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
            lineHeight:     "24px"
        }
    });
}

/**
 * Countdown.
 */
function redirectTimer(time) {
    if (time > 0) {
        jQuery("#redirect-timer").html(time);
    }
}

/**
 * Moip Success functions.
 */
var wcMoipSuccess = function(data) {
    var method = jQuery("#woocommerce-moip-payment-form .panel:visible").data("payment-method"),
        message_wrap = jQuery(".woocommerce-message"),
        timer = 10;

    // Remove the blockUI.
    jQuery.unblockUI();

    // Add meu blockUI.
    blockMessage(woocommerce_moip_params.redirecting);
    setInterval(function() {
        redirectTimer(timer--);
    }, 1000);

    // alert(JSON.stringify(data));

    if ("CartaoCredito" !== method) {
        window.open(data.url);
    }

    // Redirect.
    setTimeout(function() {
        window.location.href=jQuery("#woocommerce-moip-redirect").val();
    }, 10000);
};

/**
 * Moip Fail functions.
 */
var wcMoipFail = function(data) {
    var response = JSON.stringify(data),
        message_wrap = jQuery("#woocommerce-moip-error");

    // Remove the blockUI.
    jQuery.unblockUI();

    // Add the error messages.
    message_wrap.empty();
    message_wrap.prepend('<ul style="margin: 0;"></ul>');
    jQuery.each(data, function(key, value) {
        jQuery("ul", message_wrap).prepend("<li>" + value.Mensagem + "</li>");
    });
    message_wrap.show();
};

jQuery(document).ready(function($) {

    /**
     * Messages.
     */
    $(".woocommerce").prepend('<div id="woocommerce-moip-error" class="woocommerce-error" style="display: none;"></div>');

    /**
     * Tabs.
     */
    $(".woocommerce-tabs .panel").not(":eq(0)").hide();
    $(".woocommerce-tabs .panel:eq(0)").show();

    $(".woocommerce-tabs ul.tabs li a").on("click", function(e) {
        e.preventDefault();

        var tab = $(this),
            tabs_wrapper = tab.closest(".woocommerce-tabs");

        $("ul.tabs li", tabs_wrapper).removeClass("active");
        $("div.panel", tabs_wrapper).hide();
        $("div" + tab.attr("href")).show();
        tab.parent().addClass("active");
    });

    /**
     * Moip Submit.
     */
    $("#woocommerce-moip-payment-form").on("submit", function(e) {
        e.preventDefault();

        var method = $("#woocommerce-moip-payment-form .panel:visible").data("payment-method"),
            institution = $("#woocommerce-moip-payment-form input[name='payment_institution']:checked").val(),
            settings = {};

        if ("CartaoCredito" === method) {
            settings.Forma = "CartaoCredito";
            settings.Instituicao = institution;
            settings.Parcelas = $("#credit-card-installments").val();
            settings.CartaoCredito = {
                Numero: $("#credit-card-number").val(),
                Expiracao: $("#credit-card-expiration-month").val() + "/" + $("#credit-card-expiration-year").val(),
                CodigoSeguranca: $("#credit-card-security-code").val(),
                Portador: {
                    Nome: $("#ccredit-card-name").val(),
                    DataNascimento: $("#credit-card-birthdate-day").val() + "/" + $("#credit-card-birthdate-month").val() + "/" + $("#credit-card-birthdate-year").val(),
                    Telefone: $("#credit-card-phone").val(),
                    Identidade: $("#credit-card-cpf").val()
                }
            };
        } else if ("DebitoBancario" === method) {
            settings.Forma = "DebitoBancario";
            settings.Instituicao = institution;
        } else {
            settings.Forma = "BoletoBancario";
        }

        // Display a blockUI.
        blockMessage();

        // Process the Moip transparent checkout.
        new MoipWidget(settings);
    });
});
