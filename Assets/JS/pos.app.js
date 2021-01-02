/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

import * as POS from './POS/ShoppingCartTools.js';
import ShoppingCart from "./POS/ShoppingCart.js";

const MAIN_FORM_NAME = "salesDocumentForm";

// Template variables
const EtaTemplate = Eta;
const cartTemplate = EtaTemplate.compile(document.getElementById('cartTemplateSource').innerHTML);
const customerTemplate = EtaTemplate.compile(document.getElementById('customerTemplateSource').innerHTML);
const productTemplate = EtaTemplate.compile(document.getElementById('productTemplateSource').innerHTML);

const cartContainer = document.getElementById('cartContainer');
const productSearchResult = document.getElementById('productSearchResult');
const customerSearchResult = document.getElementById('customerSearchResult');
const barcodeInputBox = document.getElementById("productBarcodeInput");

const templateConfig = EtaTemplate.config;
var Cart = new ShoppingCart();

function onCartDelete(e) {
    let index = e.getAttribute('data-index');

    Cart.remove(index);
    updateCart();
}

function onCartEdit(e) {
    let field = e.getAttribute('data-field');
    let index = e.getAttribute('data-index');

    Cart.edit(index, field, e.value);
    updateCart();
}

function searchCustomer(query) {
    function updateSearchResult(response) {
        customerSearchResult.innerHTML = customerTemplate({items: response}, templateConfig);
    }

    POS.search(updateSearchResult, query, 'customer');
}

function searchProduct(query) {
    function updateSearchResult(response) {
        productSearchResult.innerHTML = productTemplate({items: response}, templateConfig);
    }

    POS.search(updateSearchResult, query, 'product');
}

function searchProductBarcode(query) {
    function searchBarcode(response) {
        if (response.length > 0) {
            setProduct(response[0].code, response[0].description);
        }
        barcodeInputBox.value = '';
    }

    POS.searchBarcode(searchBarcode(), query);
}

function setProduct(code, description) {
    Cart.add(code, description);

    updateCart();
}

function setCustomer(code, description) {
    document.getElementById('codcliente').value = code;
    document.getElementById('customerSearchBox').value = description;

    $('.modal').modal('hide');
}

function updateCart() {
    function updateCartData(data) {
        Cart = new ShoppingCart(data);
        updateCartView(data);
    }

    POS.recalculate(updateCartData, Cart.data.lines, MAIN_FORM_NAME);
}

function updateCartView(data) {
    var elements = document.getElementById(MAIN_FORM_NAME).elements;

    for(var i = 0; i < elements.length; i++) {
        var element = elements[i];
        if (element.name ) {
            element.value = data.doc[element.name];
        }
    }

    document.getElementById('cartTotalDisplay').value = data.doc.total;
    document.getElementById('cartTaxesDisplay').value = data.doc.totaliva;
    document.getElementById('cartNetoDisplay').value = data.doc.netosindto;

    // Update cart view
    cartContainer.innerHTML = cartTemplate(data, templateConfig);

    //hide all open modals
    $('.modal').modal('hide');
}

// Payment calc
function recalculatePaymentAmount() {
    let checkoutButton = document.getElementById('checkoutButton');
    let checkoutPaymentAmount = document.getElementById('checkoutPaymentAmount');
    let checkoutPaymentChange = document.getElementById('checkoutPaymentChange');
    let checkoutPaymentMethod = document.getElementById("checkoutPaymentMethod");
    let total = parseFloat(document.getElementById('total').value);

    let paymentAmount = checkoutPaymentAmount.value;
    let paymentReturn = paymentAmount - total;
    paymentReturn = paymentReturn || 0;
    if (checkoutPaymentMethod.value !== CASH_PAYMENT_METHOD) {
        if (paymentReturn > 0) {
            paymentReturn = 0;
            paymentAmount = total;
            checkoutPaymentAmount.value = POS.formatNumber(paymentAmount);
        }
    }
    checkoutPaymentChange.value = POS.formatNumber(paymentReturn);
    if (paymentReturn >= 0) {
        //console.log('Cambio : ' + paymentReturn);
        checkoutButton.removeAttribute('disabled');
    } else {
        //console.log('Falta : ' + paymentReturn);
        checkoutButton.setAttribute('disabled', 'disabled');
    }
}

