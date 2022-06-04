/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Core from './Core.js';
import * as EventHandler from './EventHandler.js';
import * as UI from './AppUI.js';
import {cartView, searchView} from "./UI.js";
import {Checkout} from './CheckoutModule.js';
import * as Order from "./Order.js";
import CartClass from "./CartClass.js";
import {settings} from "./Core.js";

export const Cart = new CartClass({
    'doc': {
        'codcliente': settings.customer,
        'idpausada': 'false',
        'tipo-documento': settings.document,
        'token': settings.token
    }
});

function saveOrder() {
    if (Cart.lines.length < 1) {
        return;
    }

    Order.saveRequest(Cart, Checkout.payments).then(response => {
        Cart.updateClean(response);
        Checkout.clear();
        searchProductHandler();
    });
}

function searchProductHandler() {
    Core.searchProduct(this.value || '').then(response => {
        searchView().updateListView(response);
    });
}

/**
 * @param {Event} event
 */
function addProductHandler(event) {
    console.log('Add product', event.target.dataset.code)
    const data = event.target.dataset;
    if (typeof data.code === 'undefined' || data.code === null) {
        return;
    }
    Cart.addProduct(data.code, data.description);
}

/**
 * @param {EventTarget} target
 */
function deleteProductHandler(target) {
    console.log('Delete product', target)
    Cart.deleteProduct(target.dataset.index);
}

/**
 * @param {EventTarget} target
 */
function editProductHandler(target) {
    console.log('Edit product', target)
    updateEditView(target.dataset.index);

    if (true === cartView().editView.classList.contains('hidden')) {
        cartView().toggleEditView();
        searchView().toggleMainView();
    }
}

/**
 * @param {EventTarget} target
 */
function editProductFieldHandler(target) {
    console.log('Edit field handler', target)
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
 * @param {Array} data
 */
function updateCartView(data) {
    cartView().updateListView(data.detail);
}

/**
 * @param index
 */
function updateEditView(index) {
    let data = Cart.getProduct(index);
    cartView().updateEditForm(data);
}

/**
 * @param {Event} event
 */
function cartEventHandler(event) {
    const target = event.target;

    switch (true) {
        case target.matches('.delete-product-btn'):
            deleteProductHandler(target);
            return;
        case target.matches('.edit-product-btn'):
            editProductHandler(target);
            return;
        case target.matches('.edit-product-field'):
            editProductFieldHandler(target);
            return;
    }
}

//UI.orderHoldButton.addEventListener('click', holdOrder);
UI.orderSaveButton.addEventListener('click', saveOrder);
//UI.pausedOrdersList.addEventListener('click', EventHandler.customEventHandler);
//UI.customerSaveButton.addEventListener('click', EventHandler.saveNewCostumerHandler);
//UI.customerSearchBox.addEventListener('keyup', EventHandler.searchCustomerHandler);
//UI.customerSearchResult.addEventListener('click', EventHandler.customEventHandler);
searchView().searchBox.addEventListener('keyup', searchProductHandler);
searchView().listView.addEventListener('click', addProductHandler);
cartView().listView.addEventListener('click', cartEventHandler);
cartView().listView.addEventListener('click', cartEventHandler);
cartView().editView.addEventListener('focusout', cartEventHandler);
document.addEventListener('updateCartEvent', updateCart);
document.addEventListener('updateCartViewEvent', updateCartView);



