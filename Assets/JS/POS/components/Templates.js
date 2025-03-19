import {getElement} from "../Core.js";
import {Eta} from "../../vendor/eta/browser.module.js?v=3.5.0"

/* global eta */
const templateEngine = new Eta();

let instance;

const templates = {
    cartEdit: getElement('cartEditTemplate').innerHTML,
    cartList: getElement('cartListTemplate').innerHTML,
    customerList: getElement('customerListTemplate').innerHTML,
    lastOrdersList: getElement('lastOrdersListTemplate').innerHTML,
    messageList: getElement('message-template').innerHTML,
    pausedOrdersList: getElement('pausedOrdersListTemplate').innerHTML,
    paymentList: getElement('paymentListTemplate').innerHTML,
    printSelection: getElement('printSelectionTemplate').innerHTML,
    productFamilyList: getElement('familyListTemplate').innerHTML,
    productImageList: getElement('productImageListTemplate').innerHTML,
    productSearchResult: getElement('productListTemplate').innerHTML,
    productStockList: getElement('stockDetailListTemplate').innerHTML,
}

const views = {
    cartEdit: getElement('productEditForm'),
    cartList: getElement('cartListView'),
    customerList: getElement('customerSearchResult'),
    lastOrdersList: getElement('lastOrdersList'),
    messageList: getElement('alert-container'),
    pausedOrdersList: getElement('pausedOrdersList'),
    paymentList: getElement('paymentList'),
    printSelection: getElement('printSelectionView'),
    productFamilyList: getElement('familyList'),
    productImageList: getElement('productImageListView'),
    productSearchResult: getElement('productSearchResult'),
    productStockList: getElement('stockDetailList'),
}

class Templates {
    constructor() {
        if (instance) throw new Error("New instance cannot be created!!");

        instance = this;
    }

    render = (name, data) => {
        views[name].innerHTML = templateEngine.renderString(templates[name], data);
    };

    renderMessageList = (data) => this.render('messageList', data);
    renderCartEdit = (data) => this.render('cartEdit', data);
    renderCartList = (data) => this.render('cartList', data);
    renderCustomerList = (data) => this.render('customerList', data);
    renderLastOrderList = (data) => this.render('lastOrdersList', data);
    renderPaymentList = (data) => this.render('paymentList', data);
    renderPausedOrderList = (data) => this.render('pausedOrdersList', data);
    renderPrintSelection = (data) => this.render('printSelection', data);
    renderProductFamilyList = (data) => this.render('productFamilyList', data);
    renderProductImageList = (data) => this.render('productImageList', data);
    renderProductSearchList = (data) => this.render('productSearchResult', data);
    renderProductStockList = (data) => this.render('productStockList', data);
}

const templatesInstance = Object.freeze(new Templates());

export default templatesInstance;
