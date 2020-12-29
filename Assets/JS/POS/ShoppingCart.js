/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

export default function ShoppingCart(data = {}) {
    if (undefined === data.doc) {
        data.doc = {};
    }

    if (undefined === data.lines) {
        data.lines = [];
    }

    for (let line of data.lines) {
        updateLine(line);
    }

    let setCustomer = function (code) {
        data.doc.codcliente = code;
    };

    let add = function (code, description) {
        for (let line of data.lines) {
            if (line.referencia === code) {
                line.cantidad +=1;
                return true;
            }
        }

        data.lines.push({ referencia: code, descripcion: description });
        return false;
    };

    let edit = function (index, field, value) {
        if ('pvpunitarioiva' === field) {
            data.lines[index].pvpunitario = value / 1.16;
        }
        data.lines[index][field] = value;
    };

    let remove = function(index) {
        data.lines.splice(index, 1);
    };

    return { data, add, edit, remove, setCustomer };
}

function updateLine(line = {}) {
    line.pvptotaliva = priceWithTax(line.pvptotal, line.iva);
    line.pvpunitarioiva = line.pvptotaliva / line.cantidad;
}

function priceWithTax(base, rate) {
    return formatPrice(base * (1 + rate / 100));
}

function formatPrice(amount, factor = 2) {
    return parseFloat(amount).toFixed(factor);
}