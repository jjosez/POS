/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
var cart = new Cart({doc:{}});
var cartItemsContainer = $('#cartItemsContainer');
var cartTemplateSource = $('#cart-item-template').html();
var cartTemplate = Sqrl.Compile(cartTemplateSource);
var ajaxTemplateSource = $('#ajax-search-template').html();
var ajaxTemplate = Sqrl.Compile(ajaxTemplateSource);

function onCartUpdate() {
    var data = {};
    $.each($("#" + FormName).serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data.action = "recalculate-document";
    data.lines = cart.getCartItems();
    $.ajax({
        type: "POST",
        url: UrlAccess,
        dataType: "json",
        data: data,
        startTime: performance.now(),
        success: function (results) {
            cart = new Cart(results);
            console.log(cart.cartItems);
            updateCartView(results);
            testResponseTime(this.startTime, 'Request exec time:');
        },
        error: function (xhr, status, error) {
            //  console.log('Error:', xhr.responseText)
        }
    });
}

function onCartDelete(e) {
    let index = e.data('index');

    cart.deleteCartItem(index);
    onCartUpdate();
}

function onCartEdit(e) {
    let field = e.data('field');
    let index = e.data('index');

    cart.editCartItem(index, field, e.val());
    onCartUpdate();
}

function updateCartView(results) {
    // Hide search modal
    $('#ajaxSearchModal').modal('hide');

    // Update totals
    $('#cartTotalDisplay').val(cart.total);
    $('#cartTaxesDisplay').val(cart.totaliva);
    $('#cartNetoDisplay').val(cart.netosindto);
    $('#total').val(cart.total);
    $('#neto').val(cart.neto);
    $('#totaliva').val(cart.totaliva);
    $('#totalirpf').val(cart.totalirpf);
    $('#totalrecargo').val(cart.totalrecargo);

    // Update cart view
    var html = cartTemplate({lines: results.lines}, Sqrl);
    cartItemsContainer.html(html);
}

// Search actions
function ajaxCustomSearch(query, target) {
    var data = {
        action: "custom-search",
        query: query,
        target: target
    };
    $.ajax({
        url: UrlAccess,
        data: data,
        type: "POST",
        dataType: "json",
        startTime: performance.now(),
        success: function (data) {
            let html = ajaxTemplate({list: data, target: target}, Sqrl);
            $('#ajaxSearchResult').html(html);
        },
        error: function (xhr, status) {
            //console.log('Error', xhr.responseText);
        }
    });
}

function ajaxBarcodeSearch(query) {
    var data = {
        action: "barcode-search",
        query: query
    };
    $.ajax({
        url: UrlAccess,
        data: data,
        type: "POST",
        dataType: "json",
        startTime: performance.now(),
        success: function (data) {
            if (data.length > 0) {
                setProduct(data[0].code, data[0].description);
            } else {
                console.log('No encontrado');
            }
            $('#searchByCode').val('');
        },
        error: function (xhr, status) {
            //console.log('Error:', xhr.responseText);
        }
    });
}

function setProduct(code, description) {
    cart.addCartItem(code, description);

    onCartUpdate();
}

function setCustomer(code, description) {
    $('#codcliente').val(code);
    $('#searchCustomer').val(description);
    $('#ajaxSearchModal').modal('hide');
    $('#ajaxSearchResult').html('');
}

// Payment calc
function recalculatePaymentAmount() {
    total = parseFloat($('#total').val());
    paymentAmountInput = $('#checkoutPaymentAmount');
    paymentAmount = paymentAmountInput.val();
    paymentMethod = $('#checkoutPaymentMethod').children('option:selected').val();
    paymentReturn = paymentAmount - total;
    paymentReturn = paymentReturn || 0;
    if (paymentMethod !== CashPaymentMethod) {
        if (paymentReturn > 0) {
            paymentReturn = 0;
            paymentAmount = total;
            paymentAmountInput.val(formatNumber(paymentAmount));
        }
    }
    $('#checkoutPaymentChange').val(formatNumber(paymentReturn));
    if (paymentReturn >= 0) {
        //console.log('Cambio : ' + paymentReturn);
        $('#checkoutButton').prop('disabled', false);
    } else {
        //console.log('Falta : ' + paymentReturn);
        $('#checkoutButton').prop('disabled', true);
    }
}

function onCheckoutConfirm() {
    var paymentData = {};
    paymentData.amount = $('#checkoutPaymentAmount').val();
    paymentData.method = $('#checkoutPaymentMethod').val();
    paymentData.change = $('#checkoutPaymentChange').val();
    $('#action').val('save-document');
    $('#lines').val(JSON.stringify(cart.getCartItems()));
    $('#payments').val(JSON.stringify(paymentData));
    document.salesDocumentForm.submit();
}

function onCheckoutModalShow() {
    var total = $('#total').val();
    var modal = $('#checkoutModal');

    modal.find('.modal-title').text(total);
}

function onPauseOperation() {
    var cartItems = cart.getCartItems();
    if (cartItems.length <= 0) {
        $('#checkoutModal').modal('hide');
        return;
    }

    $('#action').val('pause-document');
    $('#lines').val(JSON.stringify(cartItems));
    document.salesDocumentForm.submit();
}

// Helper functions
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

$(document).ready(function () {
    var barcodeInput = document.getElementById("searchByCode");
    onScan.attachTo(barcodeInput, {
        onScan: function(code) { ajaxBarcodeSearch(code) }
    });

    $('[data-toggle="offcanvas"]').on('click', function () {
        $('.offcanvas-collapse').toggleClass('open')
    });
    $('#checkoutButton').click(function () {
        onCheckoutConfirm();
    });
    $('#pauseButton').click(function () {
        onPauseOperation();
    });
    $('#checkoutPaymentAmount').keyup(function (e) {
        recalculatePaymentAmount();
    });
    $('#checkoutPaymentMethod').change(function (e) {
        recalculatePaymentAmount();
    });
    $('#checkoutModal').on('shown.bs.modal', function () {
        onCheckoutModalShow();
    });
    $('#saveCashupButton').on('click', function (event) {
        document.cashupForm.submit()
    });

    // Ajax Search Events
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
        let code = $(this).data('code');
        let description = $(this).data('description');
        switch (target) {
            case 'product':
                setProduct(code, description);
                break;
            case 'customer':
                setCustomer(code, description);
                break;
        }
    });

    // Cart Items Events
    cartItemsContainer.on('focusout', '.cart-item', function () {
        onCartEdit($(this));
    });
    cartItemsContainer.on('click', '.cart-item-del', function () {
        onCartDelete($(this));
    });
});