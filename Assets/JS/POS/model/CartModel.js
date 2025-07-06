import * as Money from "./../Money.js";
import eventManager from "../components/EventManager.js";
import AppEventManager from "../core/EventManager.js";

class CartModel {
    constructor({doc, token} = {}) {
        this.init = doc;
        this.doc = doc;
        this.lines = [];
        this.count = 0;
        this.token = token
    }

    deleteProduct(index) {
        this.lines.splice(index, 1);
        this.cartChangeEvent();
    }

    editProduct(index, field, value) {
        if ('pvpunitarioiva' === field) {
            this.lines[index].pvpunitario = Money.priceWithoutTax(value, this.lines[index]);
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
        this.doc.nombrecliente = '';
        this.doc.codcliente = codcliente;
        this.cartChangeEvent();
    }

    setCustomField(field, value) {
        this.doc[field] = value;
        this.cartChangeEvent();
    }

    setDocumentClass(code, serie) {
        this.doc['tipo-documento'] = code;
        this.doc['codserie'] = serie;
    }

    setDiscountPercent(value = 0) {
        this.doc.dtopor1 = value;
        this.cartChangeEvent();
    }

    setPriceWithTax(line) {
        line.pvptotaliva = Money.priceWithTax(line.pvptotal, line.iva);
        line.pvpunitarioiva = Money.roundFixed(line.pvptotaliva / line.cantidad);
    }

    setProduct(code, description, thumbnail) {
        if (code === '') {
            this.lines.unshift({referencia: code, descripcion: description, thumbnail: thumbnail});
        } else {
            const product = this.lines.find(element => element.referencia === code);

            if (product) {
                product.cantidad = (product.cantidad || 0) + 1;
            } else {
                this.lines.unshift({referencia: code, descripcion: description, thumbnail: thumbnail});
            }
        }

        this.cartChangeEvent();
    }

    update({doc = this.init, lines = [], token = ''}) {
        this.doc = doc;
        this.lines = lines;
        this.count = 0;
        this.token = token ? token : this.token;

        for (let line of this.lines) {
            this.count += line.cantidad;
            this.setPriceWithTax(line);
        }

        this.cartUpdateEvent();
    }

    updateDocumentClass() {
        this.doc['tipo-documento'] = this.doc['generadocumento'];
    }

    updateDocumentType(code, serie) {
        this.setDocumentClass(code, serie)
        this.cartChangeEvent();
    }

    cartUpdateEvent() {
        eventManager.emit('onCartUpdate', this);
        AppEventManager.emit('onCartUpdate', this);
    }

    cartChangeEvent() {
        eventManager.emit('onCartChange', this);
        AppEventManager.emit('onCartChange', this);
    }
}

export default CartModel;
