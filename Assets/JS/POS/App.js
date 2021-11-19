/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Core from './AppCore.js';
import * as UI from './AppUI.js';
import Checkout from './Checkout.js';
import Cart from './Cart.js';

/* global Eta*/
const cartTemplate = Eta.compile(Core.cartTemplateSource());
const customerTemplate = Eta.compile(Core.customerTemplateSource());
const productTemplate = Eta.compile(Core.productTemplateSource());

const ordersOnHold = Core.getElement('pausedOperations');

const OrderCart = new Cart();
const OrderCheckout = new Checkout(Core.settings.cash);

function deleteCartItem(e) {
    let index = e.getAttribute('data-index');

    OrderCart.deleteLine(index);
    updateCart();
}

function editCartItem(e) {
    let field = e.getAttribute('data-field');
    let index = e.getAttribute('data-index');

    OrderCart.editLine(index, field, e.value);
    updateCart();
}

function searchCustomer(query) {
    function updateSearchResult(response) {
        UI.customerSearchResult.innerHTML = customerTemplate({items: response}, Eta.config);
    }

    Core.searchCustomer(query).then(updateSearchResult);
}

function searchProduct(query) {
    function updateSearchResult(response) {
        UI.productSearchResult.innerHTML = productTemplate({items: response}, Eta.config);
    }

    Core.searchProduct(query).then(updateSearchResult);
}

function searchBarcode(query) {
    function setProductBarcode(response) {
        if (undefined !== response && false !== response) {
            setProduct(response.code, response.description);
        }
        UI.productBarcodeBox.value = '';
    }

    Core.searchBarcode(query).then(setProductBarcode);
}

function setProduct(code, description) {
    OrderCart.addLine(code, description);
    updateCart();
}

function setCustomer(code, description) {
    document.getElementById('codcliente').value = code;
    UI.customerSearch.value = description;
    OrderCart.setCustomer(code);

    $('.modal').modal('hide');
}

function updateCart() {
    function updateCartData(response) {
        OrderCart.update(response);
        updateCartView(response);
    }

    Core.recalculate(OrderCart, UI.mainForm).then(updateCartData);
}

function updateCartTotals() {
    UI.mainForm.cartTotalDisplay.value = OrderCart.doc.total;
    UI.mainForm.cartTaxesDisplay.value = OrderCart.doc.totaliva;
    UI.mainForm.cartNetoDisplay.value = OrderCart.doc.netosindto;

    document.getElementById('cartNeto').value = OrderCart.doc.netosindto;
    document.getElementById('cartTaxes').value = OrderCart.doc.totaliva;
    document.getElementById('checkoutTotal').textContent = OrderCart.doc.total;

    OrderCheckout.total = OrderCart.doc.total;
}

function updateCartView(data) {
    const elements = UI.mainForm.elements;
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

    UI.cartProductsList.innerHTML = cartTemplate(data, Eta.config);
    updateCartTotals();
    $('.modal').modal('hide');
}

function recalculatePaymentAmount() {
    OrderCheckout.recalculatePayment(UI.paymentAmountInput.value, UI.paymentMethodSelect.value);

    if (OrderCheckout.change >= 0) {
        UI.paymentAmountInput.value = OrderCheckout.payment;
        UI.orderSaveButton.removeAttribute('disabled');
    } else {
        UI.orderSaveButton.setAttribute('disabled', 'disabled');
    }

    UI.checkoutChangeDisplay.textContent = Core.roundDecimals(OrderCheckout.change);
    UI.checkoutReceivedDisplay.textContent = Core.roundDecimals(OrderCheckout.payment);
}

function onCheckoutConfirm() {
    let payments = {
        amount: UI.paymentAmountInput.value,
        change: OrderCheckout.change || 0,
        method: UI.paymentMethodSelect.value
    };

    Core.saveOrder(OrderCart, payments, UI.mainForm);
}

