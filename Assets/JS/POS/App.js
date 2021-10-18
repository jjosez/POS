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
const customerSearch = Core.getElement('customerSearchResult');
const ordersOnHold = Core.getElement('pausedOperations');
const productSearch = Core.getElement('productSearchResult');

const Cart = new ShoppingCart();
var CartCheckout = new Checkout(0, Core.settings.cash);

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
        customerSearch.innerHTML = customerTemplate({items: response}, Eta.config);
    }

    Core.searchCustomer(query).then(updateSearchResult);
}

function searchProduct(query) {
    function updateSearchResult(response) {
        productSearch.innerHTML = productTemplate({items: response}, Eta.config);
    }

    Core.searchProduct(query).then(updateSearchResult);
}

function searchBarcode(query) {
    function setProductBarcode(response) {
        if (undefined !== response && false !== response) {
            setProduct(response.code, response.description);
        }
        UI.barcodeInput.value = '';
    }

    Core.searchBarcode(query).then(setProductBarcode);
}

function setProduct(code, description) {
    Cart.addLine(code, description);
    console.log(Cart);
    updateCart();
}

function setCustomer(code, description) {
    document.getElementById('codcliente').value = code;
    UI.customerNameInput.value = description;
    Cart.setCustomer(code);

    $('.modal').modal('hide');
}

function updateCart() {
    function updateCartData(response) {
        Cart.update(response);
        updateCartView(response);
    }

    Core.recalculate(Cart, UI.mainForm).then(updateCartData);
}

function updateCartTotals() {
    UI.mainForm.cartTotalDisplay.value = Cart.doc.total;
    UI.mainForm.cartTaxesDisplay.value = Cart.doc.totaliva;
    UI.mainForm.cartNetoDisplay.value = Cart.doc.netosindto;

    document.getElementById('cartNeto').value = Cart.doc.netosindto;
    document.getElementById('cartTaxes').value = Cart.doc.totaliva;
    document.getElementById('checkoutTotal').textContent = Cart.doc.total;

    CartCheckout.total = Cart.doc.total;
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

    UI.checkoutChangeDisplay.textContent = Core.roundDecimals(CartCheckout.change);
    UI.checkoutReceivedDisplay.textContent = Core.roundDecimals(CartCheckout.payment);
}

function onCheckoutConfirm() {
    let payments = {
        amount: UI.paymentAmountInput.value,
        change: CartCheckout.change || 0,
        method: UI.paymentMethodSelect.value
    };

    Core.saveOrder(Cart, payments, UI.mainForm);

    //document.getElementById("action").value = 'save-order';
    //document.getElementById("lines").value = JSON.stringify(Cart.lines);
    //document.getElementById("payments").value = JSON.stringify(paymentData);
    //document.getElementById("codpago").value = paymentData.method;
    //UI.mainForm.submit();
}

function deleteOrderOnHold(target) {
    const code = target.getAttribute('data-code');

    function deleteOrder() {
        location.href='POS';
    }

    Core.deleteOrderRequest(code).then(deleteOrder);
}

function onHoldOrder() {
    if (false === Core.holdOrder(Cart.lines, UI.mainForm)) {
        $('#checkoutModal').modal('hide');
    }
}

function resumeOrderOnHold(target) {
    const code = target.getAttribute('data-code');

    function resumeOrder(response) {
        setCustomer(response.doc.codcliente, response.doc.nombrecliente);
        Cart.update(response);
        updateCartView(response);
    }

    Core.resumeOrder(code).then(resumeOrder);
}

function onSaveNewCustomer() {
    const taxID = Core.getElement('new-customer-taxid').value;
    const name = Core.getElement('new-customer-name').value;

    function saveCustomer(response) {
        if (response.codcliente) {
            setCustomer(response.codcliente, response.razonsocial);
            $("#new-customer-form").collapse('toggle');
        }
    }

    Core.saveNewCustomer(taxID, name).then(saveCustomer);
}

function isEventTarget(target, elementClass) {
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
    return UI.closingForm.submit();
});
ordersOnHold.addEventListener('click', function (e) {
    if (isEventTarget(e.target,'resume-button')) {
        return resumeOrderOnHold(e.target);
    }
    if (isEventTarget(e.target,'delete-button')) {
        return deleteOrderOnHold(e.target);
    }
});
cartContainer.addEventListener('focusout', function (e) {
    if (isEventTarget(e.target,'cart-item')) {
        return editCartItem(e.target);
    }
});
cartContainer.addEventListener('click', function (e) {
    if (isEventTarget(e.target,'cart-item-remove')) {
        deleteCartItem(e.target);
    }
});
customerSearch.addEventListener('click', function (e) {
    if (isEventTarget(e.target,'item-add-button')) {
        setCustomer(e.target.dataset.code, e.target.dataset.description);
    }
});
productSearch.addEventListener('click', function (e) {
    if (isEventTarget(e.target,'item-add-button')) {
        setProduct(e.target.dataset.code, e.target.dataset.description);
    }
});
document.addEventListener("onSelectCustomer", function (e) {
    setCustomer(e.detail.code, e.detail.description);
});


