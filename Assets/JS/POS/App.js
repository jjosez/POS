/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Core from './AppCore.js';
import * as UI from './AppUI.js';
import Checkout from './Checkout.js';
import ShoppingCart from './ShoppingCart.js';

const cartTemplate = Eta.compile(Core.cartTemplateSource());
const customerTemplate = Eta.compile(Core.customerTemplateSource());
const productTemplate = Eta.compile(Core.productTemplateSource());

const cartContainer = Core.getElement('cartContainer');
const customerSearchResult = Core.getElement('customerSearchResult');
const ordersOnHold = Core.getElement('pausedOperations');
const productSearchResult = Core.getElement('productSearchResult');
const salesForm = Core.getElement("salesDocumentForm");

const Cart = new ShoppingCart();
var CartCheckout = new Checkout(0, CASH_PAYMENT_METHOD);

function deleteCartItem(e) {
    let index = e.getAttribute('data-index');

    Cart.deleteLine(index);
    updateCart();
}

function editCartItem(e) {
    let field = e.getAttribute('data-field');
    let index = e.getAttribute('data-index');

    Cart.editLine(index, field, e.value);
    updateCart();
}

function searchCustomer(query) {
    function updateSearchResult(response) {
        customerSearchResult.innerHTML = customerTemplate({items: response}, Eta.config);
    }

    Core.searchCustomer(updateSearchResult, query);
}

function searchProduct(query) {
    function updateSearchResult(response) {
        productSearchResult.innerHTML = productTemplate({items: response}, Eta.config);
    }

    Core.searchProduct(updateSearchResult, query);
}

function searchBarcode(query) {
    function setProductBarcode(response) {
        if (undefined !== response && false !== response) {
            setProduct(response.code, response.description);
        }
        barcodeInputBox.value = '';
    }

    Core.searchBarcode(setProductBarcode, query);
}

function setProduct(code, description) {
    Cart.addLine(code, description);
    updateCart();
}

function setCustomer(code, description) {
    document.getElementById('codcliente').value = code;
    UI.customerNameInput.value = description;
    Cart.setCustomer(code);

    $('.modal').modal('hide');
}

function updateCart() {
    function updateCartData(data) {
        Cart.update(data);
        updateCartView(data);
    }

    Core.recalculate(updateCartData, Cart.lines, salesForm);
}

function updateCartTotals() {
    salesForm.cartTotalDisplay.value = Cart.doc.total;
    salesForm.cartTaxesDisplay.value = Cart.doc.totaliva;
    salesForm.cartNetoDisplay.value = Cart.doc.netosindto;

    document.getElementById('cartNeto').value = Cart.doc.netosindto;
    document.getElementById('cartTaxes').value = Cart.doc.totaliva;
    document.getElementById('checkoutTotal').textContent = Cart.doc.total;

    CartCheckout.total = Cart.doc.total;
}

function updateCartView(data) {
    const elements = salesForm.elements;
    const excludedElements = ['token', 'tipo-documento'];

    for (const element of elements) {
        if (element.name && !excludedElements.includes(element.name)) {
            const value = data.doc[element.name];
            switch (element.type) {
                case 'checkbox':
                    element.checked = value;
                    break;
                default:
                    element.value = value;
                    break;
            }
        }
    }

    cartContainer.innerHTML = cartTemplate(data, Eta.config);
    updateCartTotals();
    $('.modal').modal('hide');
}

function recalculatePaymentAmount() {
    CartCheckout.recalculatePayment(UI.paymentAmountInput.value, UI.paymentMethodSelect.value);

    if (CartCheckout.change >= 0) {
        UI.paymentAmountInput.value = CartCheckout.payment;
        UI.saveOrderButton.removeAttribute('disabled');
    } else {
        UI.saveOrderButton.setAttribute('disabled', 'disabled');
    }

    UI.Checkout.textChange.textContent = Core.roundDecimals(CartCheckout.change);
    UI.Checkout.textReceived.textContent = Core.roundDecimals(CartCheckout.payment);
}

