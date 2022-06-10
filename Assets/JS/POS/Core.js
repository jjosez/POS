/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import {alertView} from "./UI.js";

/**
 * Send request to controller url
 * @param {FormData} data
 */
export function postRequest(data) {
    const init = {
        method: 'POST',
        body: data
    };

    return fetch('POS', init)
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Bad response');
        }).catch(err => {
            throw new Error(err);
        }).then(response => {
            showAlert(response);
            return response;
        });
}

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
export function searchBarcode(query) {
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
export function showAlert(response) {
    if (null != response.messages) {
        alertView().updateAlertListView(response);
    }

    dismissAlert();
}

/**
 * Close all alerts by timeout
 */
export function dismissAlert() {
    if (alertView().container.firstElementChild) {
        setTimeout(function () {
            if (alertView().container.firstChild) {
                alertView().container.removeChild(alertView().container.firstChild);
            }
            dismissAlert();
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
