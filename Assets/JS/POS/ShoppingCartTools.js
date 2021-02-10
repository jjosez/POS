/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
const AjaxRequestUrl = "POS";

export function pauseDocument(lines, form) {
    if (lines.length <= 0) {
        return false;
    }

    const elements = form.elements;

    elements.action.value = 'pause-document';
    elements.lines.value = JSON.stringify(lines);
    form.submit();
}

export function resumeDocument(callback, code) {
    let data = {
        action: "resume-document",
        code: code
    };

    $.ajax({
        type: "POST",
        url: AjaxRequestUrl,
        dataType: "json",
        data: data,
        success: callback,
        error: function (xhr) {
            console.error('Error al cargar la venta', xhr.responseText);
        }
    });
}

export function recalculate(callback, lines, form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    data.action = "recalculate-document";
    data.lines = lines;

    $.ajax({
        type: "POST",
        url: AjaxRequestUrl,
        dataType: "json",
        data: data,
        success: callback,
        error: function (xhr) {
            console.error('Error al recalcular las lineas', xhr.responseText);
        }
    });
}

export function search(callback, query, target) {
    let data = {
        action: "custom-search",
        query: query,
        target: target
    };
    $.ajax({
        type: "POST",
        url: AjaxRequestUrl,
        dataType: "json",
        data: data,
        success: callback,
        error: function (xhr) {
            console.error('Error searching', xhr.responseText);
            return false;
        }
    });
}

export function searchBarcode(callback, query) {
    let data = {
        action: "barcode-search",
        query: query
    };

    console.log(query);
    $.ajax({
        type: "POST",
        url: AjaxRequestUrl,
        dataType: "json",
        data: data,
        success: callback,
        error: function (xhr) {
            console.error('Error searching by barcode', xhr.responseText);
        }
    });
}

export function searchBarcode2(callback, query) {
    let data = {
        action: "barcode-search",
        query: query
    };

    console.log(query);
    $.ajax({
        type: "POST",
        url: AjaxRequestUrl,
        dataType: "json",
        data: data,
        success: function (response) {
            if (false === response.length) {
                callback = false;
                return;
            }

            callback = {
                code: response[0].code,
                description: response[0].description
            };
        },
        error: function (xhr) {
            console.error('Error searching by barcode', xhr.responseText);
        }
    });
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