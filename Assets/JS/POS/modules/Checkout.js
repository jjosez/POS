import CheckoutClass from "../model/CheckoutClass.js";
import {checkout} from "../View.js";

const Checkout = new CheckoutClass({
    cashMethod: AppSettings.cash
});

/**
 * Delete given payment at index.
 * @param {{index:int}} data
 */
function paymentDeleteAction({index}) {
    Checkout.deletePayment(index);
}

/**
 * Update checkout view, when new payment was added.
 */
function paymentRecalculateAction({value}) {
    if (value === 'balance') {
        checkout().paymentAmountInput().value = Checkout.getOutstandingBalance();
        return;
    }

    checkout().paymentAmountInput().value = checkout().getCurrentPaymentValue() + parseFloat(value) || 0;
}

/**
 * Set new payment from dialog.
 */
function paymentSetAction(data) {
    if (checkout().getCurrentPaymentValue() === 0) {
        checkout().paymentAmountInput().value = Checkout.getOutstandingBalance();
    }

    Checkout.setPayment(checkout().getCurrentPaymentData(data));
    checkout().paymentAmountInput().value = 0;
}

function showPaymentModalAction(data) {
    checkout().showPaymentModal(data);
}

/**
 * Update checkout totals when cart was updated.
 */
function updateTotals({detail}) {
    Checkout.updateTotal(detail.doc.total);
    checkout().enableConfirmButton(false);
}

/**
 * Update checkout view, when new payment was added.
 */
function updateView() {
    checkout().updateView(Checkout);
}

/**
 * @param {Event} event
 */
function checkoutEventHandler(event) {
    const data = event.target.dataset;
    const action = data.action;

    if (typeof action === 'undefined' || action === null) {
        return;
    }

    switch (action) {
        case 'deletePaymentAction':
            return paymentDeleteAction(data);

        case 'recalculatePaymentAction':
            return paymentRecalculateAction(data);

        case 'setPaymentAction':
            return paymentSetAction(data);

        case 'showPaymentModalAction':
            return showPaymentModalAction(data);
    }
}

document.addEventListener('click', checkoutEventHandler);
document.addEventListener('onCheckoutUpdate', updateView);
document.addEventListener('onCartUpdate', updateTotals);

export default Checkout;
