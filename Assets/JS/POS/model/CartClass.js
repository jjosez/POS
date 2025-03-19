import * as Money from "./../Money.js";

class CartClass {
    constructor({doc, token} = {}) {
        this.init = doc;
        this.doc = doc;
        this.lines = [];
        this.count = 0;
        this.token = token
        //this.linesMap = new Map();
    }

    deleteProduct(index) {
        this.lines.splice(index, 1);
        this.updateCartEvent();
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
        this.updateCartEvent();
    }

    setCustomField(field, value) {
        this.doc[field] = value;
        this.updateCartEvent();
    }

    setDocumentType(code, serie) {
        this.doc['tipo-documento'] = code;
        this.doc['codserie'] = serie;
    }

    setDiscountPercent(value = 0) {
        this.doc.dtopor1 = value;
        this.updateCartEvent();
    }

    setPriceWithTax(line) {
        line.pvptotaliva = Money.priceWithTax(line.pvptotal, line.iva);
        line.pvpunitarioiva = Money.roundFixed(line.pvptotaliva / line.cantidad);
    }

    setProduct(code, description, thumbnail) {
        // Si es una linea libre agregamos una nueva linea, si no buscamos si el codigo ya esta registrado y
        // aumentamos la cantidad o agregamos el nuevo codigo.
        if (code === '') {
            this.lines.unshift({referencia: code, descripcion: description, thumbnail: thumbnail});
        } else {
            // Usamos `find` para obtener el producto directamente y modificarlo
            const product = this.lines.find(element => element.referencia === code);

            if (product) {
                product.cantidad = (product.cantidad || 0) + 1;
            } else {
                this.lines.unshift({referencia: code, descripcion: description, thumbnail: thumbnail});
            }
        }

        this.updateCartEvent();
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

        this.updateCartViewEvent(this);
    }

    updateDocumentClass() {
        this.doc['tipo-documento'] = this.doc['generadocumento'];
    }

    updateDocumentType(code, serie) {
        this.setDocumentType(code, serie)
        this.updateCartEvent();
    }

    updateCartViewEvent(data) {
        document.dispatchEvent(new CustomEvent('onCartUpdate', {detail: data}));
    }

    updateCartEvent() {
        //console.log('Map', this.linesMap);
        //console.log('Object', this.lines);
        //console.log('Object JSON', JSON.stringify(this.lines));
        //console.log('Map JSON', JSON.stringify(Object.fromEntries(this.linesMap)))
        document.dispatchEvent(new Event('onCartChange'));
    }
}

export default CartClass;
