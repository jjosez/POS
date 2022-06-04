import * as Money from "./Money.js";

export default class CartClass {
    constructor({ doc } = {}) {
        this.init = doc;
        this.doc = doc;
        this.lines = [];
        this.count = 0;
    }

    addProduct(code, description) {
        if (this.lines.some(element => {
            return element.referencia === code ? element.cantidad++ : false;
        })) {
            this.updateCartEvent();
            return;
        }
        this.lines.unshift({referencia: code, descripcion: description});
        this.updateCartEvent();
    }

    deleteProduct(index) {
        this.lines.splice(index, 1);
        this.updateCartEvent();
    }

    editProduct(index, field, value) {
        if ('pvpunitarioiva' === field) {
            this.lines[index].pvpunitario = Money.priceWithoutTax(value, this.lines[index].iva);
        }
        this.lines[index][field] = value;

        return this.lines[index];
    }

    getDiscountAmount() {
        return (this.doc.netosindto - this.doc.neto) || 0;
    }

    getProduct(index) {
        this.lines[index].index = index;
        return this.lines[index];
    }

    setCustomer(codcliente) {
        this.doc.codcliente = codcliente;
        this.updateCartEvent();
    }

    setDiscountPercent(value = 0) {
        this.doc.dtopor1 = value;
        this.updateCartEvent();
    }

    setPriceWithTax(line) {
        line.pvptotaliva = Money.priceWithTax(line.pvptotal, line.iva);
        line.pvpunitarioiva = line.pvptotaliva / line.cantidad;
    }

    update({ doc, lines } = {}) {
        this.doc = doc;
        this.lines = lines;
        this.count = 0;

        for (let line of this.lines) {
            this.count += line.cantidad;
            this.setPriceWithTax(line);
        }

        this.updateCartViewEvent(this);
    }

    updateClean({ token = '' }) {
        this.count = 0;
        this.doc = this.init;
        this.doc.token = token;
        this.lines = [];

        this.updateCartViewEvent(this);
    }

    updateCartViewEvent(data) {
        document.dispatchEvent(new CustomEvent('updateCartViewEvent', { detail: data }));
    }

    updateCartEvent() {
        document.dispatchEvent(new Event('updateCartEvent'));
    }
}

