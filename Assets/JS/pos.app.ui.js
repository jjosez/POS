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

function redrawCartTable(items) {
    var html = cartTemplate(items, Sqrl);
    shoppingCartElement.html(html);
}

function searchCustomer(query) {
    var data = {
        action: "search-customer",
        query: query,
    };

    $.ajax({
        url: posUrlAccess,
        data: data,
        type: "POST",
        dataType: "json",
        startTime: performance.now(),
        success: function (data) {
            //console.log(data);
            var html = ajaxTemplate({list:data}, Sqrl);
            $('#searchCustomerResult').html(html);
            //testResponseTime(this.startTime);
        },
        error: function (xhr, status) {
            console.log('Error: ');
            console.log(xhr.responseText);
        }
    });
}

function searchProduct(query) {
    var data = {
        action: "search-product",
        query: query
    };
    $.ajax({
        url: posUrlAccess,
        data: data,
        type: "POST",
        dataType: "json",
        startTime: performance.now(),
        success: function(data) {
            //console.log(data);
            var html = ajaxTemplate({list:data}, Sqrl);
            $('#searchProductResult').html(html);
            //testResponseTime(this.startTime);
        },
        error: function(xhr, status) {
            console.log('Error: ');
            console.log(xhr.responseText);
        }
    });
}

function setCustomer(element) {
    $('#codcliente').val(element.data('code'));
    $('#searchCustomer').val(element.data('description'));

    $('#searchCustomerModal').modal('hide');
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

    $('#searchProductModal').modal('hide');
    redrawCartTable({lines: items});
}

$(document).ready(function() {
    $('#cashupButton').click(function() {
        $('#cashupModal').modal('show');
    });
    $("#checkoutButton").click(function() {
        showCheckoutModal();
    });
    $("#checkoutPaymentAmount").keyup(function(e) {
        processPaymentAmount();
    });
    $('#checkoutPaymentMethod').change(function(e) {
        processPaymentAmount();
    });

    /*Customer Search Events*/
    $('#searchCustomer').focus(function () {
        $('#searchCustomerModal').modal('show');
    });
    $('#searchCustomerModal').on('shown.bs.modal', function () {
        $('#searchCustomerInput').focus();
    });
    $('#searchCustomerInput').keyup(function () {
        searchCustomer($(this).val());
    });
    $('#searchCustomerResult').on('click', '.item-add-button', function() {
        setCustomer($(this));
    });

    /*Product Search Events*/
    $('#searchProduct').focus(function () {
        $('#searchProductModal').modal('show');
    });
    $('#searchProductModal').on('shown.bs.modal', function () {
        $('#searchProductInput').focus();
    });
    $('#searchProductInput').keyup(function () {
        searchProduct($(this).val());
    });
    $('#searchProductResult').on('click', '.item-add-button', function() {
        addCartItem($(this));
    });
});