/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class Cart {

    constructor(args = {}) {
        this._dtopor1 = args.doc.dtopor1;
        this._dtopor2 = args.doc.dtopor2;
        this._irpf =  args.doc.irpf;
        this._neto = args.doc.neto;
        this._netosindto = args.doc.netosindto;
        this._total = args.doc.total;
        this._totalirpf = args.doc.totalirpf;
        this._totaliva = args.doc.totaliva;
        this._totalrecargo = args.doc.totalrecargo;
        this.setCartItems(args.lines);
    }


    get cartItems() {
        return this._cartItems;
    }

    set cartItems(cartItem) {
        this._cartItems = cartItem;
    }

    get dtopor1() {
        return this._dtopor1;
    }

    set dtopor1(value) {
        this._dtopor1 = value;
    }

    get dtopor2() {
        return this._dtopor2;
    }

    set dtopor2(value) {
        this._dtopor2 = value;
    }

    get irpf() {
        return this._irpf;
    }

    set irpf(value) {
        this._irpf = value;
    }

    get neto() {
        return this._neto;
    }

    set neto(value) {
        this._neto = value;
    }

    get netosindto() {
        return this._netosindto;
    }

    set netosindto(value) {
        this._netosindto = value;
    }

    get total() {
        return this._total;
    }

    set total(value) {
        this._total = value;
    }

    get totalirpf() {
        return this._totalirpf;
    }

    set totalirpf(value) {
        this._totalirpf = value;
    }

    get totaliva() {
        return this._totaliva;
    }

    set totaliva(value) {
        this._totaliva = value;
    }

    get totalrecargo() {
        return this._totalrecargo;
    }

    set totalrecargo(value) {
        this._totalrecargo = value;
    }

    addCartItem(code, description) {
        for (let cartItem of this._cartItems) {
            if (cartItem.referencia === code) {
                cartItem.cantidad +=1;
                return true;
            }
        }

        this._cartItems.push(new CartItem({referencia: code, descripcion: description}));
        return false;
    }

    deleteCartItem(index) {
        this._cartItems.splice( index, 1 );
        //console.log('Deleting index:', index);
    }

    editCartItem(index, field, value) {
        this.cartItems[index][field] = value;
        //console.log('index/field editing:', index + '/' + field);
    }

    getCartItems() {
        var lines = [];
        for (let item of this.cartItems) {
            lines.push(item.toArray());
        }
        return lines;
    }

    setCartItems(lines) {
        this._cartItems = [];
        if (typeof lines !== 'undefined') {
            for (let line of lines) {
                this._cartItems.push(new CartItem(line));
            }
        }
    }
}
