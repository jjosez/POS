/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Core from './Core.js';
import * as Order from "./Order.js";
import {mainView} from "./UI.js";
import Cart from "./modules/Cart.js"
import Checkout from "./modules/Checkout.js";

/**
 * @param {{code:string}} data
 */
async function orderDeleteAction(data) {
    await Order.deleteHoldRequest(data.code);

    mainView().updateHoldOrdersList(await Order.getOnHoldRequest());
    mainView().toggleHoldOrdersModal();
}

/**
 * @param {{code:string}} data
 */
async function orderPrintAction({code}) {
    await Order.reprintRequest(code);
    mainView().toggleLastOrdersModal();
}

/**
 * @param {{code:string}} data
 */
async function orderResumeAction({code}) {
    Cart.update(await Order.resumeRequest(code));
    mainView().toggleHoldOrdersModal();
}

async function orderSaveAction() {
    const wasOnHold = Cart.doc.idpausada;
    if (Cart.lines.length < 1) return;

    Cart.update(await Order.saveRequest(Cart, Checkout.payments));
    Checkout.clear();

    //mainView().updateProductListView(await Core.searchProduct(''));
    mainView().updateLastOrdersListView(await Order.getLastOrders());

    if (wasOnHold) {
        mainView().updateHoldOrdersList(await Order.getOnHoldRequest());
    }
}

async function orderSuspendAction() {
    if (Cart.lines.length < 1) return;

    Cart.update(await Order.holdRequest(Cart));
    mainView().updateHoldOrdersList(await Order.getOnHoldRequest());
}

async function searchBarcodeAction(code) {
    let response = await Core.searchBarcode(code);

    if (response.code) {
        Cart.setProduct(response.code, response.description);
    }
}

async function searchCustomerAction() {
    mainView().updateCustomerListView(await Core.searchCustomer(this.value));
}

async function searchProductAction() {
    mainView().updateProductListView(await Core.searchProduct(this.value));
}

function sessionCloseAction() {
    mainView().closeSessionForm.submit();
}

function sessionMoneyMovmentAction() {
    mainView().cashMovmentForm.submit();
}

async function sessionPrintClosingVoucherAction() {
    await Core.printClosingVoucher();
    mainView().toggleCloseSessionModal();
}

async function showStockDetailAction({code}) {
    mainView().updateStockListView(await Core.getProductStock(code));
    mainView().toggleStockDetailModal();
}

async function showProductImagesAction({id, code}) {
    mainView().updateProductImageListView(await Core.getProductImages(id, code));
    mainView().toggleProductImageModal();
}

async function showProductFamiliesAction({code, madre}) {
    mainView().updateFamilyListView(await Core.getProductFamilyChild(code, madre));
}

/**
 * @param {Event} event
 */
async function appEventHandler(event) {
    const data = event.target.dataset;
    const action = data.action;

    if (typeof action === 'undefined' || action === null) {
        return;
    }

    switch (action) {
        case 'closeSessionAction':
            return sessionCloseAction();

        case 'deleteOrderAction':
            return orderDeleteAction(data);

        case 'holdOrderAction':
            return orderSuspendAction();

        case 'moneyInOutAction':
            return sessionMoneyMovmentAction();

        case 'resumeOrderAction':
            return orderResumeAction(data);

        case 'printOrderAction':
            return orderPrintAction(data);

        case 'printClosingVoucher':
            return sessionPrintClosingVoucherAction(data);

        case 'productImageAction':
            return showProductImagesAction(data);

        case 'saveCustomerAction':
            return saveCustomerHandler();

        case 'saveOrderAction':
            return orderSaveAction();

        case 'stockDetailAction':
            return showStockDetailAction(data);

        case 'setProductFilter':
            return console.log('FiltroProducto');

        case 'setProductFamilyAction':
            return showProductFamiliesAction(data);
    }
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

window.setCartCustomField = function (field, value) {
    Cart.doc[field] = value;
}

document.addEventListener("DOMContentLoaded", () => {
    /* global onScan*/
    onScan.attachTo(document);

    document.addEventListener('scan', event => {
        return searchBarcodeAction(event.detail.scanCode);
    });
});

mainView().customerSearchBox.addEventListener('keyup', searchCustomerAction);
mainView().productSearchBox.addEventListener('keyup', searchProductAction);
document.addEventListener('click', appEventHandler);
