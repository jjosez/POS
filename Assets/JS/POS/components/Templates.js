import {Eta} from "../../vendor/eta/browser.module.js?v=3.5.0"

/* global eta */
const templateEngine = new Eta();

let instance;

const templates = {
    cartEditTemplate: document.getElementById('cartEditTemplate').innerHTML,
    cartListTemplate: document.getElementById('cartListTemplate').innerHTML,
    customerListTemplate: document.getElementById('customerListTemplate').innerHTML,
    lastOrdersListTemplate: document.getElementById('lastOrdersListTemplate').innerHTML,
    messageListTemplate: document.getElementById('messageListTemplate').innerHTML,
    draftOrderListTemplate: document.getElementById('draftOrderListTemplate').innerHTML,
    paymentListTemplate: document.getElementById('paymentListTemplate').innerHTML,
    printOrderActionTemplate: document.getElementById('printOrderActionTemplate').innerHTML,
    printDraftActionTemplate: document.getElementById('printDraftActionTemplate').innerHTML,
    productFamilyListTemplate: document.getElementById('productFamilyListTemplate').innerHTML,
    productImageListTemplate: document.getElementById('productImageListTemplate').innerHTML,
    productSearchListTemplate: document.getElementById('productSearchListTemplate').innerHTML,
    productStockListTemplate: document.getElementById('productStockListTemplate').innerHTML,
}

class Templates {
    constructor() {
        if (instance) throw new Error("New instance cannot be created!!");
        instance = this;

        // Create a cache object for the views
        this.viewCache = {};
    }

    /**
     * Renders a template into the specified view container.
     *
     * @param {string} name Element container name.
     * @param {*} data Data to render.
     * @param {HTMLElement} [viewElement] Optional - the view container to render into.
     */
    render = (name, data, viewElement) => {
        // If the view is already cached, use the cached reference
        let view = this.viewCache[name];

        // If the view is not cached, fetch it from the DOM
        if (!view) {
            view = viewElement || document.getElementById(name + 'View');
            if (view) {
                this.viewCache[name] = view;
            } else {
                console.error(`View container for ${name} not found.`);
                return;
            }
        }

        // Render the content into the view
        view.innerHTML = templateEngine.renderString(templates[name], data);
    };

    renderMessageListView = (data) => this.render('messageListTemplate', data);
    renderCartEditView = (data) => this.render('cartEditTemplate', data);
    renderCartListView = (data) => this.render('cartListTemplate', data);
    renderCustomerListView = (data) => this.render('customerListTemplate', data);
    renderLastOrderListView = (data) => this.render('lastOrdersListTemplate', data);
    renderPaymentListView = (data) => this.render('paymentListTemplate', data);
    renderDraftOrderListView = (data) => this.render('draftOrderListTemplate', data);
    renderContextActionView = (data) => this.render('contextActionTemplate', data);
    renderPrintContextActionView = (template, data) => this.render(template, data, document.getElementById('contextActionTemplateView'));
    renderProductFamilyListView = (data) => this.render('productFamilyListTemplate', data);
    renderProductImageListView = (data) => this.render('productImageListTemplate', data);
    renderProductSearchListView = (data) => this.render('productSearchListTemplate', data);
    renderProductStockListView = (data) => this.render('productStockListTemplate', data);
}

const templatesInstance = Object.freeze(new Templates());

export default templatesInstance;
