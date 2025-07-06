import CheckoutModel from '../models/checkoutModel.js';
import * as CheckoutView from '../views/checkoutView.js';
import dispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';

export function handleDeletePayment(el) {
    const {index} = el.dataset;
    CheckoutModel.deletePayment(index);
}

export function handleRecalculatePayment(el) {
    const {value} = el.dataset;
    if (value === 'balance') {
        CheckoutView.setPaymentInputValue(CheckoutModel.getOutstandingBalance());
    } else {
        const current = CheckoutView.getPaymentInputValue();
        CheckoutView.setPaymentInputValue(current + parseFloat(value) || 0);
    }
}

export function handleSetPayment(el) {
    const code = el.dataset.code;
    const description = el.dataset.description;

    let amount = CheckoutView.getPaymentInputValue();

    if (!amount || amount === 0) {
        amount = CheckoutModel.getOutstandingBalance();
    }

    const paymentData = {
        amount: amount,
        method: code,
        description: description
    };
    CheckoutModel.setPayment(paymentData);

    CheckoutView.setPaymentInputValue(0);
}

export function handleConfirmOrder(el) {
    console.log('💵 Confirmar orden');
}

let isCheckoutVisible = false;

export function handleShowCheckoutModal() {
    isCheckoutVisible = true;
    CheckoutView.togglePaymentModal();
    CheckoutView.render(CheckoutModel);
}

export function handleHideCheckoutModal() {
    isCheckoutVisible = false;
    CheckoutView.togglePaymentModal();
}

export function getCheckoutState() {
    return CheckoutModel.getState();
}

export function initCheckoutController() {
    dispatcher.register('deletePaymentAction', handleDeletePayment);
    dispatcher.register('orderSaveAction', handleHideCheckoutModal);
    dispatcher.register('recalculatePaymentAction', handleRecalculatePayment);
    dispatcher.register('setPaymentAction', handleSetPayment);
    dispatcher.register('showCheckoutModalAction', handleShowCheckoutModal);

    EventManager.on('onCheckoutUpdate', () => {
        if (isCheckoutVisible) CheckoutView.render(CheckoutModel);
    });

    EventManager.on('onCartUpdate', ({doc}) => {
        CheckoutModel.updateTotal(doc.total);
    });

    EventManager.on('onOrderComplete', () => {
        CheckoutModel.clear();
        CheckoutView.render(CheckoutModel);
    });

    dispatcher.listen();
}
