/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import {getElement} from "./Core.js";
import * as Money from "./Money.js";

export const cartView = () => {
    return {
        'main': getElement('cartMainView'),
        'editForm': getElement('cartEditForm'),
        'editView': getElement('cartEditView'),
        'listView': getElement('cartListView'),
        'editTemplate': getTemplate('cartEditTemplate'),
        'listTemplate': getTemplate('cartListTemplate'),
        'itemsNumber': getElement('orderItemsNumber'),
        'subtotal': getElement('orderSubtotal'),
        'discountPercent': getElement('orderDiscount'),
        'discountAmount': getElement('orderDiscountAmount'),
        'taxes': getElement('orderTaxes'),
        'totalNet': getElement('orderTotalNet'),
        'total': getElement('orderTotal'),


        toggleEditView: function () {
            this.editView.classList.toggle('hidden');
        },

        updateListView: function (data = []) {
            this.listView.innerHTML = this.listTemplate(data, Eta.config);
        },

        updateEditForm: function (data = []) {
            this.editForm.innerHTML = this.editTemplate(data, Eta.config);
        },

        updateTotals: function (data = {}) {
            this.itemsNumber.textContent = data.count;
            this.subtotal.textContent = Money.roundFixed(data.doc.netosindto);
            this.discountPercent.value = data.doc.dtopor1 || 0;
            this.discountAmount.textContent = Money.roundFixed(data.getDiscountAmount());
            this.totalNet.textContent = Money.roundFixed(data.doc.neto);
            this.taxes.textContent = Money.roundFixed(data.doc.totaliva);
            this.total.textContent = Money.roundFixed(data.doc.total);
        }
    };
}

export const searchView = () => {
    return {
        'main': getElement('productMainView'),
        'searchBox': getElement('productSearchBox'),
        'listView': getElement('productSearchResult'),
        'listTemplate': getTemplate('productListTemplate'),

        toggleMainView: function () {
            this.main.classList.toggle('hidden');
        },

        updateListView: function (data = []) {
            this.listView.innerHTML = this.listTemplate({items: data}, Eta.config);
        }
    };
}

export const customerView = () => {
    return {
        'searchBox': getElement('customerSearchBox'),
        'listView': getElement('customerSearchResult'),
        'listTemplate': getTemplate('customerListTemplate')
    }
}

export const checkoutView = () => {
    return {
        'listView': getElement('paymentList'),
        'listTemplate': getTemplate('paymentListTemplate'),
        'confirmButton': getElement('orderSaveButton'),
        'change': getElement('checkoutChange'),
        'tendered': getElement('checkoutTotalTendered'),
        'paymentModal': getElement('addPaymentModal'),
        'paymentModalButton': document.querySelectorAll('.payment-modal-btn'),
        'paymentAmounButton': document.querySelectorAll('.payment-add-btn'),
        'paymentApplyButton': getElement('paymentApplyButton'),
        'paymentInput': getElement('paymentApplyInput'),

        togglePaymentModal: function () {
            toggleModal(this.paymentModal);
        },

        showPaymentModal: function (code) {
            this.paymentInput.dataset.method = code;
            toggleModal(getElement('addPaymentModal'));
        },

        updatePaymentList: function (data = []) {
            this.listView.innerHTML = this.listTemplate(data, Eta.config);
        },

        updateTotals: function (data = {}) {
            this.change.textContent = data.change;
            this.tendered.textContent = data.getPaymentsTotal();
        }
    }
}

export const appView = () => {
    return {
        'searchBox': ''
    }
}

/**
 * @param {string} id
 */
function getTemplate(id) {
    return Eta.compile(getElement(id).innerHTML);
}

/**
 * @param {HTMLElement} element
 */
export function toggleModal(element) {
    element.classList.toggle("flex");

    if (element.classList.toggle("hidden")) {
        document.querySelector('.modal-backdrop').remove();
    } else {
        let backdrop = document.createElement('div');
        backdrop.classList.add('modal-backdrop');
        document.querySelector('body').append(backdrop);
    }
}
