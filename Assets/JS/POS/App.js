/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Order from "./controllers/OrderController.js";
import CartController from "./controllers/CartController.js";
import CheckoutController from "./controllers/CheckoutController.js";
import CustomerController from "./controllers/CustomerController.js";
import PrintController from './controllers/PrintController.js';
import ProductController from './controllers/ProductController.js';
import SessionController from './controllers/SessionController.js';
import eventDispatcher from "./core/EventDispatcher.js";
import EventManager from "./core/EventManager.js";
import MainView from "./views/MainView.js";

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
async function orderResumeAction({code}) {
    const updatedCart = await Order.resumeRequest(code);

    CartController.update(updatedCart);

    EventManager.emit('onOrderResume', updatedCart.doc);
    MainView.toggleDraftOrdersModal();
}

async function orderSaveAction() {
    if (!CartController.hasLines()) return;

    const result = await Order.saveRequest(CartController.getState(), CheckoutController.getState().payments);

    CartController.update(result);

    if (result?.status === 'success') {
        MainView.showPrintOrderContextModal(result.data);
        EventManager.emit('onOrderComplete', result)
    }
}

async function orderSuspendAction() {
    if (!CartController.hasLines()) return;

    const result = await Order.saveDraftRequest(CartController.getState());

    CartController.update(result);
    EventManager.emit('onOrderComplete', result);
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
async function appEventHandler(event){
    const data = event.target.dataset;
    const action = data.action;

    if (typeof action === 'undefined' || action === null) {
        return;
    }

    switch (action) {
        case 'orderDeleteAction':
            return orderDeleteAction(data);

        case 'orderSuspendAction':
            return orderSuspendAction();

        case 'orderResumeAction':
            return orderResumeAction(data);

        case 'orderSaveAction':
            return orderSaveAction();

        case 'showPausedOrders':
            return showPausedOrdersAction();

        case 'showLastOrdersAction':
            return showLastOrdersAction();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    /* global onScan*/
    onScan.attachTo(document);

    SessionController.init();
    CheckoutController.init();
    CartController.init();
    CustomerController.init();
    PrintController.init();
    ProductController.init();

    eventDispatcher.listen();
});

document.addEventListener('click', appEventHandler);
