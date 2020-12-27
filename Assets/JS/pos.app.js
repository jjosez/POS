/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import { Cart } from './POS/Cart.js';
import * as Tools from './POS/tools.js';
import { ShoppingCart } from "./POS/ShoppingCart.js";

const FormName = "salesDocumentForm";
const barcodeInputBox = document.getElementById("productBarcodeInput");

const cartTemplate = Eta.compile(document.getElementById('cartTemplateSource').innerHTML);
const customerTemplate = Eta.compile(document.getElementById('customerTemplateSource').innerHTML);
const productTemplate = Eta.compile(document.getElementById('productTemplateSource').innerHTML);

const cartContainer = document.getElementById('cartContainer');
const productSearchResult = document.getElementById('productSearchResult');
const customerSearchResult = document.getElementById('customerSearchResult');

const etaConfig = Eta.config;
let cart = new Cart();
let shoppingCart = new ShoppingCart();

function onCartDelete(e) {
    let index = e.getAttribute('data-index');

    cart.deleteCartItem(index);
    shoppingCart.removeItem(index);
    updateCart();
}

function onCartEdit(e) {
    let field = e.getAttribute('data-field');
    let index = e.getAttribute('data-index');

    cart.editCartItem(index, field, e.value);
    updateCart();
}

function searchCustomer(query) {
    function updateSearchResult(response) {
        customerSearchResult.innerHTML = customerTemplate({items: response}, etaConfig);
    }

    Tools.search(updateSearchResult, query, 'customer');
}

function searchProduct(query) {
    function updateSearchResult(response) {
        productSearchResult.innerHTML = productTemplate({items: response}, etaConfig);
    }

    Tools.search(updateSearchResult, query, 'product');
}

function searchProductBarcode(query) {
    function searchBarcode(response) {
        if (response.length > 0) {
            setProduct(response[0].code, response[0].description);
        }
        barcodeInputBox.value = '';
    }

    Tools.searchBarcode(searchBarcode(), query);
}

function setProduct(code, description) {
    cart.addCartItem(code, description);
    shoppingCart.addItem(code, description);
    console.log(shoppingCart);
    updateCart();
}

function setCustomer(code, description) {
    document.getElementById('codcliente').value = code;
    document.getElementById('customerSearchBox').value = description;

    updateCart();
}

function updateCart() {
    function updateCartData(response) {
        cart = new Cart(response);
        shoppingCart = new ShoppingCart(response);
        updateCartView(response);
    }

    Tools.recalculateCartData(updateCartData, cart.getCartItems(), FormName);
}

function updateCartView(results) {
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
    console.info("ShoppingCart Data", shoppingCart);
    console.info("Cart Data", cart);

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
    function loadPausedOperation(response) {
        document.getElementById('idpausada').value = response.doc.idpausada;
        setCustomer(response.doc.codcliente, response.doc.nombrecliente);
        cart = new Cart(response);
        updateCartView(response);
    }

    Tools.loadOperation(loadPausedOperation, code);
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