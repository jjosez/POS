/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
export function deleteOrderRequest(code) {
    const data = new FormData();

    data.set('action', 'delete-order-on-hold');
    data.set('code', code);

    return basePostRequest(data);
}

export function holdOrder(lines, form) {
    if (lines.length <= 0) {
        return false;
    }

    const elements = form.elements;

    elements.action.value = 'hold-order';
    elements.lines.value = JSON.stringify(lines);

    form.submit();
}

export function recalculate(order, form) {
    const data = new FormData(form);

    data.set('action',"recalculate-order");
    data.set('lines', JSON.stringify(order.lines));

    return basePostRequest(data);
}

export function resumeOrder(code) {
    const data = new FormData();

    data.set('action', 'resume-order');
    data.set('code', code);

    return basePostRequest(data);
}

export function saveOrder(order, payments, form) {
    const elements = form.elements;

    elements.action.value = 'save-order';
    elements.lines.value = JSON.stringify(order.lines);
    elements.payments.value = JSON.stringify(payments);

    //form.setAttribute('target', '_blank');

    form.submit();
}

/* Guardar sin imprimir */
export function saveNoPrintOrder(order, payments, form) {
    const elements = form.elements;

    elements.action.value = 'save-noprint-order';
    elements.lines.value = JSON.stringify(order.lines);
    elements.payments.value = JSON.stringify(payments);

    //form.setAttribute('target', '_blank');

    form.submit();
}

export function saveNewCustomer(taxID, name) {
    const data = new FormData();

    data.set('action', 'save-new-customer');
    data.set('taxID', taxID);
    data.set('name', name);

    return basePostRequest(data);
}

export function searchBarcode(query) {
    return baseSearchRequest(query, 'search-barcode');
}

export function searchCustomer(query) {
    return baseSearchRequest(query, 'search-customer');
}

export function searchProduct(query) {
    return baseSearchRequest(query, 'search-product');
}

export function roundDecimals(amount, roundRate = 1000) {
    return Math.round(amount * roundRate) / roundRate;
}

export function getElement(id) {
    return document.getElementById(id);
}

export const token = () => getElement('token');

export const cartTemplateSource = () => getElement('cart-template').innerHTML;
export const customerTemplateSource = () => getElement('customer-template').innerHTML;
export const productTemplateSource = () => getElement('product-template').innerHTML;

function basePostRequest(data) {
    const options = {
        method: 'POST',
        body: data
    };

    return fetch(settings.url, options).then(response => {
        if (response.ok) {
            return response.json();
        }
    });
}

function baseSearchRequest(query, action) {
    const data = new FormData();

    data.set('action', action);
    data.set('query', query);

    return basePostRequest(data);
}

export const settings = getElement('app-settings').dataset;