function deleteOrderOnHold(target) {
    const code = target.getAttribute('data-code');

    function deleteOrder() {
        location.href = 'POS';
    }

    Core.deleteOrderRequest(code).then(deleteOrder);
}

function onHoldOrder() {
    if (false === Core.holdOrder(OrderCart.lines, UI.mainForm)) {
        $('#checkoutModal').modal('hide');
    }
}

function resumeOrderOnHold(target) {
    const code = target.getAttribute('data-code');

    function resumeOrder(response) {
        setCustomer(response.doc.codcliente, response.doc.nombrecliente);
        OrderCart.update(response);
        updateCartView(response);
    }

    Core.resumeOrder(code).then(resumeOrder);
}

function onSaveNewCustomer() {
    const taxID = Core.getElement('newCustomerTaxID').value;
    const name = Core.getElement('newCustomerName').value;

    function saveCustomer(response) {
        if (response.codcliente) {
            setCustomer(response.codcliente, response.razonsocial);
            $("#newCustomerForm").collapse('toggle');
        }
    }

    Core.saveNewCustomer(taxID, name).then(saveCustomer);
}

function isEventTarget(target, elementClass) {
    return target && target.classList.contains(elementClass);
}

document.addEventListener( "DOMContentLoaded", () => {
    /* global onScan*/
    onScan.attachTo(UI.productBarcodeBox, {
        onScan: code => searchBarcode(code),
        onKeyDetect: iKeyCode => {
            if (13 === iKeyCode) {
                searchBarcode(UI.productBarcodeBox.value);
            }
        }
    });
    $('#customerSearchModal').on('shown.bs.modal', function () {
        $('#customerSearchBox').focus();
    });
    $('#productSearchModal').on('shown.bs.modal', function () {
        $('#productSearchBox').focus();
    });
});
document.addEventListener("onSelectCustomer", function (e) {
    setCustomer(e.detail.code, e.detail.description);
});
UI.productBarcodeBox.addEventListener('scan', function(code) {
    searchBarcode(code);
});
UI.paymentAmountInput.addEventListener('keyup', function () {
    return recalculatePaymentAmount();
});
UI.paymentMethodSelect.addEventListener('change', function () {
    return recalculatePaymentAmount();
});
UI.orderSaveButton.addEventListener('click', function () {
    return onCheckoutConfirm();
});
UI.orderHoldButton.addEventListener('click', function () {
    return onHoldOrder();
});
UI.productSearch.addEventListener('focus', function () {
    $('#productSearchModal').modal('show');
});
UI.productSearchBox.addEventListener('keyup', function () {
    return searchProduct(this.value);
});
UI.customerSearch.addEventListener('focus', function () {
    $('#customerSearchModal').modal('show');
});
UI.customerSearchBox.addEventListener('keyup', function () {
    return searchCustomer(this.value);
});
UI.customerSaveButton.addEventListener('click', function () {
    return onSaveNewCustomer();
});
UI.closeSessionButton.addEventListener('click', function () {
    return UI.closingForm.submit();
});
ordersOnHold.addEventListener('click', function (e) {
    if (isEventTarget(e.target, 'resume-button')) {
        return resumeOrderOnHold(e.target);
    }
    if (isEventTarget(e.target, 'delete-button')) {
        return deleteOrderOnHold(e.target);
    }
});
UI.cartProductsList.addEventListener('focusout', function (e) {
    if (isEventTarget(e.target, 'cart-item')) {
        return editCartItem(e.target);
    }
});
UI.cartProductsList.addEventListener('click', function (e) {
    if (isEventTarget(e.target, 'cart-item-remove')) {
        deleteCartItem(e.target);
    }
});
UI.customerSearchResult.addEventListener('click', function (e) {
    if (isEventTarget(e.target, 'item-add-button')) {
        setCustomer(e.target.dataset.code, e.target.dataset.description);
    }
});
UI.productSearchResult.addEventListener('click', function (e) {
    if (isEventTarget(e.target, 'item-add-button')) {
        setProduct(e.target.dataset.code, e.target.dataset.description);
    }
});


