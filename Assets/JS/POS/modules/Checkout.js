import {checkoutView} from "../UI.js";
import CheckoutClass from "../model/CheckoutClass.js";

const Checkout = new CheckoutClass({
    cashPaymentMethod: settings.cash
});

/**
 * Delete given payment at index.
 * @param {{index:int}} data
 */
function deletePaymentAction({index}) {
    Checkout.deletePayment(index);
}

/**
 * Update checkout view, when new payment was added.
 */
function recalculateAction({value}) {
    if (value === 'balance') {
        checkoutView().paymentInput.value = Checkout.getOutstandingBalance();
        return;
    }

    checkoutView().paymentInput.value = checkoutView().getCurrentPaymentInput() + parseFloat(value) || 0;
}

/**
 * Set new payment from dialog.
 */
function setPaymentAction() {
    Checkout.setPayment(checkoutView().getCurrentPaymentData());
    checkoutView().paymentInput.value = 0;
}

/**
 * Update checkout view, when new payment was added.
 */
function updateView() {
    checkoutView().enableConfirmButton(Checkout.change >= 0);
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
            return deletePaymentAction(data);

        case 'recalculatePaymentAction':
            return recalculateAction(data);

        case 'setPaymentAction':
            return setPaymentAction();
    }
}

document.addEventListener('click', checkoutEventHandler);
document.addEventListener('updateCheckout', updateView);

export default Checkout;
