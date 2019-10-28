/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

function processPaymentAmount()
{
    total = parseFloat($('#total').val());
    paymentAmount = parseFloat($('#checkoutPaymentAmount').val());
    paymentMethod = $('#checkoutPaymentMethod').children("option:selected").val();

    paymentReturn = paymentAmount - total;
    paymentReturn = paymentReturn || 0;
    if (paymentMethod != documentCashPaymentMethod) {
        if (paymentReturn > 0) {
            paymentReturn = 0;
            paymentAmount = total;
            $('#checkoutPaymentAmount').val(formatNumber(paymentAmount));
        }
    }

    $('#checkoutPaymentChange').val(formatNumber(paymentReturn));

    if (paymentReturn >= 0) {
        $("#savePaymentButton").prop('disabled', false);
        console.log('Cambio : ' + paymentReturn);
    } else {
        $("#savePaymentButton").prop('disabled', true);
        console.log('Falta : ' + paymentReturn);
    }
}

function showCashupModal() {
    $("#cashupModal").modal('show');
}

function showCheckoutModal() {
    document.getElementById("action").value = "save-document";
    document.getElementById("lines").value = JSON.stringify(getGridData());
    console.log(getGridData());

    total = formatNumber(document.getElementById("total").value);
    var modal = $('#checkoutModal');
    modal.find('.modal-title').text(total);
    modal.modal();

    $('#savePaymentButton').on('click', function(event) {
        var paymentData = {};

        paymentData['amount'] = $('#checkoutPaymentAmount').val();
        paymentData['method'] = $('#checkoutPaymentMethod').val();
        paymentData['change'] = $('#checkoutPaymentChange').val();

        document.getElementById("payments").value = JSON.stringify(paymentData);
        document.formSalesDocument.submit()
    });
}

$(document).ready(function() {
    $("#cashupButton").click(function() {
        showCashupModal();
    });

    $("#checkoutButton").click(function () {
        showCheckoutModal();
    });

    $("#checkoutPaymentAmount").keyup(function (e) {
        processPaymentAmount();
    });

    $('#checkoutPaymentMethod').change(function (e) {
        processPaymentAmount();
    });
});
