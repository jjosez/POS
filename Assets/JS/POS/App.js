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
        'codalmacen': settings.warehouse,
        'codcliente': settings.customer,
        'idpausada': 'false',
        'tipo-documento': settings.document
    },
    'token': settings.token
});

export const Checkout = new CheckoutModel(settings.cash);

//window.App = {};

async function saveOrder() {
    const wasOnHold = Cart.doc.idpausada;
    if (Cart.lines.length < 1) return;

    Cart.update(await Order.saveRequest(Cart, Checkout.payments));
    Checkout.clear();

    mainView().updateProductListView(await Core.searchProduct(''));
    mainView().updateLastOrdersListView(await Order.getLastOrders());

    if (wasOnHold) {
        mainView().updateHoldOrdersList(await Order.getOnHoldRequest());
    }
}

async function holdOrder() {
    if (Cart.lines.length < 1) return;

    Cart.update(await Order.holdRequest(Cart));
    mainView().updateHoldOrdersList(await Order.getOnHoldRequest());
}

/**
 * @param {{code:string}} data
 */
async function resumeOrderHandler(data) {
    Cart.update(await Order.resumeRequest(data.code));

    mainView().toggleHoldOrdersModal();
}

/**
 * @param {{code:string}} data
 */
async function reprintOrderHandler(data) {
    await Order.reprintRequest(data.code);

    mainView().toggleLastOrdersModal();
}

async function printClosingVoucherHandler() {
    await Core.printClosingVoucher();

    mainView().toggleCloseSessionModal();
}

async function searchCustomerHandler() {
    mainView().updateCustomerListView(await Core.searchCustomer(this.value));
}

async function searchProductHandler() {
    mainView().updateProductListView(await Core.searchProduct(this.value));
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setCustomerHandler(data) {
    if (typeof data.code === 'undefined' || data.code === null) {
        return;
    }
    Cart.setCustomer(data.code);
    mainView().updateCustomer(data.description);
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setDocumentHandler(data) {
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
    const data = event.target.dataset;
    if (typeof data.code === 'undefined' || data.code === null) {
        return;
    }
    Cart.setProduct(data.code, data.description);
}

/**
 * @param {{code:string}} data
 */
async function deleteOrderHandler(data) {
    await Order.deleteHoldRequest(data.code);

    mainView().updateHoldOrdersList(await Order.getOnHoldRequest());
    mainView().toggleHoldOrdersModal();
}

/**
 * @param {{index:int}} data
 */
function deletePaymentHandler(data) {
    Checkout.deletePayment(data.index);
}

/**
 * @param {{index:int}} data
 */
function deleteProductHandler(data) {
    Cart.deleteProduct(data.index);
}

/**
 * @param {{index:int}} data
 */
function editProductHandler(data) {
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
    const index = target.dataset.index;
    Cart.editProduct(index, target.dataset.field, target.value);

    updateCart().then(() => {
        updateEditView(index);
    });
}

async function updateCart() {
    Cart.update(await Order.recalculateRequest(Cart));
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
            void resumeOrderHandler(data);
            return;
        case 'printOrderAction':
            void reprintOrderHandler(data);
            return;
        case 'printClosingVoucher':
            void printClosingVoucherHandler(data);
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

async function scanCodeHandler(code) {
    let result = await Core.searchBarcode(code);

    if (result.code) {
        Cart.setProduct(result.code, result.description);
        return;
    }
    console.log('Barcode not found');
}

async function saveNewCostumerHandler() {
    const taxID = Core.getElement('newCustomerTaxID').value;
    const name = Core.getElement('newCustomerName').value;
    const response = await Core.saveNewCustomer(taxID, name);

    if (response.codcliente) {
        Cart.setCustomer(response.codcliente);
        mainView().updateCustomer(response.razonsocial);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    /* global onScan*/
    onScan.attachTo(document);

    document.addEventListener('scan', function (event) {
        void scanCodeHandler(event.detail.scanCode);
    });
});

mainView().customerSaveButton.addEventListener('click', saveNewCostumerHandler);
mainView().closeSessionButton.addEventListener('click', closeSessionHandler);
mainView().closeSessionModal.addEventListener('click', commonEventHandler);
mainView().customerSearchBox.addEventListener('keyup', searchCustomerHandler);
mainView().customerListView.addEventListener('click', commonEventHandler);
mainView().documentTypeListView.addEventListener('click', commonEventHandler);
//mainView().documentTypeListView.addEventListener('click', documentEventHandler);
mainView().holdOrdersList.addEventListener('click', commonEventHandler);
mainView().lastOrdersList.addEventListener('click', commonEventHandler);
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
