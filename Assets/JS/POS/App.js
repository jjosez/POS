/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Core from './Core.js';
import * as Order from "./Order.js";
import MainView from "./view/MainView.js";
import CartView from "./view/CartView.js";
import Cart from "./modules/Cart.js"
import Checkout from "./modules/Checkout.js";
import EventManager from "./components/EventManager.js";
import FilterClass from "./model/FilterClass.js";

const SearchFilter = new FilterClass({
    'families': [],
    'filters': []
});

function cashEntryAction() {
    MainView.cashEntryForm().submit();
}

function cashWithdrawAction() {
    MainView.cashWithdrawForm().submit();
}

/**
 * @param {{code:string}} data
 */
async function orderDeleteAction(data) {
    await Order.deleteHoldRequest(data.code);

    if ((data.code * 1) === (Cart.doc.idpausada * 1)) location.reload();

    MainView.toggleDraftOrdersModal();
}

/**
 * @param {{code:string}} data
 */
async function orderPrintAction({code, type}) {
    const data = {
        code: code,
        type: type
    }

    const response = await Order.printOnDesktop(data);
    //MainView.toggleLastOrdersModal();
    MainView.togglePrintSelectionModal();

    await Core.printerServerRequest(response);
}

/**
 * @param {{code:string}} data
 */
async function pausedOrderPrintAction({code}) {
    const response = await Order.printPausedOrderRequest(code);
    MainView.toggleDraftOrdersModal();

    await Core.printerServerRequest(response);
}

/**
 * @param {{code:string}} data
 */
async function orderResumeAction({code}) {
    Cart.update(await Order.resumeRequest(code));
    Cart.updateDocumentClass();

    EventManager.emit('onOrderResume', Cart);
    MainView.toggleDraftOrdersModal();
}

async function orderSaveAction() {
    if (Cart.lines.length < 1) return;

    const response = await Order.saveRequest(Cart, Checkout.payments);

    Cart.update(response);
    EventManager.emit('onOrderComplete', response);

    MainView.showPrintSelectionModal(response);
    await Core.printerServerRequest(response);
}

async function orderSuspendAction() {
    if (Cart.lines.length < 1) return;

    const response = await Order.holdRequest(Cart);

    Cart.update(response);
    EventManager.emit('onOrderComplete', response);
}

async function saveCustomerAction() {
    const taxID = Core.getElement('newCustomerTaxID').value;
    const name = Core.getElement('newCustomerName').value;
    const response = await Core.saveNewCustomer(taxID, name);


    if (response.customer.codcliente) {

        EventManager.emit('onCustomerChange', {
            code: response.customer.codcliente,
            description: response.customer.nombre
        });
    }
}

async function searchBarcodeAction(code) {
    let response = await Core.searchBarcode(code);

    if (response.code) {
        Cart.setProduct(response.code, response.description);
    }
}

async function searchCustomerAction() {
    MainView.updateCustomerListView(await Core.searchCustomer(this.value));
}

async function searchProductAction() {
    MainView.updateProductSearchResult(await Core.searchProduct(this.value, SearchFilter));
}

async function sessionCloseAction() {
    MainView.toggleLoadingModal();
    const formData = new FormData(MainView.closeSessionForm());
    const response = await Core.postRequest(formData);

    await Core.printerServerRequest(response);
    Core.reloadApp();
}

async function sessionPrintClosingVoucherAction() {
    const response = await Core.printClosingVoucher();
    await Core.printerServerRequest(response);

    MainView.toggleCloseSessionModal();
}

async function setFamilyFilterAction({code, description, thumbnail}) {
    SearchFilter.setFamilyFilter(code, description, thumbnail);

    EventManager.emit('onProductFilterChange', SearchFilter);
}

async function showStockDetailAction({code}) {
    const stock = await Core.getProductStock(code);
    MainView.showProductStockDetailModal(stock);
}

async function showProductImagesAction({id, code}) {
    const images = await Core.getProductImages(id, code);
    MainView.showProductImagesModal(images);
}

async function showPausedOrdersAction() {
    const orders = await Order.getOnHoldRequest();
    MainView.showPausedOrdersModal(orders);
}

async function showLastOrdersAction() {
    const orders = await Order.getLastOrders();
    MainView.showLastOrdersModal(orders);
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
        case 'cashEntryAction':
            return cashEntryAction();

        case 'cashWithdrawAction':
            return cashWithdrawAction();

        case 'closeSessionAction':
            return sessionCloseAction();

        case 'deleteOrderAction':
            return orderDeleteAction(data);

        case 'holdOrderAction':
            return orderSuspendAction();

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
            return setFamilyFilterAction(data);

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

CartView.customerSearchBox().addEventListener('keyup', searchCustomerAction);
MainView.productSearchBox().addEventListener('keyup', searchProductAction);
document.addEventListener('click', appEventHandler);
