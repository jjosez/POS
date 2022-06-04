/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import {alertsContainer, updateAlertList} from "./AppUI.js";

export const settings = {
    url: 'POS'
}

/**
 * Send request to controller url
 * @param {FormData} data
 */
export function postRequest(data) {
    const init = {
        method: 'POST',
        body: data
    };

    return fetch(settings.url, init)
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
        updateAlertList(response);
        alert('respuesta');
    }

    dismissAlert();
}

/**
 * Close all alerts by timeout
 */
export function dismissAlert() {
    if (alertsContainer.firstElementChild) {
        setTimeout(function () {
            if (alertsContainer.firstChild) {
                alertsContainer.removeChild(alertsContainer.firstChild);
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
