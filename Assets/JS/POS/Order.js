import {isAndroidUserAgent, postRequest, postRequestCore} from "./Core.js";

/**
 * @param {string} code
 */
export function deleteHoldRequest(code) {
    const data = new FormData();

    data.set('action', 'delete-order-on-hold');
    data.set('code', code);

    return postRequest(data);
}

export function getLastOrders() {
    const data = new FormData();

    data.set('action', 'get-last-orders');

    return postRequest(data);
}

export function getOnHoldRequest() {
    const data = new FormData();

    data.set('action', 'get-orders-on-hold');

    return postRequest(data);
}

export function holdRequest({doc, lines, token}) {
    const data = getFormData(doc);

    data.set('token', token);
    data.set('action', 'hold-order');
    data.set('lines', JSON.stringify(lines));

    return postRequest(data);
}

export function recalculateRequest({doc, lines}) {
    const data = getFormData(doc);

    data.set('action', "recalculate-order");
    data.set('lines', JSON.stringify(lines));

    return postRequest(data);
}

export function resumeRequest(code) {
    const data = new FormData();

    data.set('action', 'resume-order');
    data.set('code', code);

    return postRequest(data);
}

export function saveRequest({doc, lines, token}, payments) {
    const data = getFormData(doc);

    data.set('token', token);
    data.set('action', 'save-order');
    data.set('lines', JSON.stringify(lines));
    data.set('payments', JSON.stringify(payments));

    return postRequest(data);
}

export async function printRequest(code) {
    const data = new FormData();
    data.set('code', code);

    if (isAndroidUserAgent()) {
        data.set('action', 'print-mobile-ticket');
        return await printOnAndroid(data);
    }

    data.set('action', 'print-desktop-ticket');
    return await printOnDesktop(data);
}

async function printOnDesktop(data) {
    return await postRequest(data);
}

async function printOnAndroid(data) {
    /*var S = "#Intent;scheme=rawbt;";
    var P = "package=ru.a402d.rawbtprinter;end;";

    var textEncoded = encodeURI(result);*/
    let response = await postRequestCore(data);

    try {
        window.location.href = await response.text();
    } catch (e) {
        alert(e);
    }
}


/**
 * @param {string} code
 */
export async function printPausedOrderRequest(code) {
    const data = new FormData();

    data.set('code', code);

    if (isAndroidUserAgent()) {
        data.set('action', 'print-mobile-paused-ticket');
        return await printOnAndroid(data);
    }

    data.set('action', 'print-paused-order');
    return await printOnDesktop(data);
}

function getFormData(obj = {}) {
    const data = new FormData();

    for (let name in obj) {
        if (obj.hasOwnProperty(name) && (obj[name] != null && obj[name] !== 'null')) {
            data.set(name, obj[name]);
        }
    }

    return data;
}
