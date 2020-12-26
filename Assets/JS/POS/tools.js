export function loadOperation(callback, url, code) {
    let data = {
        action: "resume-document",
        code: code
    };

    $.ajax({
        type: "POST",
        url: url,
        dataType: "json",
        data: data,
        success: callback,
        error: function (xhr, status, error) {
            console.error('Error al cargar la venta', xhr.responseText);
        }
    });
}

export function recalculateCartLines(callback, url, lines, formName) {
    let data = {};
    $.each($("#" + formName).serializeArray(), function (key, value) {
        data[value.name] = value.value;
    });
    data.action = "recalculate-document";
    data.lines = lines;

    $.ajax({
        type: "POST",
        url: url,
        dataType: "json",
        data: data,
        success: callback,
        error: function (xhr, status, error) {
            console.error('Error al recalcular las lineas', xhr.responseText);
        }
    });
}

export function search(callback, url, query, target) {
    let data = {
        action: "custom-search",
        query: query,
        target: target
    };
    $.ajax({
        url: url,
        data: data,
        type: "POST",
        dataType: "json",
        success: callback,
        error: function (xhr, status) {
            console.error('Error en la busqueda', xhr.responseText);
            return false;
        }
    });
}

export function searchBarcode(callback, url, query) {
    let data = {
        action: "barcode-search",
        query: query
    };
    $.ajax({
        url: url,
        data: data,
        type: "POST",
        dataType: "json",
        success: callback,
        error: function (xhr, status) {
            console.error('Error searching by code', xhr.responseText);
        }
    });
}

// Helper functions
export function formatNumber(val) {
    return parseFloat(val).toFixed(2);
}

export function testResponseTime(startTime, label = 'Exec time:') {
    //Calculate the difference in milliseconds.
    let time = performance.now() - startTime;

    //Convert milliseconds to seconds.
    let seconds = time / 100;
    console.log(label, seconds.toFixed(3));
}