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
        data.lines[index][field] = value;
    };

    let remove = function(index) {
        data.lines.splice(index, 1);
    };

    return { data, add, edit, remove };
};

function updateLine(line = {}) {
    line.pvptotaliva = formatPrice(line.pvptotal * (1 + line.iva / 100));
    line.pvpunitarioiva = line.pvptotaliva / line.cantidad;
}

function formatPrice(amount, factor = 2) {
    return parseFloat(amount).toFixed(factor);
}