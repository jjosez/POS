/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

import Modals from "../components/Modals.js";
import templates from "../views/TemplateManger.js";
import * as Money from "../Money.js";

const viewElements = {
    customerNameLabel: document.getElementById('customerNameLabel'),
    customerSearchBox: document.getElementById('customerSearchBox'),
    cartTotalLabel: document.getElementById('cartTotal'),
    documentClassLabel: document.getElementById('documentClassLabel'),
    orderDiscountAmountLabel: document.getElementById('orderDiscountAmountLabel'),
    orderDiscountAmountInput: document.getElementById('orderDiscountAmountInput'),
    orderHoldButton: document.getElementById('orderHoldButton'),
    orderItemsNumberLabel: document.getElementById('orderItemsNumber'),
    orderNetoLabel: document.getElementById('orderTotalNet'),
    orderTaxesLabel: document.getElementById('orderTaxes'),
    orderTotalLabel: document.getElementById('orderTotal'),
    productQuantityInput: document.getElementById('productQuantityInput')
}

class CartView {
    cartTotalLabel = () => viewElements['cartTotalLabel'];
    customerSearchBox = () => viewElements['customerSearchBox'];
    orderDiscountAmountLabel = () => viewElements['orderDiscountAmountLabel'];
    orderDiscountAmountInput = () => viewElements['orderDiscountAmountInput'];
    orderHoldButton = () => viewElements['orderHoldButton'];
    orderItemsNumberLabel = () => viewElements['orderItemsNumberLabel'];
    orderNetoLabel = () => viewElements['orderNetoLabel'];
    productQuantityInput = () => viewElements['productQuantityInput'];

    showProductEditModal = (product = {}) => {
        this.updateCartEditView(product);

        Modals.toggleModal('productEditModal');
    };

    showQuantityEditModal = ({index, cantidad}) => {
        this.productQuantityInput().dataset.index = index;
        this.productQuantityInput().value = cantidad;

        Modals.toggleModal('productQuantityEditModal');
    };

    updateCustomerNameLabel = (name = '') => {
        viewElements['customerNameLabel'].textContent = name;
    };

    updateDocumentClassLabel = (name = '') => {
        viewElements['documentClassLabel'].textContent = name;
    };

    updateCartEditView = (product = {}) => {
        templates.render('cartEditTemplate',{ product: product },'cartEditTemplateView')
    };

    updateTotals = (data = {}) => {
        this.cartTotalLabel().textContent = Money.roundFixed(data.doc.total);
        this.orderItemsNumberLabel().textContent = Money.roundFixed(data.count);
        this.orderDiscountAmountInput().value = data.doc.dtopor1 || 0;
        this.orderDiscountAmountLabel().textContent = Money.roundFixed(data.getDiscountAmount());
        this.orderNetoLabel().textContent = Money.roundFixed(data.doc.neto);

        templates.render('cartListTemplate', data, 'cartListTemplateView')
    };

    /*Modals*/
    toggleCustomerSearchModal = () => {
        Modals.toggleModal('customerSearchModal');
    };

    toggleDocumentClassSearchModal = () => {
        Modals.toggleModal('documentTypeModal');
    };
}

const cartViewInstance = () => Object.freeze(new CartView());
export default cartViewInstance();
