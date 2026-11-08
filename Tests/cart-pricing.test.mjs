import assert from 'node:assert/strict';
import {test} from 'node:test';

globalThis.AppSettings = {
    currency: {decimals: 2},
};

const {default: CartModel} = await import('../Assets/JS/POS/models/CartModel.js');

function cartWith(lines) {
    const cart = new CartModel({doc: {}});
    cart.lines = lines;
    return cart;
}

test('setPriceWithTax honours terminal rounding when mode is none', () => {
    globalThis.AppSettings.lineRounding = {mode: 'none', decimals: 4};
    const cart = cartWith([{pvptotal: 348, cantidad: 1, iva: 0}]);

    cart.setPriceWithTax(cart.lines[0]);

    assert.equal(cart.lines[0].pvptotaliva, 348);
    assert.equal(cart.lines[0].pvpunitarioiva, 348);
    assert.equal(typeof cart.lines[0].pvpunitarioiva, 'number');
});

test('setPriceWithTax rounds to the terminal configured decimals', () => {
    globalThis.AppSettings.lineRounding = {mode: 'none', decimals: 4};
    const cart = cartWith([{pvptotal: 348.1234567, cantidad: 1, iva: 0}]);

    cart.setPriceWithTax(cart.lines[0]);

    assert.equal(cart.lines[0].pvpunitarioiva, 348.1235);
});

test('setPriceWithTax rounds near upper bound with mode up', () => {
    globalThis.AppSettings.lineRounding = {mode: 'up', decimals: 2};
    const cart = cartWith([{pvptotal: 348.121, cantidad: 1, iva: 0}]);

    cart.setPriceWithTax(cart.lines[0]);

    assert.equal(cart.lines[0].pvpunitarioiva, 348.13);
});

test('setPriceWithTax falls back to two decimals when AppSettings.lineRounding missing', () => {
    delete globalThis.AppSettings.lineRounding;
    const cart = cartWith([{pvptotal: 348.1234567, cantidad: 1, iva: 0}]);

    cart.setPriceWithTax(cart.lines[0]);

    assert.equal(cart.lines[0].pvpunitarioiva, 348.12);
});
