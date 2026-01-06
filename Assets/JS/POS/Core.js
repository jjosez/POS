/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

import templates from "./views/TemplateManger.js";

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
        console.log(e.message);

        return {
            error: true,
            message: e.message,
            messages: [{type: "error", message: e.message}]
        };
    }
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
        console.log("Ocurrio un error.", e.message);

        return {
            error: true,
            message: e.message,
            messages: [{type: "error", message: e.message}]
        };
    }
}

export async function printerServerRequest({print_job_id, print_enabled}) {
    if (!print_enabled) {
        return null;
    }

    let url = 'http://127.0.0.1:8089';

    if (print_job_id != null && print_job_id !== '') {
        let params = new URLSearchParams({documento: print_job_id});
        url += '?' + params.toString();
    }

    try {
        const response = await fetch(url, {
            mode: 'cors', method: 'GET'
        });

        if (!response.ok) {
            throw new Error(`❌ HTTP error: ${response.status}`);
        }

        return response;
    } catch (error) {
        console.warn('❌ Error al conectar al servidor de impresión:', error);
    }
}

/**
 * @param {string} query
 * @param filters
 */
export function searchProduct(query = '', filters = {}) {
    return searchRequest('product:search', query, filters);
}

/**
 * @param {string} code
 * @param {string} madre
 */
export function getProductFamilyChild(code, madre) {
    const data = new FormData();

    data.set('action', 'family:filter:set');
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
export function showMessages(response) {
    if (null == response.messages) return;

    templates.render('messageListTemplate', response, 'messageListTemplateView');

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

    const searchParams = new URLSearchParams(actionParams).toString();
    const url = searchParams
        ? urlBase + (urlBase.includes('?') ? '&' : '?') + searchParams
        : urlBase;

    window.open(url, target);
}
