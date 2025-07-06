import templates from "../views/TemplateManger.js";
import Modals from "../components/Modals.js";
export function render(model) {
    const state = model.getState();

    document.getElementById('checkoutTotal').textContent = state.total.toFixed(2);
    document.getElementById('checkoutTenderedAmount').textContent = model.getPaymentsTotal().toFixed(2);
    document.getElementById('checkoutChangeAmount').textContent = state.change.toFixed(2);

    templates.render('paymentListTemplate', state, 'paymentListTemplateView');

    if (state.paymentsTotal >= state.total && state.total !== 0) {
        enableConfirmButton();
    } else {
        disableConfirmButton();
    }
}

export function setPaymentInputValue(value) {
    document.getElementById('paymentApplyInput').value = value;
}

export function getPaymentInputValue() {
    return parseFloat(document.getElementById('paymentApplyInput').value) || 0;
}

export function getPaymentData({code, description}) {
    return {
        amount: getPaymentInputValue(),
        method: code,
        description: description
    };
}

export function showPaymentModal({code, description}) {
    const input = document.getElementById('paymentApplyInput');
    input.dataset.method = code;
    input.dataset.description = description;

    togglePaymentModal();
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
