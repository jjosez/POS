/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

import EventManager from "../core/EventManager.js";
import Modals from "../components/Modals.js";
import templates from "./TemplateManger.js";
import * as Core from "../Core.js";
import * as Money from "../Money.js";

const viewElements = {
    cashEntryForm: document.getElementById('cashEntryForm'),
    cashWithdrawForm: document.getElementById('cashWithdrawForm'),
    closeSessionForm: document.getElementById('closeSessionForm'),
    customerNameLabel: document.getElementById('customerNameLabel'),
    customerSearchBox: document.getElementById('customerSearchBox'),
    documentFieldList: document.querySelectorAll('[data-document-field]'),
    documentClassLabel: document.getElementById('documentClassLabel'),
    mainContent: document.getElementById('mainContent'),
    newCustomerSaveButton: document.getElementById('newCustomerSaveButton'),
    productSearchBox: document.getElementById('productSearchBox')
}

class MainView {
    cashEntryForm = () => viewElements['cashEntryForm'];
    cashWithdrawForm = () => viewElements['cashWithdrawForm'];
    customerNameLabel = () => viewElements['customerNameLabel'];
    customerSearchBox = () => viewElements['customerSearchBox'];
    closeSessionForm = () => viewElements['closeSessionForm'];
    productSearchBox = () => viewElements['productSearchBox'];
    newCustomerSaveButton = () => viewElements['newCustomerSaveButton'];
    updateCustomerListView = (data = []) => {
        templates.render(
            'customerListTemplate',
            {customers: data},
            'customerListTemplateView'
        );
    };

    updateProductFamilyList = (data = []) => {
        templates.render('productFilterListTemplate', {filters: data}, 'productFilterListTemplateView');
    };
    updateProductSearchResult = (data = []) => {
        templates.render('productSearchListTemplate', {products: data}, 'productSearchListTemplateView');
    };

    updateView({doc}) {
        const documentFields = viewElements['documentFieldList'];

        for (let i = 0; i < documentFields.length; i++) {
            updateDocumentFieldValue(doc, documentFields[i])
        }
    }

    showLastOrdersModal(data) {
        this.toggleLastOrdersModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('lastOrdersListTemplate', { orders:data }, 'lastOrdersListTemplateView');
    }

    showPausedOrdersModal = (data) => {
        this.toggleDraftOrdersModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('draftOrderListTemplate', {orders: data}, 'draftOrderListTemplateView')
    };

    showPrintDraftSelectionModal = data => {
        this.togglePrintSelectionModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('printDraftActionTemplate', {data: data}, 'contextActionTemplateView');
    };

    showPrintOrderSelectionModal = data => {
        this.togglePrintSelectionModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('printOrderActionTemplate', {data: data}, 'contextActionTemplateView');
    };

    showProductImagesModal = data => {
        this.toggleProductImagesModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('productImageListTemplate', {images: data}, 'productImageListTemplateView');
    };

    showProductStockDetailModal = data => {
        this.toggleProductStockDetailModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('productStockListTemplate', {stocks: data}, 'productStockListTemplateView');
    };

    toggleLoadingModal = () => Modals.toggleModal('loadingModal');

    toggleCloseSessionModal = () => Modals.toggleModal('closeSessionModal');

    toggleLastOrdersModal = () => Modals.toggleModal('lastOrdersModal');

    toggleDraftOrdersModal = () => Modals.toggleModal('draftOrdersModal');

    toggleProductImagesModal = () => Modals.toggleModal('productImagesModal');

    toggleProductStockDetailModal = () => Modals.toggleModal('stockDetailModal');

    togglePrintSelectionModal = () => Modals.toggleModal('contextActionModal');
}

const updateDocumentFieldValue = (data = {}, element) => {
    const field = element.getAttribute('data-document-field');
    const format = element.getAttribute('data-format');

    switch (element.type) {
        case 'text':
        case 'textarea':
            element.value = data[field] ?? '';
            break
        case 'number':
        case 'decimal':
            element.value = Money.roundFixed(data[field]);
            break;
        case'checkbox':
            element.checked = data[field] === true || data[field] === "true";
            break;
        default:
            element.textContent = (format === 'number') ? Money.roundFixed(data[field]) : data[field];
    }
}

const updateProductFilter = async searchFilter => {
    let query = mainViewInstance().productSearchBox().value ?? '';
    let result = await Core.searchProduct(query, searchFilter);

    mainViewInstance().updateProductFamilyList(searchFilter.families);
    mainViewInstance().updateProductSearchResult(result);
};

const updateView = data => {
    mainViewInstance().updateView(data);
};

/**
 * Show alerts in response
 * @param {{messages}} messages
 */
function showMessages(messages) {
    if (null == messages) return;

    templates.render('messageListTemplate', {messages: messages}, 'messageListTemplateView');

    cleanMessages();
}

/**
 * Close all messages after 1000ms timeout
 */
function cleanMessages() {
    let container = Core.getElement("messageListTemplateView");

    if (null === container.firstChild) return;

    setTimeout(() => {
        const child = container.firstChild;

        if (child && child.nodeType) {
            container.removeChild(container.firstChild);
        }

        cleanMessages();
    }, 1000);
}

const mainViewInstance = () => Object.freeze(new MainView());

EventManager.on('onCartUpdate', mainViewInstance().updateView);
EventManager.on('onProductFilterChange', updateProductFilter);
EventManager.on('responseMessages', showMessages);

export default mainViewInstance();