function onCheckoutConfirm() {
    let paymentData = {};
    paymentData.amount = UI.paymentAmountInput.value;
    paymentData.change = CartCheckout.change || 0;
    paymentData.method = UI.paymentMethodSelect.value;

    document.getElementById("action").value = 'save-order';
    document.getElementById("lines").value = JSON.stringify(Cart.lines);
    document.getElementById("payments").value = JSON.stringify(paymentData);
    document.getElementById("codpago").value = paymentData.method;
    salesForm.submit();
}

function onDeletePausedOperation(target) {
    const code = target.getAttribute('data-code');
    Core.deleteOrderOnHold(code, salesForm);
}

function onHoldOrder() {
    if (false === Core.holdOrder(Cart.lines, salesForm)) {
        $('#checkoutModal').modal('hide');
    }
}

function onResumePausedOperation(target) {
    const code = target.getAttribute('data-code');

    function resumeOrder(response) {
        setCustomer(response.doc.codcliente, response.doc.nombrecliente);
        Cart.update(response);
        updateCartView(response);

        console.log('Cart Resume', Cart);
    }

    Core.resumeOrder(resumeOrder, code);
}

function onSaveNewCustomer() {
    let taxID = Core.getElement('new-customer-taxid').value;
    let name = Core.getElement('new-customer-name').value;

    function saveCustomer(response) {
        const customer = response[0];

        if (customer.code) {
            setCustomer(customer.code, customer.description);
            $("#new-customer-form").collapse('toggle');
        }
    }

    Core.saveNewCustomer(saveCustomer, taxID, name);
}

function validEventTarget(target, elementClass) {
    return target && target.classList.contains(elementClass);
}

$(document).ready(function () {
    onScan.attachTo(UI.barcodeInput, {
        onScan: function (code) {
            searchBarcode(code);
        },
        onKeyDetect: function (iKeyCode) {
            if (13 === iKeyCode) {
                searchBarcode(UI.barcodeInput.value);
            }
        }
    });
    // Ajax Search Events
    $('#customerSearchBox').focus(function () {
        $('#customerSearchModal').modal('show');
    });
    $('#customerSearchModal').on('shown.bs.modal', function () {
        $('#customerSerachInput').focus();
    });
    $('#productSearchBox').focus(function () {
        $('#productSearchModal').modal('show');
    });
    $('#productSearchModal').on('shown.bs.modal', function () {
        $('#productSerachInput').focus();
    });
});

UI.paymentAmountInput.addEventListener('keyup', function () {
    return recalculatePaymentAmount();
});
UI.paymentMethodSelect.addEventListener('change', function () {
    return recalculatePaymentAmount();
});
UI.saveOrderButton.addEventListener('click', function () {
    return onCheckoutConfirm();
});
UI.holdOrderButton.addEventListener('click', function () {
    return onHoldOrder();
});
UI.searchCustomerInput.addEventListener('keyup', function () {
    return searchCustomer(this.value);
});
UI.searchProductInput.addEventListener('keyup', function () {
    return searchProduct(this.value);
});
UI.saveCustomerButton.addEventListener('click', function () {
    return onSaveNewCustomer();
});
UI.saveCashupButton.addEventListener('click', function () {
    return document.cashupForm.submit();
});
ordersOnHold.addEventListener('click', function (e) {
    if (validEventTarget(e.target,'delete-button')) {
        onDeletePausedOperation(e.target);
    }
});
ordersOnHold.addEventListener('click', function (e) {
    if (validEventTarget(e.target,'resume-button')) {
        onResumePausedOperation(e.target);
    }
});
cartContainer.addEventListener('focusout', function (e) {
    if (validEventTarget(e.target,'cart-item')) {
        editCartItem(e.target);
    }
});
cartContainer.addEventListener('click', function (e) {
    if (validEventTarget(e.target,'cart-item-remove')) {
        deleteCartItem(e.target);
    }
});
customerSearchResult.addEventListener('click', function (e) {
    if (validEventTarget(e.target,'item-add-button')) {
        setCustomer(e.target.dataset.code, e.target.dataset.description);
    }
});
productSearchResult.addEventListener('click', function (e) {
    if (validEventTarget(e.target,'item-add-button')) {
        setProduct(e.target.dataset.code, e.target.dataset.description);
    }
});
document.addEventListener("onSelectCustomer", function (e) {
    setCustomer(e.detail.code, e.detail.description);
});


