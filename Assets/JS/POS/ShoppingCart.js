/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Money from "./Money.js";

export default class ShoppingCart {
    constructor(data = {}) {
        this.doc = data.doc ? data.doc : {};
        this.lines = data.lines ? data.lines : [];

        for (let line of this.lines) {
            this.setPriceWithTax(line);
        }
    }

    add(code, description) {
        for (let line of this.lines) {
            if (line.referencia === code) {
                line.cantidad++;
                return true;
            }
        }

        this.lines.unshift({ referencia: code, descripcion: description });
        return false;
    }

    edit(index, field, value) {
        if ('pvpunitarioiva' === field) {
            this.lines[index].pvpunitario = Money.priceWithoutTax(value, this.lines[index].iva);
        }
        this.lines[index][field] = value;
    }

    delete(index) {
        this.lines.splice(index, 1);
    }

    setCustomer(codcliente) {
        this.doc.codcliente = codcliente;
    }

    setPriceWithTax(line) {
        line.pvptotaliva = Money.priceWithTax(line.pvptotal, line.iva);
        line.pvpunitarioiva = line.pvptotaliva / line.cantidad;
    }
}