/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Core from './Core.js';
import {mainView, cartView, checkoutView} from "./UI.js";
import * as Order from "./Order.js";
import CartModel from "./Cart.js";
import CheckoutModel from "./Checkout.js";

export const Cart = new CartModel({
    'doc': {
        'codcliente': settings.customer,
        'idpausada': 'false',
        'tipo-documento': settings.document
    },
    'token': settings.token
});

export const Checkout = new CheckoutModel(settings.cash);

//window.App = {};

function saveOrder() {
    if (Cart.lines.length < 1) {
        return;
    }

    Order.saveRequest(Cart, Checkout.payments).then(response => {
        Cart.updateClean(response);
        Checkout.clear();

        Core.searchProduct('').then(response => {
            mainView().updateProductListView(response);
        });
    });
}

function holdOrder() {
    if (Cart.lines.length < 1) {
        return;
    }

    Order.holdRequest(Cart).then(response => {
        Cart.updateClean(response);
        //Cart.token = response.token;

        Order.getOnHoldRequest().then(response => {
            mainView().updateHoldOrdersList(response);
        });
    });
}

/**
 * @param {{code:string}} data
 */
function resumeOrderHandler(data) {
    Order.resumeRequest(data.code).then(response => {
        Cart.update(response);
        Cart.token = response.token;

        mainView().toggleHoldOrdersModal();
    });
}

function searchCustomerHandler() {
    Core.searchCustomer(this.value).then(response => {
        mainView().updateCustomerListView(response);
    });
}

function searchProductHandler() {
    let query = this.value || '';
    Core.searchProduct(query).then(response => {
        mainView().updateProductListView(response);
    });
}

/**
 * @param {{code:string, description:string}} data
 */
function setCustomerHandler(data) {
    console.log('Set customer', data.description)
    if (typeof data.code === 'undefined' || data.code === null) {
        return;
    }
    Cart.setCustomer(data.code);
    mainView().updateCustomer(data.description);
}

/**
 * @param {{code:string, description:string}} data
 */
function setDocumentHandler(data) {
    console.log('Set document', data.description)
    if (typeof data.code === 'undefined' || data.code === null) {
        return;
    }
    Cart.setDocumentType(data.code);
    mainView().updateDocument(data.description);
}

/**
 * @param {Event} event
 */
function setProductHandler(event) {
    console.log('Add product', event.target.dataset)
    const data = event.target.dataset;
    if (typeof data.code === 'undefined' || data.code === null) {
        return;
    }
    Cart.addProduct(data.code, data.description);
}

/**
 * @param {{code:string}} data
 */
function deleteOrderHandler(data) {
    Order.deleteHoldRequest(data.code).then(() => {
        Order.getOnHoldRequest().then(response => {
            mainView().updateHoldOrdersList(response);
            mainView().toggleHoldOrdersModal();
        });
    });
}

/**
 * @param {{index:int}} data
 */
function deletePaymentHandler(data) {
    console.log('Delete payment action', data.index)
    Checkout.deletePayment(data.index);
}

/**
 * @param {{index:int}} data
 */
function deleteProductHandler(data) {
    console.log('Delete product action', data.index)
    Cart.deleteProduct(data.index);
}

/**
 * @param {{index:int}} data
 */
function editProductHandler(data) {
    console.log('Edit product', data)
    updateEditView(data.index);

    if (true === cartView().editView.classList.contains('hidden')) {
        cartView().toggleEditView();
        mainView().toggleMainView();
    }
}

/**
 * @param {EventTarget} target
 */
function editProductFieldHandler(target) {
    console.log('Edit field handler', target.dataset)
    const index = target.dataset.index;
    Cart.editProduct(index, target.dataset.field, target.value);

    updateCart().then(() => {
        updateEditView(index);
    });
}

function updateCart() {
    return Order.recalculateRequest(Cart).then(response => {
        Cart.update(response);
    });
}

/**
 * @param {{detail}} data
 */
function updateCartView(data) {
    cartView().updateListView(data.detail);
    cartView().updateTotals(data.detail);
    Checkout.total = data.detail.doc.total;

    if (data.detail.doc.total === 0) {
        Checkout.clear();
    }
}

/**
 * @param index
 */
function updateEditView(index) {
    let data = Cart.getProduct(index);
    cartView().updateEditForm(data);
}

/*function documentEventHandler(event) {
    const data = event.target.dataset;
    const functionName = data.action;

    if (typeof App[functionName] === "function") {
        console.log('Ejecutando funcion:', functionName);
        App[functionName](data);
    }
}

App.setDocumentAction = function (data) {
    if (typeof data.code === 'undefined' || data.code === null) {
        return;
    }
    Cart.setDocumentType(data.code);
    mainView().updateDocument(data.description);
}*/

