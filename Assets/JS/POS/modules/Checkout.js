import {checkoutView} from "../UI.js";
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
        /*checkoutView().paymentInput.value = Checkout.getOutstandingBalance();*/
        return;
    }

    /*checkoutView().paymentInput.value = checkoutView().getCurrentPaymentInput() + parseFloat(value) || 0;*/
    checkout().paymentAmountInput().value = checkoutView().getCurrentPaymentInput() + parseFloat(value) || 0;
}

/**
 * Set new payment from dialog.
 */
function paymentSetAction(data) {
    if (checkout().paymentAmountInput().valueAsNumber === 0 || checkout().paymentAmountInput().value === '') {
        checkout().paymentAmountInput().value = Checkout.getOutstandingBalance();
    }

    Checkout.setPayment(checkoutView().getCurrentPaymentData(data));
    checkout().paymentAmountInput().value = 0;
}

function showPaymentModalAction(data) {
    checkoutView().showPaymentModal(data);
}

/**
 * Update checkout totals when cart was updated.
 */
function updateTotals({detail}) {
    Checkout.updateTotal(detail.doc.total);
    checkoutView().enableConfirmButton(false);
}

/**
 * Update checkout view, when new payment was added.
 */
function updateView() {
    checkoutView().enableConfirmButton(Checkout.change >= 0 && Checkout.total !== 0);
    checkoutView().updateTotals(Checkout);
    checkoutView().updatePaymentList(Checkout);
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
