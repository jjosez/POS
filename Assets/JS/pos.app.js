/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import { Cart } from './POS/Cart.js';
import * as Tools from './POS/tools.js';

const FormName = "salesDocumentForm";
const UrlAccess = "POS";

const cartTemplate = Eta.compile(document.getElementById('cartTemplateSource').innerHTML);
const customerTemplate = Eta.compile(document.getElementById('customerTemplateSource').innerHTML);
const productTemplate = Eta.compile(document.getElementById('productTemplateSource').innerHTML);

const cartContainer = document.getElementById('cartContainer');
const productSearchResult = document.getElementById('productSearchResult');
const customerSearchResult = document.getElementById('customerSearchResult');

const etaConfig = Eta.config;
let cart = new Cart();

function onCartDelete(e) {
    let index = e.getAttribute('data-index');

    cart.deleteCartItem(index);
    updateCart();
}

function onCartEdit(e) {
    let field = e.getAttribute('data-field');
    let index = e.getAttribute('data-index');

    cart.editCartItem(index, field, e.value);
    updateCart();
}

function updateCart() {
    function recalculateLines(result) {
        cart = new Cart(result);
        updateCartView(result);
    }

    Tools.recalculateCartLines(recalculateLines, UrlAccess, cart.getCartItems(), FormName);
}

function updateCartView(results) {
    // Hide search modal
    $('#ajaxSearchModal').modal('hide');

    // Update totals
    document.getElementById('cartTotalDisplay').value = cart.total;
    document.getElementById('cartTaxesDisplay').value = cart.totaliva;
    document.getElementById('cartNetoDisplay').value = cart.netosindto;
    document.getElementById('total').value = cart.total;
    document.getElementById('neto').value = cart.neto;
    document.getElementById('totaliva').value = cart.totaliva;
    document.getElementById('totalirpf').value = cart.totalirpf;
    document.getElementById('totalrecargo').value = cart.totalrecargo;

    // Update cart view
    cartContainer.innerHTML = cartTemplate(results, etaConfig);
}

function searchCustomer(query) {
    function drawTemplate(result) {
        customerSearchResult.innerHTML = customerTemplate({items: result}, etaConfig);
    }

    Tools.search(drawTemplate, UrlAccess, query, 'customer');
}

function searchProduct(query) {
    function drawTemplate(result) {
        productSearchResult.innerHTML = productTemplate({items: result}, etaConfig);
    }

    Tools.search(drawTemplate, UrlAccess, query, 'product');
}

function searchProductBarcode(query) {
    function searchBarcode(result) {
        if (result.length > 0) {
            setProduct(result[0].code, result[0].description);
        }
        document.getElementById('productBarcodeInput').value = '';
    }

    Tools.searchBarcode(searchBarcode(), UrlAccess, query);
}

function setProduct(code, description) {
    cart.addCartItem(code, description);

    updateCart();

    $('#productSearchModal').modal('hide');
}

function setCustomer(code, description) {
    document.getElementById('codcliente').value = code;
    document.getElementById('customerSearchBox').value = description;

    $('#customerSearchModal').modal('hide');
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
    if (checkoutPaymentMethod.value !== CashPaymentMethod) {
        if (paymentReturn > 0) {
            paymentReturn = 0;
            paymentAmount = total;
            checkoutPaymentAmount.value = Tools.formatNumber(paymentAmount);
        }
    }
    checkoutPaymentChange.value = Tools.formatNumber(paymentReturn);
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
    document.getElementById("lines").value = JSON.stringify(cart.getCartItems());
    document.getElementById("payments").value = JSON.stringify(paymentData);
    document.getElementById("codpago").value = JSON.stringify(paymentData.method);
    document.salesDocumentForm.submit();
}

function onCheckoutModalShow() {
    let modalTitle = document.getElementById('dueAmount');
    modalTitle.textContent = document.getElementById('total').value;
}

function onPauseOperation() {
    if (cart.getCartItems().length <= 0) {
        $('#checkoutModal').modal('hide');
        return;
    }

    document.getElementById('action').value = 'pause-document';
    document.getElementById('lines').value = JSON.stringify(cart.getCartItems());
    document.salesDocumentForm.submit();
}

function resumeOperation(code) {
    function loadPausedOperation(result) {
        cart = new Cart(result);
        document.getElementById('idpausada').value = result.doc.idpausada;

        setCustomer(result.doc.codcliente, result.doc.nombrecliente);
        updateCartView(result);
        $('#pausedOpsModal').modal('hide');
    }

    Tools.loadOperation(loadPausedOperation, UrlAccess, code);
}

$(document).ready(function () {
    let barcodeInput = document.getElementById("productBarcodeInput");
    onScan.attachTo(barcodeInput, {
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

        resumeOperation(code);
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