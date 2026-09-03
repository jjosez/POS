/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

import EventManager from "../core/EventManager.js";
import EventDispatcher from "../core/EventDispatcher.js";
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
        templates.render('product:filter:template', {filters: data}, 'product:filter:template:view');
        templates.render('product:filter:template', {filters: data}, 'product:filter:family:view');
    };

    updateFamilyNavigator = ({madre, children, breadcrumb}) => {
        templates.render('family:breadcrumb:template', {breadcrumb}, 'family:breadcrumb:template:view');
        templates.render('family:list:template', {children}, 'family:list:template:view');
    };

    updateProductSearchResult = (data = []) => {
        const mode = AppSettings.productsearch.templateDisplayMode === 'list' ? 'list' : 'grid';
        templates.render('product:search:template', {products: data, mode: mode}, 'product:search:list:view');
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
        templates.render('last:order:list:template', {orders: data}, 'last:order:list:view');
    }

    showPausedOrdersModal = (data) => {
        this.toggleDraftOrdersModal();

        data = Core.isObjectEmpty(data) ? {drafts: [], refunds: []} : data;
        templates.render('draft:order:list:template', data, 'draft:order:list:view')
    };

    showPrintDraftContextModal = data => {
        this.togglePrintSelectionModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('printDraftActionTemplate', {data: data}, 'contextActionTemplateView');
    };

    showPrintOrderContextModal = data => {
        this.togglePrintSelectionModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('printOrderContextActionTemplate', {data: data}, 'contextActionTemplateView');
    };

    showProductImagesModal = data => {
        this.toggleProductImagesModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('product:image:list:template', {images: data}, 'product:image:list:template:view');
    };

    showProductStockDetailModal = data => {
        this.toggleProductStockDetailModal();

        data = Core.isObjectEmpty(data) ? [] : data;
        templates.render('product:stock:list:template', {stocks: data}, 'product:stock:list:view');
    };

    showProductDetailModal = data => {
        if (!data?.product) return;

        templates.render('product:detail:template', data, 'product:detail:view');
        Modals.showModal('product:detail:modal');
    };

    showLoading = () => Modals.loadingModal().show();
    hideLoading = () => Modals.loadingModal().hide();
    toggleLoadingModal = () => Modals.toggleModal('loadingModal');

    toggleCloseSessionModal = () => Modals.toggleModal('session:close:modal');

    toggleLastOrdersModal = () => Modals.toggleModal('order:last:list:modal');

    toggleDraftOrdersModal = () => Modals.toggleModal('draftOrdersModal');

    toggleProductImagesModal = () => Modals.toggleModal('product:image:modal');

    toggleProductStockDetailModal = () => Modals.toggleModal('product:stock:modal');

    togglePrintSelectionModal = () => Modals.toggleModal('context:action:modal');

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

function handleViewPaneChange(el) {
    const cart = document.getElementById('cartPane');
    const products = document.getElementById('productsPane');
    const showCart = el.dataset.action === 'view:cart:pane';

    cart.classList.toggle('hidden', !showCart);
    cart.classList.toggle('flex', showCart);
    products.classList.toggle('hidden', showCart);
    products.classList.toggle('flex', !showCart);
}

const mainViewInstance = () => Object.freeze(new MainView());

EventManager.on('event:cart:updated', mainViewInstance().updateView);
EventManager.on('responseMessages', showMessages);

EventDispatcher.register('view:cart:pane', handleViewPaneChange.bind(this));
EventDispatcher.register('view:products:pane', handleViewPaneChange.bind(this));

export default mainViewInstance();
