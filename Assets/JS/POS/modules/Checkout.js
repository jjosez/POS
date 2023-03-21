import {checkoutView} from "../UI.js";
import CheckoutClass from "../model/CheckoutClass.js";

const Checkout = new CheckoutClass({
    cashMethod: settings.cash
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
        checkoutView().paymentInput.value = Checkout.getOutstandingBalance();
        return;
    }

    checkoutView().paymentInput.value = checkoutView().getCurrentPaymentInput() + parseFloat(value) || 0;
}

/**
 * Set new payment from dialog.
 */
function paymentSetAction(data) {
    if (checkoutView().paymentInput.valueAsNumber === 0 || checkoutView().paymentInput.value === '') {
        checkoutView().paymentInput.value = Checkout.getOutstandingBalance();
    }

    Checkout.setPayment(checkoutView().getCurrentPaymentData(data));
    checkoutView().paymentInput.value = 0;
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
