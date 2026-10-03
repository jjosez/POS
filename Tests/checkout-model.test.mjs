import assert from 'node:assert/strict';
import {test} from 'node:test';

globalThis.AppSettings = {
    currency: {decimals: 2},
    document: {payment_policy: 'required'},
    cash: 'CASH',
};
const {default: checkout} = await import('../Assets/JS/POS/models/CheckoutModel.js');

test('optional policy allows an unpaid or partial balance', () => {
    checkout.updateTotal(1000);
    checkout.paymentPolicy = 'optional';
    assert.equal(checkout.getState().canFinalize, true);
    checkout.setPayment({amount: 200, method: 'CASH', description: 'Cash'});
    assert.equal(checkout.getState().pendingAmount, 800);
    assert.equal(checkout.getState().collectedAmount, 200);
    assert.equal(checkout.getState().canFinalize, true);
});

test('required policy only allows a full payment', () => {
    checkout.clear();
    checkout.updateTotal(1000);
    checkout.paymentPolicy = 'required';
    assert.equal(checkout.getState().canFinalize, false);
    checkout.setPayment({amount: 300, method: 'CASH', description: 'Cash'});
    assert.equal(checkout.getState().canFinalize, false);
    checkout.setPayment({amount: 700, method: 'CASH', description: 'Cash'});
    assert.equal(checkout.getState().pendingAmount, 0);
    assert.equal(checkout.getState().canFinalize, true);
});
