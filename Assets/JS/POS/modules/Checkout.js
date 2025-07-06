import CheckoutModel from "../model/CheckoutModel.js";
import CheckoutView from "../view/CheckoutView.js";
import EventManager from "../components/EventManager.js";

const Checkout = new CheckoutModel({
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
        CheckoutView.paymentAmountInput().value = Checkout.getOutstandingBalance();
        return;
    }

    CheckoutView.paymentAmountInput().value = CheckoutView.getCurrentPaymentValue() + parseFloat(value) || 0;
}

/**
 * Set new payment from dialog.
 */
function paymentSetAction(data) {
    if (CheckoutView.getCurrentPaymentValue() === 0) {
        CheckoutView.paymentAmountInput().value = Checkout.getOutstandingBalance();
    }

    Checkout.setPayment(CheckoutView.getCurrentPaymentData(data));
    CheckoutView.paymentAmountInput().value = 0;
}

function showPaymentModalAction(data) {
    CheckoutView.showPaymentModal(data);
}

/**
 * Update checkout totals when cart was updated.
 */
function updateTotals({doc}) {
    Checkout.updateTotal(doc.total);
    CheckoutView.enableConfirmButton(false);
}

/**
 * Update checkout view, when new payment was added.
 */
function updateView() {
    CheckoutView.updateView(Checkout);
}


/**
 * Clear the checkout model.
 */
function clearCheckout() {
    Checkout.clear();
}

/**
 * Processes checkout actions based on the action type specified in the event's data attributes.
 *
 * @param {Event} event - The event object triggered by the user's interaction.
 */
function checkoutEventHandler(event) {
    const { action } = event.target.dataset;

    // Si no existe una acción, salir
    if (!action) return;

    // Mapeo de acciones a funciones
    const actionMap = {
        'deletePaymentAction': paymentDeleteAction,
        //'recalculatePaymentAction': paymentRecalculateAction,
        //'setPaymentAction': paymentSetAction,
        'showPaymentModalAction': showPaymentModalAction,
    };

    // Si la acción existe en el mapa, ejecutarla
    if (actionMap[action]) {
        actionMap[action](event.target.dataset);
    }
}

document.addEventListener('click', checkoutEventHandler);

EventManager.on('onCartUpdate', updateTotals);
EventManager.on('onCheckoutUpdate', updateView);
EventManager.on('onOrderComplete', clearCheckout)

export default Checkout;
