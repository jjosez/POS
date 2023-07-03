/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Core from './Core.js';
import * as Order from "./Order.js";
import * as View from "./View.js";
import Cart from "./modules/Cart.js"
import Checkout from "./modules/Checkout.js";

/**
 * @param {{code:string}} data
 */
async function orderDeleteAction(data) {
    await Order.deleteHoldRequest(data.code);

    if ((data.code * 1) === (Cart.doc.idpausada * 1)) location.reload();

    View.modals().pausedOrdersModal().hide();
}

/**
 * @param {{code:string}} data
 */
async function orderPrintAction({code}) {
    await Order.reprintRequest(code);
    View.modals().lastOrdersModal().hide();
}

/**
 * @param {{code:string}} data
 */
async function pausedOrderPrintAction({code}) {
    await Order.reprintPausedOrderRequest(code);
    View.modals().pausedOrdersModal().hide();
}

/**
 * @param {{code:string}} data
 */
async function orderResumeAction({code}) {
    Cart.update(await Order.resumeRequest(code));
    Cart.updateDocumentClass();

    const elements = document.querySelectorAll('[data-serie]');

    for (const element of elements) {
        if (element.dataset.code === Cart.doc.generadocumento && element.dataset.serie === Cart.doc.codserie) {
            View.main().updateDocumentNameLabel(element.dataset.description);
            break;
        }
    }

    View.main().updateCustomerNameLabel(Cart.doc.nombrecliente);
    View.modals().pausedOrdersModal().hide();
}

async function orderSaveAction() {
    if (Cart.lines.length < 1) return;

    Cart.update(await Order.saveRequest(Cart, Checkout.payments));
    Checkout.clear();

    Cart.setDocumentType(AppSettings.document.code, AppSettings.document.serie)
    View.main().updateDocumentNameLabel(AppSettings.document.description);
}

async function orderSuspendAction() {
    if (Cart.lines.length < 1) return;

    Cart.update(await Order.holdRequest(Cart));

    Cart.setDocumentType(AppSettings.document.code, AppSettings.document.serie)
    View.main().updateDocumentNameLabel(AppSettings.document.description);
}

async function saveCustomerAction() {
    const taxID = Core.getElement('newCustomerTaxID').value;
    const name = Core.getElement('newCustomerName').value;
    const response = await Core.saveNewCustomer(taxID, name);


    if (response.customer.codcliente) {
        Cart.setCustomer(response.customer.codcliente);
        View.main().updateCustomerNameLabel(response.customer.razonsocial);
        View.modals().customerSearchModal().hide();
    }
}

async function searchBarcodeAction(code) {
    let response = await Core.searchBarcode(code);

    if (response.code) {
        Cart.setProduct(response.code, response.description);
    }
}

async function searchCustomerAction() {
    View.main().updateCustomerListView(await Core.searchCustomer(this.value));
}

async function searchProductAction() {
    View.main().updateProductSearchResult(await Core.searchProduct(this.value));
}

function sessionCloseAction() {
    View.main().closeSessionForm().submit();
}

function sessionMoneyMovmentAction() {
    View.main().cashMovmentForm().submit();
}

async function sessionPrintClosingVoucherAction() {
    await Core.printClosingVoucher();
    View.modals().closeSessionModal().hide();
}

async function showStockDetailAction({code}) {
    View.main().showProductStockDetailModal(await Core.getProductStock(code));
}

async function showProductImagesAction({id, code}) {
    View.main().showProductImagesModal(await Core.getProductImages(id, code));
}

async function showProductFamiliesAction({code, madre}) {
    View.main().updateProductFamilyList(await Core.getProductFamilyChild(code, madre));
}

async function showPausedOrdersAction() {
    View.main().showPausedOrdersModal(await Order.getOnHoldRequest());
}

async function showLastOrdersAction() {
    View.main().showLastOrdersModal(await Order.getLastOrders());
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

        case 'printPausedOrderAction':
            return pausedOrderPrintAction(data);

        case 'printClosingVoucher':
            return sessionPrintClosingVoucherAction(data);

        case 'productImageAction':
            return showProductImagesAction(data);

        case 'saveCustomerAction':
            return saveCustomerAction();

        case 'saveOrderAction':
            return orderSaveAction();

        case 'stockDetailAction':
            return showStockDetailAction(data);

        case 'setProductFilter':
            return console.log('FiltroProducto');

        case 'setProductFamilyAction':
            return showProductFamiliesAction(data);

        case 'showPausedOrders':
            return showPausedOrdersAction();

        case 'showLastOrders':
            return showLastOrdersAction();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    /* global onScan*/
    onScan.attachTo(document);

    document.addEventListener('scan', event => {
        return searchBarcodeAction(event.detail.scanCode);
    });
});

View.main().customerSearchBox().addEventListener('keyup', searchCustomerAction);
View.main().productSearchBox().addEventListener('keyup', searchProductAction);
document.addEventListener('click', appEventHandler);
