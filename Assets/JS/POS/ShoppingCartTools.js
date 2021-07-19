/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
const TARGET_URL = "POS";

export function deletePausedTransaction(code, form) {
    const elements = form.elements;

    elements.action.value = 'delete-order-on-hold';
    elements.idpausada.value = code;

    form.submit();
}

export function pauseDocument(lines, form) {
    if (lines.length <= 0) {
        return false;
    }

    const elements = form.elements;

    elements.action.value = 'hold-order';
    elements.lines.value = JSON.stringify(lines);
    form.submit();
}

export function resumeTransaction(callback, code) {
    let data = {
        action: "resume-order",
        code: code
    };

    baseAjaxRequest(callback, data, 'Error al cargar la venta');
}

export function recalculate(callback, lines, form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    data.action = "recalculate-order";
    data.lines = lines;

    baseAjaxRequest(callback, data, 'Error al recalcular las lineas');
}

export function searchBarcode(callback, query) {
    baseSearch(callback, query, 'search-barcode');
}

export function searchCustomer(callback, query) {
    baseSearch(callback, query, 'search-customer');
}

export function searchProduct(callback, query) {
    baseSearch(callback, query, 'search-product');
}

// Helper functions
export function formatNumber(val) {
    return parseFloat(val).toFixed(2);
}

export function roundDecimals(amount, roundRate = 1000) {
    return Math.round(amount * roundRate) / roundRate;
}

export function testResponseTime(startTime, label = 'Exec time:') {
    //Calculate the difference in milliseconds.
    let time = performance.now() - startTime;

    //Convert milliseconds to seconds.
    let seconds = time / 100;
    console.log(label, seconds.toFixed(3));
}

function baseSearch(callback, query, action) {
    let data = {
        action: action,
        query: query
    };
    $.ajax({
        type: "POST",
        url: TARGET_URL,
        dataType: "json",
        data: data,
        success: callback,
        error: function (xhr) {
            console.error('Error searching', xhr.responseText);
            return false;
        }
    });
}

function baseAjaxRequest(callback, data, emessage) {
    $.ajax({
        type: "POST",
        url: TARGET_URL,
        dataType: "json",
        data: data,
        success: callback,
        error: function (xhr) {
            console.error(emessage, xhr.responseText);
        }
    });
}