/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Money from "./Money.js";

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

        data.lines.unshift({ referencia: code, descripcion: description });
        return false;
    };

    let edit = function (index, field, value) {
        if ('pvpunitarioiva' === field) {
            data.lines[index].pvpunitario = Money.priceWithoutTax(value, data.lines[index].iva);
        }
        data.lines[index][field] = value;
    };

    let remove = function(index) {
        data.lines.splice(index, 1);
    };

    return { lines: data.lines, add, doc: data.doc, edit, remove, setCustomer };
}

function updateLine(line = {}) {
    line.pvptotaliva = Money.priceWithTax(line.pvptotal, line.iva);
    line.pvpunitarioiva = line.pvptotaliva / line.cantidad;
}