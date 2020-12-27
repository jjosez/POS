/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
var ShoppingCart = function (data = {}) {
    if (undefined === data.doc) {
        data.doc = {};
    }

    if (undefined === data.lines) {
        data.lines = [];
    }

    for (let line of data.lines) {
        ShopingCartLine(line);
    }

    var addItem = function (code, description) {
        for (let line of data.lines) {
            if (line.referencia === code) {
                line.cantidad +=1;
                return true;
            }
        }

        data.lines.push({referencia: code, descripcion: description});
        return false;
    };

    var removeItem = function(index) {
        data.lines.splice(index, 1);
    };

    return {
        data,
        addItem,
        removeItem
    };
};


export function ShoppingCartC(args = {}) {
    if (undefined === args.doc) {
        args.doc = {};
    }

    let ShoppingCart = new Object(args);

    if (undefined !== args.lines) {
        for (let line of args.lines) {
            ShopingCartLine(line);
        }
    }

    return ShoppingCart;
}


function ShopingCartLine(args = {}) {
    let line = new Object(args);

    line.pvptotaliva = formatPrice(line.pvptotal * (1 + line.iva / 100));
    line.pvpunitarioiva = line.pvptotaliva / line.cantidad;
}

function formatPrice(amount, factor = 2) {
    return parseFloat(amount).toFixed(factor);
}

export { ShoppingCart };