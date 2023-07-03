import {getElement} from "../Core.js";

/* global eta */
const templateEngine = new eta.Eta();
let instance;

const templates = {
    alertList: getElement('message-template').innerHTML,
    cartEdit: getElement('cartEditTemplate').innerHTML,
    cartList: getElement('cartListTemplate').innerHTML,
    customerList: getElement('customerListTemplate').innerHTML,
    lastOrdersList: getElement('lastOrdersListTemplate').innerHTML,
    paymentList: getElement('paymentListTemplate').innerHTML,
    pausedOrdersList: getElement('pausedOrdersListTemplate').innerHTML,
    productFamilyList: getElement('familyListTemplate').innerHTML,
    productImageList: getElement('productImageListTemplate').innerHTML,
    productSearchResult: getElement('productListTemplate').innerHTML,
    productStockList: getElement('stockDetailListTemplate').innerHTML,
}

const views = {
    alertList: getElement('alert-container'),
    cartList: getElement('cartListView'),
    cartEdit: getElement('productEditForm'),
    customerList: getElement('customerSearchResult'),
    lastOrdersList: getElement('lastOrdersList'),
    paymentList: getElement('paymentList'),
    pausedOrdersList: getElement('pausedOrdersList'),
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

    renderAlertList = (data) => this.render('alertList', data);
    renderCartEdit = (data) => this.render('cartEdit', data);
    renderCartList = (data) => this.render('cartList', data);
    renderCustomerList = (data) => this.render('customerList', data);
    renderLastOrderList = (data) => this.render('lastOrdersList', data);
    renderPaymentList = (data) => this.render('paymentList', data);
    renderPausedOrderList = (data) => this.render('pausedOrdersList', data);
    renderProductFamilyList = (data) => this.render('productFamilyList', data);
    renderProductImageList = (data) => this.render('productImageList', data);
    renderProductSearchList = (data) => this.render('productSearchResult', data);
    renderProductStockList = (data) => this.render('productStockList', data);
}

let templatesInstance = Object.freeze(new Templates());

export default templatesInstance;
