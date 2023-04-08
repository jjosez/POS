/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import {getElement} from "./Core.js";
import * as Money from "./Money.js";

export const alertView = () => {
    return {
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
        'editForm': getElement('productEditForm'),
        'editView': getElement('cartEditView'),
        'listView': getElement('cartListView'),
        'editTemplate': getTemplate('cartEditTemplate'),
        'listTemplate': getTemplate('cartListTemplate'),
        'itemsNumber': getElement('orderItemsNumber'),
        'subtotal': getElement('orderSubtotal'),
        'discountPercent': getElement('orderDiscount'),
        'discountAmount': getElement('orderDiscountAmount'),
        'observations': getElement('orderObservations'),
        'taxes': getElement('orderTaxes'),
        'totalNet': getElement('orderTotalNet'),
        'cartTotal': getElement('cartTotal'),
        'total': getElement('orderTotal'),
        'holdButton': getElement('orderHoldButton'),
        'productEditModal': getElement('productEditModal'),
        'productQuantityEditModal': getElement('productQuantityEditModal'),
        'productQuantityInput': getElement('productQuantityInput'),

        showEditView: function () {
            toggleModal(this.productEditModal);
        },

        showQuantityEditView: function (producto) {
            this.productQuantityInput.dataset.index = producto.index;
            this.productQuantityInput.value = producto.cantidad;
            toggleModal(this.productQuantityEditModal);
        },

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
            this.observations.value = data.doc.observaciones;
            this.cartTotal.textContent = Money.roundFixed(data.doc.total);
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
        'change': getElement('checkoutChangeAmount'),
        'tendered': getElement('checkoutTenderedAmount'),
        'total': getElement('checkoutTotal'),
        'paymentModal': getElement('paymentModal'),
        'paymentModalButton': document.querySelectorAll('.payment-modal-btn'),
        'paymentAmounButton': document.querySelectorAll('.payment-add-btn'),
        'paymentApplyButton': getElement('paymentApplyButton'),
        'paymentInput': getElement('paymentApplyInput'),

        enableConfirmButton: function (enable = true) {
            this.confirmButton.disabled = !enable;
        },

        getCurrentPaymentData: function ({code, description}) {
            return {
                amount: this.paymentInput.value,
                method: code,
                description: description
            };
        },

        getCurrentPaymentInput: function () {
            return parseFloat(this.paymentInput.value) || 0;
        },

        showPaymentModal: function (data = {}) {
            this.paymentInput.dataset.method = data.code;
            this.paymentInput.dataset.description = data.description;
            toggleModal(this.paymentModal);
        },

        updatePaymentList: function (data = []) {
            this.listView.innerHTML = this.listTemplate(data, Eta.config);
        },

        updateTotals: function (data = {}) {
            this.change.textContent = data.change;
            this.tendered.textContent = data.getPaymentsTotal();
            this.total.textContent = data.total;
        }
    }
}

export const mainView = () => {
    return {
        'main': getElement('productMainView'),
        'cashMovmentForm': getElement('cashMovmentForm'),
        'cashMovmentButton': getElement('cashMovmentSaveButton'),
        'customerNameLabel': getElement('customerNameLabel'),
        'customerSearchBox': getElement('customerSearchBox'),
        'customerSearchModal': getElement('customerSearchModal'),
        'customerSaveButton': getElement('newCustomerSaveButton'),
        'customerListView': getElement('customerSearchResult'),
        'customerListTemplate': getTemplate('customerListTemplate'),
        'closeSessionButton': getElement('closeSessionButton'),
        'closeSessionForm': getElement('closeSessionForm'),
        'closeSessionModal': getElement('closeSessionModal'),
        'documentTypeModal': getElement('documentTypeModal'),
        'documentNamelLabel': getElement('documentTypeLabel'),
        'documentTypeListView': getElement('documentTypeList'),
        'holdOrdersList': getElement('pausedOrdersList'),
        'holdOrdersListTemplate': getTemplate('pausedOrdersListTemplate'),
        'holdOrdersModal': getElement('holdOrdersModal'),
        'lastOrdersList': getElement('lastOrdersList'),
        'lastOrdersListTemplate': getTemplate('lastOrdersListTemplate'),
        'lastOrdersModal': getElement('lastOrdersModal'),
        'productImagesModal': getElement('productImagesModal'),
        'productSearchBox': getElement('productSearchBox'),
        'productListView': getElement('productSearchResult'),
        'productListTemplate': getTemplate('productListTemplate'),
        'productImageListView': getElement('productImageListView'),
        'productImageListTemplate': getTemplate('productImageListTemplate'),
        'stockDetailModal': getElement('stockDetailModal'),
        'stockDetailList': getElement('stockDetailList'),
        'stockDetailListTemplate': getTemplate('stockDetailListTemplate'),
        'familyList': getElement('familyList'),
        'familyListTemplate': getTemplate('familyListTemplate'),

        toggleMainView: function () {
            this.main.classList.toggle('hidden');
        },

        toggleCloseSessionModal: function () {
            toggleModal(this.closeSessionModal);
        },

        toggleCustomerListModal: function () {
            toggleModal(this.customerSearchModal);
        },

        toggleDoctypeListModal: function () {
            toggleModal(this.documentTypeModal);
        },

        toggleHoldOrdersModal: function () {
            toggleModal(this.holdOrdersModal);
        },

        toggleLastOrdersModal: function () {
            toggleModal(this.lastOrdersModal);
        },

        toggleStockDetailModal: function () {
            toggleModal(this.stockDetailModal);
        },

        toggleProductImageModal: function () {
            toggleModal(this.productImagesModal);
        },

        updateCustomer: function (name = '') {
            this.customerNameLabel.textContent = name;
        },

        updateCustomerListView: function (data = []) {
            this.customerListView.innerHTML = this.customerListTemplate({items: data}, Eta.config);
        },

        updateDocument: function (name = '') {
            this.documentNamelLabel.textContent = name;
        },

        updateHoldOrdersList: function (data = []) {
            this.holdOrdersList.innerHTML = this.holdOrdersListTemplate({items: data}, Eta.config);
        },

        /*updateProductListView: async function (data = []) {
            this.productListView.innerHTML = await Eta.renderAsync(
                this.productoListTemplateHtml, {items: data}
            )
        },*/

        updateProductListView: function (data = []) {
            this.productListView.innerHTML = this.productListTemplate({items: data}, Eta.config);
        },

        updateProductImageListView: function (data = []) {
            this.productImageListView.innerHTML = this.productImageListTemplate({items: data}, Eta.config);
        },

        updateLastOrdersListView: function (data = []) {
            this.lastOrdersList.innerHTML = this.lastOrdersListTemplate({items: data}, Eta.config);
        },

        updateStockListView: function (data = []) {
            this.stockDetailList.innerHTML = this.stockDetailListTemplate({items: data}, Eta.config);
        },

        updateFamilyListView: function (data = []) {
            this.familyList.innerHTML = this.familyListTemplate({items: data}, Eta.config);
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
    if (!element) return;

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

window.addEventListener("click", function (event) {
    let menu = getElement('navbarMenu');

    if (!menu.contains(event.target) && !menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    }
})

