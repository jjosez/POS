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

test('prevents adding more units than available stock', () => {
    const cart = cartWith([{referencia: 'A', cantidad: 2, stock: 2, allow_no_stock: false}]);

    assert.equal(cart.canAddProduct({code: 'A', stock: 2, allow_no_stock: false}), false);
    assert.equal(cart.canSetQuantity(0, 3), false);
    assert.equal(cart.canSetQuantity(0, 2), true);
});

test('aggregates stock across separate lines with the same reference', () => {
    const cart = cartWith([
        {referencia: 'A', cantidad: 2, stock: 5, allow_no_stock: false},
        {referencia: 'A', cantidad: 3, stock: 5, allow_no_stock: false},
    ]);

    assert.equal(cart.getMaxQuantity(0), 2);
    assert.equal(cart.getMaxQuantity(1), 3);
    assert.equal(cart.canAddProduct({code: 'A', stock: 5, allow_no_stock: false}), false);
});

test('does not limit free lines or products that allow sales without stock', () => {
    const cart = cartWith([{referencia: 'A', cantidad: 10, stock: 0, allow_no_stock: true}]);

    assert.equal(cart.canSetQuantity(0, 100), true);
    assert.equal(cart.canAddProduct({code: '', stock: 0, allow_no_stock: false}), true);
});

test('preserves stock metadata after a server recalculation', () => {
    const cart = cartWith([{referencia: 'A', cantidad: 1, stock: 4, allow_no_stock: false}]);

    cart.update({doc: {}, lines: [{referencia: 'A', cantidad: 1, pvptotal: 1, iva: 0}]});

    assert.equal(cart.lines[0].stock, 4);
    assert.equal(cart.lines[0].allow_no_stock, false);
});

test('counts a product waiting for its first server recalculation as one unit', () => {
    const cart = cartWith([{referencia: 'A', stock: 1, allow_no_stock: false, pending_quantity: 1}]);

    assert.equal(cart.canAddProduct({code: 'A', stock: 1, allow_no_stock: false}), false);
});
