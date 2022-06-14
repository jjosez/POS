/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import {getElement} from "./Core.js";
import * as Money from "./Money.js";

export const alertView = () => {
    return  {
        'container': getElement("alert-container"),
        'listTemplate': getTemplate('message-template'),

        updateAlertListView: function (data) {
            this.container.innerHTML = this.listTemplate(data, Eta.config);
        }
    }
}

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
        'holdButton': getElement('orderHoldButton'),


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

export const checkoutView = () => {
    return {
        'listView': getElement('paymentList'),
        'listTemplate': getTemplate('paymentListTemplate'),
        'confirmButton': getElement('orderSaveButton'),
        'change': getElement('checkoutChange'),
        'tendered': getElement('checkoutTotalTendered'),
        'paymentModal': getElement('paymentModal'),
        'paymentModalButton': document.querySelectorAll('.payment-modal-btn'),
        'paymentAmounButton': document.querySelectorAll('.payment-add-btn'),
        'paymentApplyButton': getElement('paymentApplyButton'),
        'paymentInput': getElement('paymentApplyInput'),

        showPaymentModal: function (element) {
            this.paymentInput.dataset.method = element.dataset.code;
            this.paymentInput.dataset.description = element.dataset.description;
            toggleModal(this.paymentModal);
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

export const mainView = () => {
    return {
        'main': getElement('productMainView'),
        'customerNameLabel': getElement('customerNameLabel'),
        'customerSearchBox': getElement('customerSearchBox'),
        'customerSearchModal': getElement('customerSearchModal'),
        'customerSaveButton': getElement('newCustomerSaveButton'),
        'customerListView': getElement('customerSearchResult'),
        'closeSessionButton': getElement('closeSessionButton'),
        'closeSessionForm': getElement('closeSessionForm'),
        'documentTypeModal': getElement('documentTypeModal'),
        'documentNamelLabel': getElement('documentTypeLabel'),
        'documentTypeListView': getElement('documentTypeList'),
        'customerListTemplate': getTemplate('customerListTemplate'),
        'holdOrdersModal': getElement('holdOrdersModal'),
        'holdOrdersList': getElement('pausedOrdersList'),
        'holdOrdersListTemplate': getTemplate('paused-orders-template'),
        'productSearchBox': getElement('productSearchBox'),
        'productListView': getElement('productSearchResult'),
        'productoListTemplate': getTemplate('productListTemplate'),

        toggleMainView: function () {
            this.main.classList.toggle('hidden');
        },

        toggleHoldOrdersModal: function () {
            toggleModal(this.holdOrdersModal);
        },

        updateCustomer: function (name = '') {
            this.customerNameLabel.textContent = name;
            toggleModal(this.customerSearchModal);
        },

        updateCustomerListView: function (data = []) {
            this.customerListView.innerHTML = this.customerListTemplate({items: data}, Eta.config);
        },

        updateDocument: function (name = '') {
            this.documentNamelLabel.textContent = name;
            toggleModal(this.documentTypeModal);
        },

        updateHoldOrdersList: function (data = []) {
            this.holdOrdersList.innerHTML = this.holdOrdersListTemplate({items: data}, Eta.config);
        },

        updateProductListView: function (data = []) {
            this.productListView.innerHTML = this.productoListTemplate({items: data}, Eta.config);
        }
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
    element.classList.toggle("flex");

    if (element.classList.toggle("hidden")) {
        document.querySelector('.modal-backdrop').remove();
    } else {
        const backdrop = document.createElement('div');
        backdrop.classList.add('modal-backdrop');
        document.querySelector('body').append(backdrop);
    }
}

/**
 * @param {HTMLElement} element
 */
export function toggle(element) {
    let target = getElement(element.dataset.target);

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

