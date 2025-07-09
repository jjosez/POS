import {postRequest} from "../Core.js";
import EventManager from "../core/EventManager.js";

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

export function saveRequest({doc, lines, token}, payments) {
    const payload = {
        ...doc,
        lines: lines,
        payments: payments
    };

    const resource = `POS?action=save-order&token=${token}`;

    return postJsonRequest(resource, payload);
}

export function saveDraftRequest({doc, lines, token}) {
    const payload = {
        ...doc,
        lines: lines,
        draft: true,
        token: token
    }

    const resource = `POS?action=save-draft&token=${token}`;
    return postJsonRequest(resource, payload);
}

export function recalculateRequest({doc, lines}) {
    const payload = {
        ...doc,
        lines: lines
    };

    const resource = 'POS?action=recalculate-order';

    return postJsonRequest(resource, payload);
}

export function resumeRequest(code) {
    const data = new FormData();

    data.set('action', 'resume-order');
    data.set('code', code);

    return postRequest(data);
}


/**
 * Send a POST request with JSON payload
 * @param {string} resource - Example: 'POSQuery?action=recalculate-order'
 * @param {Object} payload - The JSON body to send
 * @returns {Promise<Object>} - Parsed JSON response
 */
export async function postJsonRequest(resource, payload = {}) {
    try {
        const response = await fetch(resource, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            return Promise.resolve({
                status: 'error',
                error: `HTTP ${response.status}`
            });
        }

        const result = await response.json();

        if (result.messages && result.messages.length) {
            EventManager.emit('responseMessages', result.messages);
        }

        return result;
    } catch (e) {
        console.warn(`❌ Error al ejecutar la consulta. ${e.message}`);
        return {
            status: 'error',
            error: e.message,
            data: {}
        };
    }
}
