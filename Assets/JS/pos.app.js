/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

function processPaymentAmount()
{
    total = parseFloat($("#total").val());
    paymentAmount = parseFloat($("#checkoutPaymentAmount").val());
    paymentMethod = $("#checkoutPaymentMethod").children("option:selected").val();

    paymentReturn = paymentAmount - total;
    paymentReturn = paymentReturn || 0;
    if (paymentMethod !== documentCashPaymentMethod) {
        if (paymentReturn > 0) {
            paymentReturn = 0;
            paymentAmount = total;
            $('#checkoutPaymentAmount').val(formatNumber(paymentAmount));
        }
    }

    $("#checkoutPaymentChange").val(formatNumber(paymentReturn));

    if (paymentReturn >= 0) {
        $("#savePaymentButton").prop('disabled', false);
        console.log('Cambio : ' + paymentReturn);
    } else {
        $("#savePaymentButton").prop('disabled', true);
        console.log('Falta : ' + paymentReturn);
    }
}

function showCashupModal()
{

}

function showCheckoutModal()
{
    document.getElementById("action").value = "save-document";
    document.getElementById("lines").value = JSON.stringify(getGridData());
    console.log(getGridData());

    total = formatNumber(document.getElementById("total").value);
    var modal = $('#checkoutModal');
    modal.find('.modal-title').text(total);
    modal.modal();

    $('#savePaymentButton').on('click', function (event) {
        var paymentData = {};

        paymentData['amount'] = $('#checkoutPaymentAmount').val();
        paymentData['method'] = $('#checkoutPaymentMethod').val();
        paymentData['change'] = $('#checkoutPaymentChange').val();

        document.getElementById("payments").value = JSON.stringify(paymentData);
        document.formSalesDocument.submit()
    });
}
