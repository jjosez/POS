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

function calculatePaymentChange()
{
    documentTotal = parseFloat($('#doc_total').val());
    paymentAmount = parseFloat($('#payment-amount').val());
    paymentMethod = $('#payment-method').children("option:selected").val();

    paymentReturn = paymentAmount - documentTotal;
    paymentReturn = paymentReturn || 0;
    if (paymentMethod != PosDocCashPaymentMethod) {
        if (paymentReturn > 0) {
            paymentReturn = 0;
            paymentAmount = documentTotal;
            $('#payment-amount').val(formatNumber(paymentAmount));
        }
    }

    $('#payment-change').val(formatNumber(paymentReturn));

    if (paymentReturn >= 0) {
        $("#btn-payment-ok").prop('disabled', false);
        console.log('Cambio : ' + paymentReturn);
    } else {
        $("#btn-payment-ok").prop('disabled', true);
        console.log('Falta : ' + paymentReturn);
    }
}

function saveSalesDocument() {
    document.getElementById("action").value = "save-document";
    document.getElementById("lines").value = JSON.stringify(getGridData());

    console.log(getGridData());
    var modal = $('#payment-modal');
    modal.find('.modal-title').text(formatNumber(document.getElementById("doc_total").value));
    modal.modal();

    $('#btn-payment-ok').on('click', function(event) {
        var paymentData = {};

        paymentData['amount'] = $('#payment-amount').val();
        paymentData['method'] = $('#payment-method').val();
        paymentData['change'] = $('#payment-change').val();

        document.getElementById("payments").value = JSON.stringify(paymentData);
        document.formSalesDocument.submit()
    });
}

function showCashupModal() {
    $("#cashup-modal").modal('show');
}

$(document).ready(function() {

    $("#show-cashup-btn").click(function() {
        showCashupModal();
    });

    $("#save-document-btn").click(function () {
        saveSalesDocument();
    });

    $("#payment-amount").keyup(function (e) {
        calculatePaymentChange();
    });

});
