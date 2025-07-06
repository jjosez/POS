/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Core from './Core.js';
import * as Order from "./controllers/OrderController.js";
import * as View from "./View.js";
import {searchFilter} from './models/FilterModel.js';
import CartController from "./controllers/CartController.js";
import CheckoutController from "./controllers/CheckoutController.js";
import eventDispatcher from "./core/EventDispatcher.js";
import eventManager from "./core/EventManager.js";
import MainView from "./view/MainView.js";

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

    if (CartController.isDraftOrder(data.code)) {
        location.reload();
    }

    MainView.toggleDraftOrdersModal();
}

/**
 * @param {{code:string}} data
 */
async function printOrderContextAction({code, model, order}) {
    MainView.showPrintOrderSelectionModal({document_code: code, document_model: model, document_order: order});
}

/**
 * @param {{code:string}} data
 */
async function printDraftContextAction({code, model, order}) {
    MainView.showPrintDraftSelectionModal({code: code, document: model, order: order});
}

/**
 * @param {{}} data
 */
async function printDraftTicketAction(data) {
    if (data.type === 'link') {
        Core.openLinkAction(data.controller, data.params);
        return;
    }

    const formData = new FormData();

    formData.set('action', 'print-draft-ticket');
    formData.set('code', data.code);
    formData.set('action-name', data.name);

    let params = JSON.parse(data.params)
    formData.set('action-params', JSON.stringify(params));
    formData.set('document', data.document);

    const response = await Core.postRequest(formData);
    Core.printerServerRequest(response);

    MainView.togglePrintSelectionModal();
}

/**
 * @param {{}} data
 */
async function printOrderTicketAction(data) {
    if (data.type === 'link') {
        Core.openLinkAction(data.controller, data.params);
        return;
    }

    const formData = new FormData();
    let params = JSON.parse(data.params)

    formData.set('action', 'print-sales-ticket');
    formData.set('action-name', data.name);
    formData.set('action-params', JSON.stringify(params));
    formData.set('document-code', data.code);
    formData.set('document-model', data.document);
    formData.set('document-order', data.order);

    const response = await Core.postRequest(formData);
    Core.printerServerRequest(response);

    MainView.togglePrintSelectionModal();
}

/**
 * @param {{code:string}} data
 */
async function orderResumeAction({code}) {
    const updatedCart = await Order.resumeRequest(code);

    CartController.update(updatedCart);

    eventManager.emit('onOrderResume', updatedCart.doc);
    MainView.toggleDraftOrdersModal();
}

async function orderSaveAction() {
    if (!CartController.hasLines()) return;

    const response = await Order.saveRequest(CartController.getState(), CheckoutController.getState().payments);

    if (!response || response.success === false) {
        //console.warn('❌ Pedido no guardado correctamente');
        //return;
    }

    CartController.update(response);
    eventManager.emit('onOrderComplete', response);

    if (response?.document_code) {
        MainView.showPrintOrderSelectionModal(response);
    }
}

async function orderSuspendAction() {
    if (!CartController.hasLines()) return;

    const response = await Order.holdRequest(CartController.getState());

    CartController.update(response);
    eventManager.emit('onOrderComplete', response);
}

async function saveCustomerAction() {
    const taxID = Core.getElement('newCustomerTaxID').value;
    const name = Core.getElement('newCustomerName').value;
    const response = await Core.saveNewCustomer(taxID, name);


    if (response.customer.codcliente) {

        eventManager.emit('onCustomerChange', {
            code: response.customer.codcliente,
            description: response.customer.nombre
        });
    }
}

async function searchBarcodeAction(code) {
    let response = await Core.searchBarcode(code);

    if (response.code) {
        CartController.addScannedProduct(response);
    }
}

async function searchProductAction() {
    MainView.updateProductSearchResult(await Core.searchProduct(this.value, searchFilter));
}

async function sessionCloseAction() {
    MainView.toggleLoadingModal();
    const formData = new FormData(MainView.closeSessionForm());
    const response = await Core.postRequest(formData);

    await Core.printerServerRequest(response);
    Core.reloadApp();
}

async function printClosingTicketAction() {
    MainView.toggleLoadingModal();

    try {
        const response = await Core.printClosingTicket();
        await Core.printerServerRequest(response);
    } finally {
        MainView.toggleLoadingModal();
    }
}

async function setFamilyFilterAction({code, description, thumbnail}) {
    searchFilter.toggleFamilyFilter(code, description, thumbnail);

    eventManager.emit('onProductFilterChange', searchFilter);
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

        case 'orderDeleteAction':
            return orderDeleteAction(data);

        case 'orderSuspendAction':
            return orderSuspendAction();

        case 'orderResumeAction':
            return orderResumeAction(data);

        case 'printOrderContextAction':
            return printOrderContextAction(data);

        case 'printDraftContextAction':
            return printDraftContextAction(data);

        case 'printClosingTicketAction':
            return printClosingTicketAction(data);

        case 'printOrderTicketAction':
            return printOrderTicketAction(data);

        case 'printDraftTicketAction':
            return printDraftTicketAction(data);

        case 'productImageAction':
            return showProductImagesAction(data);

        case 'saveCustomerAction':
            return saveCustomerAction();

        case 'orderSaveAction':
            return orderSaveAction();

        case 'stockDetailAction':
            return showStockDetailAction(data);

        case 'setProductFilter':
            return console.log('FiltroProducto');

        case 'setFamilyFilterAction':
            return setFamilyFilterAction(data);

        case 'showPausedOrders':
            return showPausedOrdersAction();

        case 'showLastOrdersAction':
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

MainView.productSearchBox().addEventListener('keyup', searchProductAction);
document.addEventListener('click', appEventHandler);
eventDispatcher.listen();
CheckoutController.init();
CartController.init();