function onCheckoutConfirm() {
    let paymentData = {};
    paymentData.amount = document.getElementById('checkoutPaymentAmount').value;
    paymentData.change = document.getElementById('checkoutPaymentChange').value;
    paymentData.method = document.getElementById("checkoutPaymentMethod").value;

    document.getElementById("action").value = 'save-document';
    document.getElementById("lines").value = JSON.stringify(Cart.data.lines);
    document.getElementById("payments").value = JSON.stringify(paymentData);
    document.getElementById("codpago").value = JSON.stringify(paymentData.method);
    document.salesDocumentForm.submit();
}

function onCheckoutModalShow() {
    let modalTitle = document.getElementById('dueAmount');
    modalTitle.textContent = document.getElementById('total').value;
}

function onPauseOperation() {
    if (Cart.data.lines.length <= 0) {
        $('#checkoutModal').modal('hide');
        return;
    }

    document.getElementById('action').value = 'pause-document';
    document.getElementById('lines').value = JSON.stringify(Cart.data.lines);
    document.salesDocumentForm.submit();
}

function resumePausedDocument(code) {
    function resumeDocument(response) {
        document.getElementById('idpausada').value = response.doc.idpausada;
        setCustomer(response.doc.codcliente, response.doc.nombrecliente);
        Cart = new ShoppingCart(response);
        updateCartView(response);
    }

    POS.resumeDocument(resumeDocument, code);
}

$(document).ready(function () {
    onScan.attachTo(barcodeInputBox, {
        onScan: function(code) { searchProductBarcode(code); }
    });

    $('[data-toggle="offcanvas"]').on('click', function () {
        $('.offcanvas-collapse').toggleClass('open');
    });
    $('#checkoutButton').click(function () {
        onCheckoutConfirm();
    });
    $('#pauseButton').click(function () {
        onPauseOperation();
    });
    $('#checkoutPaymentAmount').keyup(function () {
        recalculatePaymentAmount();
    });
    $('#checkoutPaymentMethod').change(function () {
        recalculatePaymentAmount();
    });
    $('#checkoutModal').on('shown.bs.modal', function () {
        onCheckoutModalShow();
    });
    $('#saveCashupButton').on('click', function () {
        document.cashupForm.submit();
    });

    // Ajax Search Events
    $('#customerSearchBox').focus(function () {
        $('#customerSearchModal').modal('show');
    });
    $('#customerSearchModal').on('shown.bs.modal', function () {
        $('#customerSerachInput').focus();
    });
    $('#customerSerachInput').keyup(function () {
        searchCustomer($(this).val());
    });
    $('#customerSearchResult').on('click', '.item-add-button', function () {
        let code = $(this).data('code');
        let description = $(this).data('description');

        setCustomer(code, description);
    });

    $('#productSearchBox').focus(function () {
        $('#productSearchModal').modal('show');
    });
    $('#productSearchModal').on('shown.bs.modal', function () {
        $('#productSerachInput').focus();
    });
    $('#productSerachInput').keyup(function () {
        searchProduct($(this).val());
    });
    $('#productSearchResult').on('click', '.item-add-button', function () {
        let code = $(this).data('code');
        let description = $(this).data('description');

        setProduct(code, description);
    });

    // Cart Items Events
    $('#pausedOperations').on('click', '.resume-button', function () {
        let code = $(this).data('code');

        resumePausedDocument(code);
    });
});

// Cart Items Events
cartContainer.addEventListener('click', ({target}) => {
    if(target.classList.contains('cart-item-remove')) {
        onCartDelete(target);
    }
});

cartContainer.addEventListener('focusout', ({target}) => {
    if(target.classList.contains('cart-item')) {
        onCartEdit(target);
    }
});