/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
var cartItemsList = [];
var cartItemsContainer = $('#cartItemsContainer');
var cartTemplateSource = $('#cart-item-template').html();
var cartTemplate = Sqrl.Compile(cartTemplateSource);
var ajaxTemplateSource = $('#ajax-search-template').html();
var ajaxTemplate = Sqrl.Compile(ajaxTemplateSource);

function getCartData() {
    var lines = [];
    var n = 0;
    for (var key in cartItemsList) {
        lines[n] = cartItemsList[key].newLineData();
        n++;
    }
    return lines;
}

function onCartDelete(e) {
    let index = e.data('index');

    cartItemsList.splice( index, 1 );
    console.log('Index deleting:', index);
    onCartUpdate();
}

function onCartEdit(e) {
    let field = e.data('field');
    let index = e.data('index');

    cartItemsList[index][field] = e.val();
    console.log('Index editing:', index);
    onCartUpdate();
}

function onCartUpdate() {
    var data = {};
    $.each($("#" + posFormName).serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    console.log("Form data:", data);
    data.action = "recalculate-document";
    data.lines = getCartData();
    $.ajax({
        type: "POST",
        url: posUrlAccess,
        dataType: "json",
        data: data,
        startTime: performance.now(),
        success: function (results) {
            console.log("Request results: ", results);
            updateCartItemList(results.lines);
            $('#cartTotalDisplay').val(results.doc.total);
            $('#cartTaxesDisplay').val(results.doc.totaliva);
            $('#cartNetoDisplay').val(results.doc.netosindto);
            $('#total').val(results.doc.total);
            $('#neto').val(results.doc.neto);
            $('#totalsuplidos').val(results.doc.totalsuplidos);
            $('#totaliva').val(results.doc.totaliva);
            $('#totalirpf').val(results.doc.totalirpf);
            $('#totalrecargo').val(results.doc.totalrecargo);
            testResponseTime(this.startTime, 'Recalculation exec time:');
        },
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        }
    });
}

function updateCartItemList(items) {
    cartItemsList = [];
    for (let item of items) {
        //cartItemsList[item.referencia] = new CartItem(item);
        cartItemsList.push(new CartItem(item));
    }
    /// Hide search modal
    $('#ajaxSearchModal').modal('hide');
    /// Update cart view
    var html = cartTemplate({lines: items}, Sqrl);
    cartItemsContainer.html(html);
}

/* Search actions*/
function ajaxCustomSearch(query, target) {
    var data = {
        action: "custom-search",
        query: query,
        target: target
    };
    $.ajax({
        url: posUrlAccess,
        data: data,
        type: "POST",
        dataType: "json",
        startTime: performance.now(),
        success: function (data) {
            let html = ajaxTemplate({list: data, target: target}, Sqrl);
            $('#ajaxSearchResult').html(html);
            //testResponseTime(this.startTime);
        },
        error: function (xhr, status) {
            console.log('Error: ');
            console.log(xhr.responseText);
        }
    });
}

function setProduct(e) {
    for (let i = 0; i < cartItemsList.length; i++) {
        if (cartItemsList[i].referencia === e.data('code')) {
            cartItemsList[i].cantidad +=1;
            onCartUpdate();
            return;
        }
    }

    var cartItem = new CartItem({referencia: e.data('code'), descripcion: e.data('description')});
    cartItemsList.push(cartItem);

    onCartUpdate();
}

function setCustomer(e) {
    $('#codcliente').val(e.data('code'));
    $('#searchCustomer').val(e.data('description'));
    $('#ajaxSearchModal').modal('hide');
    $('#ajaxSearchResult').html('');
}

