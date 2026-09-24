import assert from 'node:assert/strict';
import {test} from 'node:test';

globalThis.AppSettings = {
    currency: {decimals: 2},
    document: {payment_policy: 'required'},
    customer: {codcliente: 'GENERIC'},
    cash: 'CASH',
};
const {default: checkout} = await import('../Assets/JS/POS/models/CheckoutModel.js');

test('optional balance, credit denial, stale approvals and full payment', () => {
    checkout.updateTotal(1000);
    checkout.paymentPolicy = 'optional';
    assert.equal(checkout.getState().canFinalize, true);
    assert.equal(checkout.getState().customerAccountAmount, 0);
    checkout.setPayment({amount: 200, method: 'CASH', description: 'Cash'});
    assert.equal(checkout.getState().pendingAmount, 800);
    assert.equal(checkout.getState().canFinalize, true);

    checkout.paymentPolicy = 'customer-account';
    checkout.customerCode = 'CLIENT';
    checkout.updateCheckoutEvent();
    const oldRevision = checkout.accountRevision;
    assert.equal(checkout.getState().canFinalize, false);
    checkout.setAccountResult({customer_account: true, status: 'approved'}, oldRevision);
    assert.equal(checkout.getState().canFinalize, true);

    checkout.setPayment({amount: 100, method: 'CASH', description: 'Cash'});
    checkout.setAccountResult({customer_account: true, status: 'approved'}, oldRevision);
    assert.equal(checkout.getState().canFinalize, false);
    checkout.setAccountResult({customer_account: false, status: 'not_available'}, checkout.accountRevision);
    assert.equal(checkout.getState().canFinalize, false);
    checkout.setPayment({amount: 700, method: 'CASH', description: 'Cash'});
    assert.equal(checkout.getState().customerAccountAmount, 0);
    assert.equal(checkout.getState().canFinalize, true);

    checkout.clear();
    checkout.customerCode = 'GENERIC';
    checkout.setAccountResult({customer_account: true, status: 'approved'}, checkout.accountRevision);
    assert.equal(checkout.getState().canFinalize, false);
    checkout.paymentPolicy = 'required';
    assert.equal(checkout.getState().canFinalize, false);
});
