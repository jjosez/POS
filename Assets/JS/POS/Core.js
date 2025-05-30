/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

import Templates from "./components/Templates.js";

export function reloadApp() {
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    window.location = window.location.href;
}

/**
 * Short for document.getElementById
 * @param {string} id
 */
export function getElement(id) {
    return document.getElementById(id);
}

export function isObjectEmpty(obj) {
    for (const i in obj) return false;

    return true;
}

/**
 * Send request to controller url
 * @param {FormData} data
 */
export async function postRequest(data) {
    try {
        const response = await fetch('POS', {
            method: 'POST',
            body: data
        });

        if (!response.ok) requestErrorHandler(response.status);

        let result = await response.json();
        showMessages(result);

        return result;
    } catch (e) {
        /*Promise.resolve({
            messages: [{type: "warning", message: e.name + e.message}]
        }).then((messages) => showMessages(messages));*/

        console.log("Ocurrio un error.", e.message);
    }

    return Promise.resolve({});
}

/**
 * Send request to controller url
 * @param {FormData} data
 */
export async function postRequestCore(data) {
    try {
        const response = await fetch('POS', {
            method: 'POST',
            body: data
        });

        if (!response.ok) requestErrorHandler(response.status);

        return response;
    } catch (e) {
        /*Promise.resolve({
            messages: [{type: "warning", message: e.name + e.message}]
        }).then((messages) => showMessages(messages));*/

        console.log("Ocurrio un error.", e.message);
    }

    return Promise.resolve({});
}

export function printClosingTicket() {
    const data = new FormData();

    data.set('action', 'print-closing-ticket');

    return postRequest(data);
}

export async function printerServerRequest({print_job_id}) {
    if (print_job_id == null) return;

    let params = new URLSearchParams({"documento": print_job_id});

    try {
        await fetch('http://127.0.0.1:8089?' + params, {
            mode: 'no-cors', method: 'GET'
        });
    } catch (error) {
        console.log(error);
    }
}

/**
 * @param {string} taxID
 * @param {string} name
 */
export function saveNewCustomer(taxID, name) {
    const data = new FormData();

    data.set('action', 'save-new-customer');
    data.set('taxID', taxID);
    data.set('name', name);

    return postRequest(data);
}

/**
 * @param {string} query
 */
export function searchBarcode(query = '') {
    return searchRequest('search-barcode', query);
}

/**
 * @param {string} query
 */
export function searchCustomer(query = '') {
    return searchRequest('search-customer', query);
}

/**
 * @param {string} query
 * @param filters
 */
export function searchProduct(query = '', filters = {}) {
    return searchRequest('search-product', query, filters);
}

/**
 * @param {string} code
 */
export function getProductStock(code) {
    return searchRequest('get-product-stock', code);
}

/**
 * @param {string} id
 * @param {string} code
 */
export function getProductImages(id, code) {
    const data = new FormData();

    data.set('action', 'get-product-images');

    data.set('id', id);
    data.set('code', code);

    return postRequest(data);
}

/**
 * @param {string} code
 * @param {string} madre
 */
export function getProductFamilyChild(code, madre) {
    const data = new FormData();

    data.set('action', 'set-family-filter');
    data.set('code', code);
    data.set('madre', madre);

    return postRequest(data);
}

export function isAndroidUserAgent() {
    let userAgent = navigator.userAgent.toLowerCase();

    return userAgent.indexOf("android") > -1; //&& ua.indexOf("mobile");
}

/**
 * @param {string} action
 * @param {string} query
 * @param filters
 */
export function searchRequest(action, query, filters = {}) {
    const data = new FormData();

    data.set('action', action);
    data.set('query', query);
    data.set('terminal', AppSettings.terminal);
    data.set('filters', JSON.stringify(filters))

    return postRequest(data);
}

/**
 * Show alerts in response
 * @param {Promise} response
 */
function showMessages(response) {
    if (null == response.messages) return;

    Templates.renderMessageListView(response);
    cleanMessages();
}

/**
 * Close all messages after 1000ms timeout
 */
function cleanMessages() {
    let container = getElement("messageListTemplateView");

    if (null === container.firstChild) return;

    setTimeout(() => {
        const child = container.firstChild;

        if (child && child.nodeType) {
            container.removeChild(container.firstChild);
        }

        cleanMessages();
    }, 1000);
}

/**
 * Show console error message *
 * @param {int} error
 */
function requestErrorHandler(error) {
    throw new Error(`An error has occured: ${error}`);
}

export function parseParams(paramsJson) {
    if (!paramsJson || typeof paramsJson !== 'string') {
        return {};
    }

    try {
        const parsed = JSON.parse(paramsJson);
        return (parsed && typeof parsed === 'object') ? parsed : {};
    } catch (err) {
        console.warn('invalid data-params:', paramsJson, err);
        return {};
    }
}

export function openLinkAction(controllerUrl, actionParams, target = '_blank') {
    const urlBase = controllerUrl;
    const params = parseParams(actionParams);

    /*if (!urlBase || !/^https?:\/\//.test(urlBase)) {
        console.warn("URL inválida o ausente en data-url:", urlBase);
        return;
    }*/

    const searchParams = new URLSearchParams(params).toString();
    const url = searchParams
        ? urlBase + (urlBase.includes('?') ? '&' : '?') + searchParams
        : urlBase;

    window.open(url, target);
}
