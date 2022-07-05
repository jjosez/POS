/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Core from './Core.js';
import {cartView, checkoutView, mainView} from "./UI.js";
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

async function printClosingVoucherHandler() {
    await Core.printClosingVoucher();

    mainView().toggleCloseSessionModal();
}

/**
 * @param {{code:string}} data
 */
async function printOrderHandler(data) {
    await Order.reprintRequest(data.code);

    mainView().toggleLastOrdersModal();
}

function recalculatePaymentHandler({value}) {
    if (value === 'balance') {
        checkoutView().paymentInput.value = Checkout.getOutstandingBalance();
    } else {
        let amount = parseFloat(checkoutView().paymentInput.value) || 0;
        amount += parseFloat(value) || 0;
        checkoutView().paymentInput.value = amount;
    }
}

/**
 * @param {{code:string}} data
 */
async function resumeOrderHandler(data) {
    Cart.update(await Order.resumeRequest(data.code));

    mainView().toggleHoldOrdersModal();
}

async function searchBarcodeHandler(code) {
    let result = await Core.searchBarcode(code);

    if (result.code) {
        Cart.setProduct(result.code, result.description);
    }
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
function setCustomerHandler({code, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setCustomer(code);
    mainView().updateCustomer(description);
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setDocumentHandler({code, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setDocumentType(code);
    mainView().updateDocument(description);
}

function setPayment() {
    Checkout.setPayment(checkoutView().paymentInput.value, checkoutView().paymentInput.dataset);
    checkoutView().paymentInput.value = 0;
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setProductHandler({code, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setProduct(code, description);
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

/**
 * @param {string} data
 */
async function stockDetailHandler(data) {
    mainView().updateStockListView(await Core.getProductStock(data.code));
    mainView().toggleStockDetailModal();
}

async function updateCart() {
    Cart.update(await Order.recalculateRequest(Cart));
}

/**
 * @param {{detail}} data
 */
function updateCartView({detail}) {
    cartView().updateListView(detail);
    cartView().updateTotals(detail);
    Checkout.total = detail.doc.total;

    if (detail.doc.total === 0) {
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

    if (typeof action === 'undefined' || action === null) {
        return;
    }

    console.log('Action:', action);
    switch (action) {
        case 'setDocumentAction':
            setDocumentHandler(data);
            return;
        case 'setCustomerAction':
            setCustomerHandler(data);
            return;
        case 'setProductAction':
            setProductHandler(data);
            return;
        case 'closeSessionAction':
            closeSessionHandler();
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
        case 'holdOrderAction':
            holdOrder();
            return;
        case 'moneyInOutAction':
            moneyInOutHandler();
            return;
        case 'resumeOrderAction':
            void resumeOrderHandler(data);
            case 'recalculatePaymentAction':
            void recalculatePaymentHandler(data);
            return;
        case 'setPaymentAction':
            setPayment();
            return;
        case 'showPaymentModalAction':
            void showPaymentModal(data);
            return;
        case 'printOrderAction':
            void printOrderHandler(data);
            return;
        case 'printClosingVoucher':
            void printClosingVoucherHandler(data);
            return;
        case 'saveCustomerAction':
            void saveCustomerHandler();
            return;
        case 'saveOrderAction':
            saveOrder();
            return;
        case 'stockDetailAction':
            void stockDetailHandler(data);
            event.stopPropagation();
            return;
    }
}

function showPaymentModal(data) {
    checkoutView().showPaymentModal(data);
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

function moneyInOutHandler() {
    mainView().cashMovmentForm.submit();
}

async function saveCustomerHandler() {
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
        void searchBarcodeHandler(event.detail.scanCode);
    });
});

mainView().customerSearchBox.addEventListener('keyup', searchCustomerHandler);
mainView().productSearchBox.addEventListener('keyup', searchProductHandler);
cartView().discountPercent.addEventListener('focusout', updateOrderDiscount);
document.addEventListener('click', commonEventHandler);
document.addEventListener('updateCartEvent', updateCart);
document.addEventListener('updateCartViewEvent', updateCartView);
document.addEventListener('updateCheckout', updateCheckoutView);
