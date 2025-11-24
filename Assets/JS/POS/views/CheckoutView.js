import templates from "../views/TemplateManger.js";
import Modals from "../components/Modals.js";
import {roundFixed} from "../Money.js";

const viewElements = {
    checkoutTotal: document.getElementById('checkoutTotal'),
    tenderedAmount: document.getElementById('checkoutTenderedAmount'),
    changeAmount: document.getElementById('checkoutChangeAmount'),

    paymentInput: document.getElementById('paymentApplyInput'),
    confirmButton: document.getElementById('orderSaveButton'),

    paymentListView: 'paymentListTemplateView'
};

export function render(model) {
    const state = model.getState();

    viewElements.checkoutTotal.textContent = roundFixed(state.total);
    viewElements.tenderedAmount.textContent = roundFixed(model.getPaymentsTotal());
    viewElements.changeAmount.textContent = roundFixed(state.change);

    templates.render('paymentListTemplate', state, viewElements.paymentListView);

    updateConfirmButton(state);
}

export function setPaymentInputValue(value) {
    if (viewElements.paymentInput) {
        viewElements.paymentInput.value = value;
    }
}

export function getPaymentInputValue() {
    return parseFloat(viewElements.paymentInput?.value ?? 0) || 0;
}

export function getPaymentData({code, description}) {
    return {
        amount: getPaymentInputValue(),
        method: code,
        description: description
    };
}

export function togglePaymentModal() {
    Modals.toggleModal('checkoutModal');
}

export function enableConfirmButton() {
    document.getElementById('orderSaveButton').disabled = false;
}

export function disableConfirmButton() {
    document.getElementById('orderSaveButton').disabled = true;
}

function updateConfirmButton(state) {
    viewElements.confirmButton.disabled = !(state.paymentsTotal >= state.total && state.total !== 0);
}
