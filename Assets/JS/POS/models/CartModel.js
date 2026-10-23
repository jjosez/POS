import * as Money from "../Money.js";
import EventManager from "../core/EventManager.js";

class CartModel {
    constructor({doc, token} = {}) {
        this.init = {...doc};
        this.doc = {...doc};
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

        this.cartChangeEvent();
        return this.lines[index];
    }

    getDiscountAmount() {
        return (this.doc.netosindto - this.doc.neto) || 0;
    }

    getProduct(index) {
        this.lines[index].index = index;
        return this.lines[index];
    }

    allowsWithoutStock(line = {}) {
        return [true, 1, '1', 'true'].includes(line.allow_no_stock);
    }

    getReferenceQuantity(reference, excludedIndex = null) {
        return this.lines.reduce((total, line, index) => {
            if (index === excludedIndex || line.referencia !== reference) return total;
            const quantity = line.cantidad ?? line.pending_quantity ?? 1;
            return total + (Number(quantity) || 0);
        }, 0);
    }

    getStockLimit(line = {}) {
        if (!line.referencia || this.allowsWithoutStock(line)) return null;
        if (line.stock === null || line.stock === undefined || line.stock === '') return null;

        const stock = Number(line.stock);
        return Number.isFinite(stock) ? Math.max(0, stock) : null;
    }

    getMaxQuantity(index) {
        const line = this.lines[index];
        const stock = this.getStockLimit(line);
        if (stock === null) return null;

        return Math.max(0, stock - this.getReferenceQuantity(line.referencia, Number(index)));
    }

    canAddProduct({code, stock, allow_no_stock}, quantity = 1) {
        const candidate = {referencia: code, stock, allow_no_stock};
        const stockLimit = this.getStockLimit(candidate);
        if (stockLimit === null) return true;

        return this.getReferenceQuantity(code) + quantity <= stockLimit + Number.EPSILON;
    }

    canSetQuantity(index, quantity) {
        const maxQuantity = this.getMaxQuantity(Number(index));
        return maxQuantity === null || quantity <= maxQuantity + Number.EPSILON;
    }

    setAgent(codagente) {
        this.doc.codagente = codagente;
        this.cartChangeEvent();
    }

    setCustomer(codcliente) {
        this.doc.nombrecliente = '';
        this.doc.codcliente = codcliente;
        this.cartChangeEvent();
    }

    resetCustomer(codcliente) {
        this.doc.nombrecliente = '';
        this.doc.codcliente = codcliente;
    }

    setCustomField(field, value) {
        this.doc[field] = value;
        this.cartChangeEvent();
    }

    setDocumentClass(code, serie, title) {
        this.doc['tipo-documento'] = code;
        this.doc['title'] = title;
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

    setProduct(code, description, thumbnail, stock = null, allow_no_stock = null) {
        if (code === '') {
            this.lines.unshift({referencia: code, descripcion: description, thumbnail: thumbnail});
        } else {
            const product = this.lines.find(element => element.referencia === code);

            if (product) {
                if (product.cantidad === undefined) {
                    product.pending_quantity = (product.pending_quantity || 1) + 1;
                } else {
                    product.cantidad = (product.cantidad || 0) + 1;
                }
            } else {
                this.lines.unshift({
                    referencia: code,
                    descripcion: description,
                    thumbnail: thumbnail,
                    stock,
                    allow_no_stock,
                    pending_quantity: 1,
                });
            }
        }

        this.cartChangeEvent();
    }

    addOrUpdateProduct(code, description, thumbnail, stock = null, allow_no_stock = null) {
        const product = this.lines.find(element => element.referencia === code);

        if (product) {
            if (product.cantidad === undefined) {
                product.pending_quantity = (product.pending_quantity || 1) + 1;
            } else {
                product.cantidad = (product.cantidad || 0) + 1;
            }
            product.stock = stock ?? product.stock;
            product.allow_no_stock = allow_no_stock ?? product.allow_no_stock;
        } else {
            this.lines.unshift({
                referencia: code,
                descripcion: description,
                thumbnail: thumbnail,
                stock,
                allow_no_stock,
                pending_quantity: 1,
            });
        }

        this.cartChangeEvent();
    }

    addProduct(code, description, thumbnail, stock = null, allow_no_stock = null) {
        this.lines.unshift({
            referencia: code,
            descripcion: description,
            thumbnail: thumbnail,
            stock,
            allow_no_stock,
            pending_quantity: code === '' ? null : 1,
        });

        this.cartChangeEvent();
    }

    update({doc = this.init, lines = [], token = ''}) {
        const tipoDocumento = this.doc['tipo-documento'];
        const previousMetadata = new Map();
        for (const line of this.lines) {
            if (line.referencia) {
                previousMetadata.set(line.referencia, {
                    stock: line.stock,
                    allow_no_stock: line.allow_no_stock,
                });
            }
        }

        this.doc = {...doc};
        if (!this.doc['tipo-documento'] && tipoDocumento) {
            this.doc['tipo-documento'] = tipoDocumento;
        }

        this.lines = lines.map(line => {
            const previous = previousMetadata.get(line.referencia) || {};
            return {
                ...line,
                stock: line.stock ?? previous.stock,
                allow_no_stock: line.allow_no_stock ?? previous.allow_no_stock,
            };
        });
        this.count = 0;
        this.token = token ? token : this.token;

        for (let line of this.lines) {
            this.count += line.cantidad;
            this.setPriceWithTax(line);
        }

        this.cartUpdateEvent();
    }

    updateDocumentType(code, serie, title) {
        this.setDocumentClass(code, serie, title)
        this.cartChangeEvent();
    }

    cartUpdateEvent() {
        EventManager.emit('event:cart:updated', this);
    }

    cartChangeEvent() {
        EventManager.emit('cart:changed', this);
    }
}

export default CartModel;
