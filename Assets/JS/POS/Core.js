/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import {alertView} from "./UI.js";

/**
 * Send request to controller url
 * @param {FormData} data
 */
export async function postRequest(data) {
    const response = await fetch('POS', {
        method: 'POST',
        body: data
    });

    if (!response.ok) requestErrorHandler(response.status);

    let result = await response.json();
    await showMessage(result);

    return result;
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
 */
export function searchProduct(query = '') {
    return searchRequest('search-product', query);
}

/**
 * @param {string} action
 * @param {string} query
 */
export function searchRequest(action, query) {
    const data = new FormData();

    data.set('action', action);
    data.set('query', query);

    return postRequest(data);
}

/**
 * Show alerts in response
 * @param {Promise} response
 */
export function showMessage(response) {
    if (null != response.messages) {
        alertView().updateAlertListView(response);
    }

    autoCloseMessage();
}

/**
 * Close all messages by timeout
 */
export function autoCloseMessage() {
    let container = alertView().container;

    if (container.firstElementChild) {
        setTimeout(function () {
            if (container.firstChild) {
                container.removeChild(container.firstChild);
            }
            autoCloseMessage();
        }, 1800);
    } else {
        clearTimeout();
    }
}

/**
 * Short for document.getElementById *
 * @param {string} id
 */
export function getElement(id) {
    return document.getElementById(id);
}

/**
 * Show console error message *
 * @param {int} error
 */
function requestErrorHandler(error) {
    throw new Error(`An error has occured: ${error}`);
}
