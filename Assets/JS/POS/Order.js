import { postRequest } from "./Core.js";

/**
 * @param {string} code
 */
export function deleteHoldRequest(code) {
    const data = new FormData();

    data.set('action', 'delete-order-on-hold');
    data.set('code', code);

    return postRequest(data);
}

/**
 * @param {string} code
 */
export function reprintRequest(code) {
    const data = new FormData();

    data.set('action', 'reprint-order');
    data.set('code', code);

    return postRequest(data);
}

/**
 * @param {string} code
 */
export function reprintPausedOrderRequest(code) {
    const data = new FormData();

    data.set('action', 'reprint-paused-order');
    data.set('code', code);

    return postRequest(data);
}

export function getLastOrders(){
    const data = new FormData();

    data.set('action', 'get-last-orders');

    return postRequest(data);
}

export function getOnHoldRequest() {
    const data = new FormData();

    data.set('action', 'get-orders-on-hold');

    return postRequest(data);
}

export function holdRequest({doc, lines, linesMap, token}) {
    const data = getFormData(doc);

    data.set('token', token);
    data.set('action', 'hold-order');
    data.set('lines', JSON.stringify(lines));

    /*const data = new FormData();

    data.set('action', 'hold-order');
    data.set('document', JSON.stringify(doc));
    data.set('linesMap', JSON.stringify(Object.fromEntries(linesMap)));
    data.set('token', token);*/

    return postRequest(data);
}

export function recalculateRequest({ doc, lines, linesMap }) {
    const data = getFormData(doc);


    data.set('action', "recalculate-order");
    data.set('lines', JSON.stringify(lines));

    /*const data = new FormData();

    data.set('action', "recalculate-order");
    data.set('document', JSON.stringify(doc));
    data.set('linesMap', JSON.stringify(Object.fromEntries(linesMap)));*/

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

function getFormData(obj = {}) {
    const data = new FormData();

    for (let name in obj) {
        if (obj.hasOwnProperty(name) && (obj[name] != null && obj[name] !== 'null')) {
            data.set(name, obj[name]);
        }
    }

    return data;
}
