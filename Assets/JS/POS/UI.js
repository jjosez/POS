/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import {getElement} from "./Core.js";
import * as Money from "./Money.js";
import * as view from "./View.js";

export const cartView = () => {
    return {
        showQuantityEditView: function (producto) {
            view.cart().productQuantityInput().dataset.index = producto.index;
            view.cart().productQuantityInput().value = producto.cantidad;
            view.modals().productQuantityEditModal().show();
        },

        updateTotals: function (data = {}) {
            view.cart().cartTotalLabel().textContent = Money.roundFixed(data.doc.total);
            view.cart().orderItemsNumberLabel().textContent = Money.roundFixed(data.count);
            view.cart().orderSubtotalLabel().textContent = Money.roundFixed(data.doc.netosindto);
            view.cart().orderDiscountAmountInput().value = data.doc.dtopor1 || 0;
            view.cart().orderDiscountAmountLabel().textContent = Money.roundFixed(data.doc.neto);
            view.cart().orderNetoLabel().textContent = Money.roundFixed(data.getDiscountAmount());
            view.cart().orderTaxesLabel().textContent = Money.roundFixed(data.doc.totaliva);
            view.cart().orderTotalLabel().textContent = Money.roundFixed(data.doc.total);
        }
    };
}

export const checkoutView = () => {
    return {
        enableConfirmButton: function (enable = true) {
            view.checkout().confirmOrderButton().disabled = !enable;
        },

        getCurrentPaymentData: function ({code, description}) {
            return {
                amount: view.checkout().paymentAmountInput().value,
                method: code,
                description: description
            };
        },

        getCurrentPaymentInput: function () {
            return parseFloat(view.checkout().paymentAmountInput().value) || 0;
        },

        showPaymentModal: function (data = {}) {
            view.checkout().paymentAmountInput().dataset.method = data.code;
            view.checkout().paymentAmountInput().dataset.description = data.description;
            view.modals().paymentModal().show();
        },

        updatePaymentList: function (data = []) {
            view.templates().renderPaymentList(data);
        },

        updateTotals: function (data = {}) {
            view.checkout().changeAmountLabel().textContent = data.change;
            view.checkout().changeAmountLabel().textContent = data.getPaymentsTotal();
            view.checkout().totalAmountLabel().textContent = data.total;
        }
    }
}

export const mainView = () => {
    return {
        'cashMovmentButton': getElement('cashMovmentSaveButton'),
        'cashMovmentForm': getElement('cashMovmentForm'),
        'closeSessionButton': getElement('closeSessionButton'),
        'closeSessionForm': getElement('closeSessionForm'),
        'customerNameLabel': getElement('customerNameLabel'),
        'customerSaveButton': getElement('newCustomerSaveButton'),
        'customerSearchBox': getElement('customerSearchBox'),
        'documentNamelLabel': getElement('documentTypeLabel'),
        'main': getElement('productMainView'),
        'mainContent': getElement('mainContent'),
        'productSearchBox': getElement('productSearchBox'),

        updateCustomer: function (name = '') {
            this.customerNameLabel.textContent = name;
        },

        updateCustomerListView: function (data = []) {
            view.templates().renderCustomerList({items: data});
        },

        updateDocument: function (name = '') {
            this.documentNamelLabel.textContent = name;
        },

        updateHoldOrdersList: function (data = []) {
            view.templates().renderPausedOrderList({items: data});
        },

        updateLastOrdersList: function (data = []) {
            view.templates().renderLastOrderList({items: data});
        },

        updateProductFamilyList: function (data = []) {
            view.templates().renderProductFamilyList({items: data});
        },

        updateProductImageList: function (data = []) {
            view.templates().renderProductImageList({items: data});
        },

        updateProductSearchResult: function (data = []) {
            view.templates().renderProductSearchList({items: data});
        },

        updateProductStockDetail: function (data = []) {
            view.templates().renderProductStockList({items: data});
        },

        updateViewFields: function ({doc}) {
            for (let i = 0; i < fields.length; i++) {
                setDocumentFieldFromData(doc, fields[i])
            }
        }
    }
}

const fields = document.querySelectorAll('[data-document-field]');

const setDocumentFieldFromData = (data = {}, element) => {
    const fieldName = element.getAttribute('data-document-field');

    switch (element.type) {
        case 'text':
        case 'textarea':
            element.value = data[fieldName];
            break
        case 'number':
        case 'decimal':
            element.value = Money.roundFixed(data[fieldName]);
            break;
        case'checkbox':
            element.checked = data[fieldName] === true || data[fieldName] === "true";
            break;
        default:
            element.textContent = data[fieldName];
    }
}

/**
 * @param {HTMLElement} element
 */
export function toggleCollapse(element) {
    const target = getElement(element.dataset.target);
    const elementOntoggle = getElement(element.dataset.ontoggle);

    target.classList.toggle('hidden');

    if (elementOntoggle) {
        elementOntoggle.classList.toggle('hidden');
    }
}

/**
 * @param {HTMLElement} element
 */
export function toggleModal(element) {
    if (!element) return;

    element.classList.toggle("flex");

    if (element.classList.toggle("hidden")) {
        view.modals().backdrop().hide();
        return;
    }

    view.modals().backdrop().show();
}

/**
 * @param {HTMLElement} element
 */
export function toggle(element) {
    let target = getElement(element.dataset.target);

    if (!target) return;

    target.classList.toggle('hidden');

    if (element.dataset.ontoggle) {
        getElement(element.dataset.ontoggle).classList.toggle('hidden');
    }
}

/**
 * @param {EventTarget} event
 */
export function toggleEventHandler(event) {
    const element = getElement(event.dataset.target);
    switch (event.dataset.toggle) {
        case 'modal':
            toggleModal(element);
            break;
        case 'collapse':
            toggleCollapse(event);
            break;
        default:
            toggle(event);
    }
}

document.addEventListener('click', function (event) {
    if (event.target.attributes.getNamedItem('data-toggle')) {
        toggleEventHandler(event.target);
        event.stopPropagation();
    }
}, false);

/*window.addEventListener("click", function (event) {
    let menu = getElement('navbarMenu');

    /!*if (!menu.contains(event.target) && !menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    }*!/
})*/