/**
 * @param {Event} event
 */
function commonEventHandler(event) {
    const data = event.target.dataset;
    const action = data.action;

    switch (action) {
        case 'setDocumentAction':
            setDocumentHandler(data);
            return;
        case 'setCustomerAction':
            setCustomerHandler(data);
            return;
        case 'deleteOrderAction':
            deleteOrderHandler(data);
            return;
        case 'deletePaymentAction':
            deletePaymentHandler(data);
            return;
        case 'deleteProductAction':
            deleteProductHandler(data);
            return;
        case 'editProductAction':
            editProductHandler(data);
            return;
        case 'editProductFieldAction':
            editProductFieldHandler(event.target);
            return;
        case 'resumeOrderAction':
            resumeOrderHandler(data);
            return;
    }
}

function setPayment() {
    Checkout.setPayment(checkoutView().paymentInput.value, checkoutView().paymentInput.dataset);
    checkoutView().paymentInput.value = 0;
}

function recalculatePaymentAmount() {
    if (this.dataset.value === 'balance') {
        checkoutView().paymentInput.value = Checkout.getOutstandingBalance();
    } else {
        let value = parseFloat(checkoutView().paymentInput.value) || 0;
        value += parseFloat(this.dataset.value) || 0;
        checkoutView().paymentInput.value = value;
    }
}

function showPaymentModal() {
    checkoutView().showPaymentModal(this);
}

function updateCheckoutView() {
    if (Checkout.change >= 0) {
        checkoutView().confirmButton.removeAttribute('disabled');
        checkoutView().confirmButton.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        checkoutView().confirmButton.setAttribute('disabled', 'disabled');
    }

    checkoutView().updateTotals(Checkout);
    checkoutView().updatePaymentList(Checkout);
}

function updateOrderDiscount() {
    Cart.setDiscountPercent(this.value);
}

function closeSessionHandler() {
    mainView().closeSessionForm.submit();
}

export function scanCodeHandler(code) {
    console.log('Search barcode:', code);
    Core.searchBarcode(code).then(response => {
        if (response.code) {
            Cart.addProduct(response.code, response.description);
        } else {
            console.log('Barcode not found');
        }
    });
}

export function saveNewCostumerHandler() {
    const taxID = Core.getElement('newCustomerTaxID').value;
    const name = Core.getElement('newCustomerName').value;

    /**
     * @param {{codcliente:string, razonsocial:string}} response
     */
    function saveCustomer(response) {
        if (response.codcliente) {
            Cart.setCustomer(response.codcliente);
            mainView().updateCustomer(response.razonsocial);
        }
    }

    Core.saveNewCustomer(taxID, name).then(saveCustomer);
}

document.addEventListener("DOMContentLoaded", () => {
    /* global onScan*/
    onScan.attachTo(document);

    document.addEventListener('scan', function (event) {
        scanCodeHandler(event.detail.scanCode);
    });
});

mainView().customerSaveButton.addEventListener('click', saveNewCostumerHandler);
mainView().closeSessionButton.addEventListener('click', closeSessionHandler);
mainView().customerSearchBox.addEventListener('keyup', searchCustomerHandler);
mainView().customerListView.addEventListener('click', commonEventHandler);
mainView().documentTypeListView.addEventListener('click', commonEventHandler);
//mainView().documentTypeListView.addEventListener('click', documentEventHandler);
mainView().holdOrdersList.addEventListener('click', commonEventHandler);
mainView().productSearchBox.addEventListener('keyup', searchProductHandler);
mainView().productListView.addEventListener('click', setProductHandler);
cartView().listView.addEventListener('click', commonEventHandler);
cartView().editView.addEventListener('focusout', commonEventHandler);
cartView().discountPercent.addEventListener('focusout', updateOrderDiscount);
cartView().holdButton.addEventListener('click', holdOrder);
checkoutView().confirmButton.addEventListener('click', saveOrder);
checkoutView().listView.addEventListener('click', commonEventHandler);
checkoutView().paymentApplyButton.addEventListener('click', setPayment);
checkoutView().paymentAmounButton.forEach(element => element.addEventListener('click', recalculatePaymentAmount));
checkoutView().paymentModalButton.forEach(element => element.addEventListener('click', showPaymentModal));
document.addEventListener('updateCartEvent', updateCart);
document.addEventListener('updateCartViewEvent', updateCartView);
document.addEventListener('updateCheckout', updateCheckoutView);
