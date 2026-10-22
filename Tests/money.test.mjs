import assert from 'node:assert/strict';
import {test} from 'node:test';

globalThis.AppSettings = {
    currency: {decimals: 2},
};

const Money = await import('../Assets/JS/POS/Money.js');

test('rounds a tax-inclusive floating-point result to currency precision', () => {
    assert.equal(Money.roundCurrency(294.99999), 295);
    assert.equal(Money.roundFixed(294.99999), '295.00');
});

test('reconstructs 295.00 after removing 16 percent tax', () => {
    const line = {cantidad: 1, iva: 16};
    const netPrice = Money.priceWithoutTax(295, line);

    assert.equal(netPrice, 295 / 1.16);
    assert.equal(Money.priceWithTax(netPrice, line.iva), '295.00');
});

test('keeps enough net precision for documents configured with six decimals', () => {
    const netPrice = Money.priceWithoutTax(295, {cantidad: 1, iva: 16});
    const netTotal = Math.round(netPrice * 1e6) / 1e6;
    const taxTotal = Math.round(netPrice * 0.16 * 1e6) / 1e6;

    assert.equal(netTotal + taxTotal, 295);
});

test('does not introduce a cent through double rounding', () => {
    assert.equal(Money.roundFixed(1.234999), '1.23');
});

test('rounds negative monetary amounts symmetrically', () => {
    assert.equal(Money.roundFixed(-1.005), '-1.01');
});

test('rounds line prices according to terminal settings', () => {
    assert.equal(Money.roundLinePrice(294.123, {mode: 'none', decimals: 2}), 294.123);
    assert.equal(Money.roundLinePrice(294.123, {mode: 'nearest', decimals: 2}), 294.12);
    assert.equal(Money.roundLinePrice(294.123, {mode: 'up', decimals: 2}), 294.13);
    assert.equal(Money.roundLinePrice(294.123, {mode: 'precision', decimals: 2}), 294.123);
    assert.equal(Money.roundLinePrice(294.999994, {mode: 'precision', decimals: 2}), 295);
});
