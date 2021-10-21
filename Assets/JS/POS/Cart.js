/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import * as Money from "./Money.js";

export default class Cart {
    constructor() {
        this.doc = {};
        this.lines = [];
    }

    addLine(code, description) {
        if (this.lines.some(element => {return element.referencia === code ? element.cantidad++ : false;})) {
            return;
        }
        this.lines.unshift({ referencia: code, descripcion: description });
    }

    editLine(index, field, value) {
        if ('pvpunitarioiva' === field) {
            this.lines[index].pvpunitario = Money.priceWithoutTax(value, this.lines[index].iva);
        }
        this.lines[index][field] = value;
    }

    deleteLine(index) {
        this.lines.splice(index, 1);
    }

    setCustomer(codcliente) {
        this.doc.codcliente = codcliente;
    }

    setPriceWithTax(line) {
        line.pvptotaliva = Money.priceWithTax(line.pvptotal, line.iva);
        line.pvpunitarioiva = line.pvptotaliva / line.cantidad;
    }

    update(data = {}) {
        this.doc = data.doc ? data.doc : {};
        this.lines = data.lines ? data.lines : [];

        for (let line of this.lines) {
            this.setPriceWithTax(line);
        }
    }
}