/*Payment calc*/
function recalculatePaymentAmount() {
    total = parseFloat($("#total").val());
    paymentAmountInput = $('#checkoutPaymentAmount');
    paymentAmount = paymentAmountInput.val();
    paymentMethod = $('#checkoutPaymentMethod').children("option:selected").val();
    paymentReturn = paymentAmount - total;
    paymentReturn = paymentReturn || 0;
    if (paymentMethod !== posCashPaymentMethod) {
        if (paymentReturn > 0) {
            paymentReturn = 0;
            paymentAmount = total;
            paymentAmountInput.val(formatNumber(paymentAmount));
        }
    }
    $('#checkoutPaymentChange').val(formatNumber(paymentReturn));
    if (paymentReturn >= 0) {
        $('#savePaymentButton').prop('disabled', false);
        console.log('Cambio : ' + paymentReturn);
    } else {
        $('#savePaymentButton').prop('disabled', true);
        console.log('Falta : ' + paymentReturn);
    }
}

function showCheckoutModal() {
    total = document.getElementById("total").value;
    var modal = $('#checkoutModal');
    modal.find('.modal-title').text(total);
    modal.modal();
    $('#savePaymentButton').on('click', function (event) {
        var paymentData = {};
        paymentData['amount'] = $('#checkoutPaymentAmount').val();
        paymentData['method'] = $('#checkoutPaymentMethod').val();
        paymentData['change'] = $('#checkoutPaymentChange').val();
        document.getElementById("action").value = "save-document";
        document.getElementById("lines").value = JSON.stringify(getCartData());
        document.getElementById("payments").value = JSON.stringify(paymentData);
        document.salesDocumentForm.submit()
    });
}

/* Helper functions */
function formatNumber(val) {
    return parseFloat(val).toFixed(2);
}

function testResponseTime(startTime, label = 'Exec time:') {
    //Calculate the difference in milliseconds.
    var time = performance.now() - startTime;
    //Convert milliseconds to seconds.
    var seconds = time / 1000;
    console.log(label, seconds.toFixed(3));
}

function loadTransactionHistory() {
    $.ajax({
        url: posUrlAccess,
        data: {action: 'load-hostory'},
        type: "POST",
        dataType: "json",
        success: function (data) {
            let templateSource = $('#history-template').html();
            let template = Sqrl.Compile(templateSource);
            let html = template({list: data}, Sqrl);
            $('#historyResult').html(html);
        },
        error: function (xhr, status) {
            console.log('Error: ');
            console.log(xhr.responseText);
        }
    });
}

$(document).ready(function () {
    'use strict'
    $('[data-toggle="offcanvas"]').on('click', function () {
        $('.offcanvas-collapse').toggleClass('open')
    });
    $('#cashupButton').click(function () {
        $('#cashupModal').modal('show');
    });
    $("#checkoutButton").click(function () {
        showCheckoutModal();
    });
    $("#checkoutPaymentAmount").keyup(function (e) {
        recalculatePaymentAmount();
    });
    $('#checkoutPaymentMethod').change(function (e) {
        recalculatePaymentAmount();
    });
    $('#historyModal').on('shown.bs.modal', function () {
        loadTransactionHistory();
    });

    /*Ajax Search Events*/
    $('#searchCustomer').focus(function () {
        $('#ajaxSearchResult').html('');
        $('#ajaxSearchInput').data('target', 'customer');
        $('#ajaxSearchModal').modal('show');
    });
    $('#searchProduct').focus(function () {
        $('#ajaxSearchInput').data('target', 'product');
        $('#ajaxSearchModal').modal('show');
    });
    $('#ajaxSearchModal').on('shown.bs.modal', function () {
        $('#ajaxSearchInput').focus();
    });
    $('#ajaxSearchInput').keyup(function () {
        ajaxCustomSearch($(this).val(), $(this).data('target'));
    });
    $('#ajaxSearchResult').on('click', '.item-add-button', function () {
        let target = $(this).data('target');
        switch (target) {
            case 'product':
                setProduct($(this));
                break;
            case 'customer':
                setCustomer($(this));
                break;
        }
    });

    /*Cart Items Events*/
    cartItemsContainer.on('focusout', '.cart-item', function () {
        onCartEdit($(this));
    });
    cartItemsContainer.on('click', '.cart-item-del', function () {
        onCartDelete($(this));
    });
});