/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as POS from './ShoppingCartTools.js';
import Checkout from './Checkout.js';
import ShoppingCart from "./ShoppingCart.js";

// Template variables
const EtaTemplate = Eta;
const cartTemplate = EtaTemplate.compile(document.getElementById('cartTemplateSource').innerHTML);
const customerTemplate = EtaTemplate.compile(document.getElementById('customerTemplateSource').innerHTML);
const productTemplate = EtaTemplate.compile(document.getElementById('productTemplateSource').innerHTML);
const templateConfig = EtaTemplate.config;

const barcodeInputBox = document.getElementById("productBarcodeInput");
const cartContainer = document.getElementById('cartContainer');
const customerSearchResult = document.getElementById('customerSearchResult');
const productSearchResult = document.getElementById('productSearchResult');
const salesForm = document.getElementById("salesDocumentForm");
//const stepper = new Stepper(document.querySelector('.bs-stepper'));

var Cart = new ShoppingCart();
var CartCheckout = new Checkout(0, CASH_PAYMENT_METHOD);

function deleteCartItem(e) {
    let index = e.getAttribute('data-index');

    Cart.delete(index);
    updateCart();
}

function editCartItem(e) {
    let field = e.getAttribute('data-field');
    let index = e.getAttribute('data-index');

    Cart.edit(index, field, e.value);
    updateCart();
}

function searchCustomer(query) {
    function updateSearchResult(response) {
        customerSearchResult.innerHTML = customerTemplate({items: response}, templateConfig);
    }

    POS.searchCustomer(updateSearchResult, query);
}

function searchProduct(query) {
    function updateSearchResult(response) {
        productSearchResult.innerHTML = productTemplate({items: response}, templateConfig);
    }

    POS.searchProduct(updateSearchResult, query);
}

function searchBarcode(query) {
    function setProductByBarcode(response) {
        if (undefined !== response && false !== response) {
            setProduct(response.code, response.description);
        }
        barcodeInputBox.value = '';
    }

    POS.searchBarcode(setProductByBarcode, query);
}

function setProduct(code, description) {
    Cart.add(code, description);
    updateCart();
}

function setCustomer(code, description) {
    document.getElementById('codcliente').value = code;
    document.getElementById('customerSearchBox').value = description;
    Cart.setCustomer(code);

    $('.modal').modal('hide');
}

function updateCart() {
    function updateCartData(data) {
        Cart = new ShoppingCart(data);
        updateCartView(data);
    }

    POS.recalculate(updateCartData, Cart.lines, salesForm);
}

function updateCartTotals() {
    salesForm.cartTotalDisplay.value = Cart.doc.total;
    salesForm.cartTaxesDisplay.value = Cart.doc.totaliva;
    salesForm.cartNetoDisplay.value = Cart.doc.netosindto;

    document.getElementById('cartNeto').value = Cart.doc.netosindto;
    document.getElementById('cartTaxes').value = Cart.doc.totaliva;
    document.getElementById('checkoutTotal').textContent = Cart.doc.total;
}

function updateCartView(data) {
    const elements = salesForm.elements;

    for(let i = 0; i < elements.length; i++) {
        const element = elements[i];
        const excludedElements = ['token', 'codcliente', 'customerSearchBox', 'tipoDocumento'];

        if (element.name && false === excludedElements.includes(element.name)) {
            const value = data.doc[element.name];
            switch (element.type) {
                case "checkbox" :
                    element.checked = value;
                    break;
                default :
                    element.value = value;
            }
        }
    }
    cartContainer.innerHTML = cartTemplate(data, templateConfig);
    updateCartTotals();
    $('.modal').modal('hide');
}

function recalculatePaymentAmount() {
    const checkoutButton = document.getElementById('checkoutButton');
    const paymentAmount = document.getElementById('paymentAmount');
    const paymentMethod = document.getElementById("paymentMethod");

    CartCheckout.recalculatePayment(paymentAmount.value, paymentMethod.value);

    if (CartCheckout.change >= 0) {
        paymentAmount.value = CartCheckout.payment;
        checkoutButton.removeAttribute('disabled');
    } else {
        checkoutButton.setAttribute('disabled', 'disabled');
    }

    document.getElementById('paymentReturn').textContent = POS.roundDecimals(CartCheckout.change);
    document.getElementById('paymentOnHand').textContent = POS.roundDecimals(CartCheckout.payment);
}

function onCheckoutConfirm() {
    let paymentData = {};
    paymentData.amount = document.getElementById('paymentAmount').value;
    paymentData.change = CartCheckout.change || 0;
    paymentData.method = document.getElementById("paymentMethod").value;

    document.getElementById("action").value = 'save-document';
    document.getElementById("lines").value = JSON.stringify(Cart.lines);
    document.getElementById("payments").value = JSON.stringify(paymentData);
    document.getElementById("codpago").value = paymentData.method;
    salesForm.submit();
}

function onCheckoutModalShow() {
    CartCheckout.total = Cart.doc.total;
}

function onDeletePausedOperation(code) {
    POS.deletePausedTransaction(code, salesForm);
}

function onPauseOperation() {
    if (false === POS.pauseDocument(Cart.lines, salesForm)) {
        $('#checkoutModal').modal('hide');
    }
}

function onResumePausedOperation(code) {
    function resumeDocument(response) {
        setCustomer(response.doc.codcliente, response.doc.nombrecliente);
        Cart = new ShoppingCart(response);
        updateCartView(response);
    }

    POS.resumeTransaction(resumeDocument, code);
}

$(document).ready(function () {
    onScan.attachTo(barcodeInputBox, {
        onScan: function(code) { searchBarcode(code); }
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
    $('#paymentAmount').keyup(function () {
        recalculatePaymentAmount();
    });
    $('#paymentMethod').change(function () {
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

    $('#pausedOperations').on('click', '.resume-button', function () {
        let code = $(this).data('code');

        onResumePausedOperation(code);
    });

    $('#pausedOperations').on('click', '.delete-button', function () {
        let code = $(this).data('code');

        onDeletePausedOperation(code);
    });
});

cartContainer.addEventListener('focusout', function(e) {
    if(e.target.classList.contains('cart-item')) {
        editCartItem(e.target);
    }
});

cartContainer.addEventListener('click', function(e) {
    if(e.target.classList.contains('cart-item-remove')) {
        deleteCartItem(e.target);
    }
}, true);

/*
document.querySelectorAll('.btn-next').forEach(item => {
    item.addEventListener('click', event => {
        stepper.next();
    });
});

document.querySelectorAll('.btn-previus').forEach(item => {
    item.addEventListener('click', event => {
        stepper.previous();
    });
});*/
