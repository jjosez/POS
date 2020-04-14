/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

var cartItemsList = [];
var shoppingCartElement = $('#cartItems');
var cartTemplateSource = $('#cart-item-template').html();
var cartTemplate = Sqrl.Compile(cartTemplateSource);

var ajaxTemplateSource = $('#ajax-search-template').html();
var ajaxTemplate = Sqrl.Compile(ajaxTemplateSource);

function addCartItem(e) {
    var cartItem = new CartItem({referencia : e.data('code'), descripcion : e.data('description')});
    cartItemsList.push(cartItem);

/*    if(typeof cartItemsList[e.data('code')] !== 'undefined') {
        cartItemsList[e.data('code')].cantidad +=1;
    } else {
        var cartItem = new CartItem({referencia : e.data('code'), descripcion : e.data('desc')});
        cartItemsList[cartItem.referencia] = cartItem;
    }*/

    onCartUpdate();
}

function formatNumber(val)
{
    return parseFloat(val).toFixed(2);
}


function getCartData() {
    var lines = []; var n = 0;

    for (var key in cartItemsList) {
        lines[n] = cartItemsList[key].newLineData();
        n++;
    }
    return lines;
}

function onCartEdit(e) {
    console.log(e.data('code'));
    console.log(e.val());
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
            testResponseTime(this.startTime);
            updateCartItemList(results.lines);
            $("#cartTotalDisplay").text(results.doc.total);
            $("#cartTaxesDisplay").text(results.doc.totaliva);
            $("#total").val(results.doc.total);
            $("#neto").val(results.doc.neto);
            $("#totalsuplidos").val(results.doc.totalsuplidos);
            $("#totaliva").val(results.doc.totaliva);
            $("#totalirpf").val(results.doc.totalirpf);
            $("#totalrecargo").val(results.doc.totalrecargo);

            console.log("results", results);
            testResponseTime(this.startTime);
        },
        error: function (xhr, status, error) {
            alert(xhr.responseText);
        }
    });
}

function recalculatePaymentAmount()
{
    total = parseFloat($("#total").val());
    paymentAmount = parseFloat($("#checkoutPaymentAmount").val());
    paymentMethod = $("#checkoutPaymentMethod").children("option:selected").val();

    paymentReturn = paymentAmount - total;
    paymentReturn = paymentReturn || 0;
    if (paymentMethod !== posCashPaymentMethod) {
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

function showCheckoutModal()
{
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

function ajaxCustomSearch(query ,target) {
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
            console.log(data);
            var html = ajaxTemplate({list:data, target:target}, Sqrl);
            $('#ajaxSearchResult').html(html);
            //testResponseTime(this.startTime);
        },
        error: function (xhr, status) {
            console.log('Error: ');
            console.log(xhr.responseText);
        }
    });
}

function setCustomer(element) {
    $('#codcliente').val(element.data('code'));
    $('#searchCustomer').val(element.data('description'));

    $('#ajaxSearchModal').modal('hide');
}

function testResponseTime(startTime) {
    //Calculate the difference in milliseconds.
    var time = performance.now() - startTime;
    //Convert milliseconds to seconds.
    var seconds = time / 1000;
    console.log("Execution time: " + seconds.toFixed(3));
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
    var html = cartTemplate(items, Sqrl);
    shoppingCartElement.html(html);
}

$(document).ready(function() {
    $('#cashupButton').click(function() {
        $('#cashupModal').modal('show');
    });
    $("#checkoutButton").click(function() {
        showCheckoutModal();
    });
    $("#checkoutPaymentAmount").keyup(function(e) {
        recalculatePaymentAmount();
    });
    $('#checkoutPaymentMethod').change(function(e) {
        recalculatePaymentAmount();
    });

    /*Cart Items Events*/
    $('#cartItems').on('focusout', '.cart-form-control', function () {
        console.log($(this));
        onCartEdit($(this));
    });

    /*Ajax Search Events*/
    $('#searchCustomer').focus(function () {
        $('#ajaxSearchInput').data('target', 'customer');
        $('#ajaxSearchModal').modal('show');
    });
    $('#searchProduct').focus(function () {
        //$('#searchProductModal').modal('show');
        $('#ajaxSearchInput').data('target', 'product');
        $('#ajaxSearchModal').modal('show');
    });
    $('#ajaxSearchModal').on('shown.bs.modal', function () {
        $('#ajaxSearchInput').focus();
    });
    $('#ajaxSearchInput').keyup(function () {
        ajaxCustomSearch($(this).val(), $(this).data('target'));
    });
    $('#ajaxSearchResult').on('click', '.item-add-button', function() {
        let target = $(this).data('target');
        switch (target) {
            case 'product':
                addCartItem($(this));
                break;
            case 'customer':
                setCustomer($(this));
                break;
        }
    });
});