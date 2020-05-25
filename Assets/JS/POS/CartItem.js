/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class CartItem {
    constructor(args = {}) {
        this._cantidad = args.cantidad;
        this._descripcion = args.descripcion;
        this._dtopor = args.dtopor;
        this._dtopor2 = args.dtopor2;
        this._irpf = args.irpf;
        this._iva = args.iva;
        this._pvpsindto = args.pvpsindto;
        this._pvptotal = args.pvptotal;
        this._pvpunitario = args.pvpunitario;
        this._recargo = args.recargo;
        this._referencia = args.referencia;
    }
    get cantidad() {
        return this._cantidad;
    }

    set cantidad(value) {
        this._cantidad = value;
    }

    get descripcion() {
        return this._descripcion;
    }

    set descripcion(value) {
        this._descripcion = value;
    }

    get dtopor() {
        return this._dtopor;
    }

    set dtopor(value) {
        this._dtopor = value;
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

    get iva() {
        return this._iva;
    }

    set iva(value) {
        this._iva = value;
    }

    get pvpsindto() {
        return this._pvpsindto;
    }

    set pvpsindto(value) {
        this._pvpsindto = value;
    }

    get pvptotal() {
        return this._pvptotal;
    }

    set pvptotal(value) {
        this._pvptotal = value;
    }

    get pvpunitario() {
        return this._pvpunitario;
    }

    set pvpunitario(value) {
        this._pvpunitario = value;
    }

    get recargo() {
        return this._recargo;
    }

    set recargo(value) {
        this._recargo = value;
    }

    get referencia() {
        return this._referencia;
    }

    set referencia(value) {
        this._referencia = value;
    }

    getObjectData() {
        return {
            cantidad: this._cantidad,
            descripcion: this._descripcion,
            dtopor: this._dtopor,
            dtopor2: this._dtopor2,
            irpf: this._irpf,
            iva: this._iva,
            pvpsindto: this._pvpsindto,
            pvptotal: this._pvptotal,
            pvpunitario: this._pvpunitario,
            recargo: this._recargo,
            referencia: this._referencia,
        };
    }

    toArray(){
        var json = {};
        for (let name in this) {
            if (this.hasOwnProperty(name)) {
                json[name.substr(1)] = this[name];
            }
        }
        return json;
    }
}