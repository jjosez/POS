// Legacy checkout view kept for reference.
import templates from "../views/TemplateManger.js";
import Modals from "../components/Modals.js";
import {roundFixed} from "../Money.js";

const elements = {
    checkoutTotal: document.getElementById('checkoutTotal'),
    tenderedAmount: document.getElementById('checkoutTenderedAmount'),
    changeAmount: document.getElementById('checkoutChangeAmount'),

    paymentInput: document.getElementById('paymentApplyInput'),
    confirmButton: document.getElementById('orderSaveButton'),
};

export function render(model) {
    const state = model.getState();
    
    elements.checkoutTotal.textContent = roundFixed(state.total);
    elements.tenderedAmount.textContent = roundFixed(model.getPaymentsTotal());
    elements.changeAmount.textContent = roundFixed(state.change);

    templates.render('payment:list:template', state, 'payment:list:view');

    updateConfirmButton(state, elements.confirmButton);
}

export function renderCartSummary(cartData) {
    templates.render('checkout:cart:template', cartData, 'checkout:cart:summary');
}

export function setPaymentInputValue(value) {
    elements.paymentInput.value = value;
}

export function getPaymentInputValue() {
    return parseFloat(elements.paymentInput?.value ?? 0) || 0;
}

export function getPaymentData({code, description}) {
    return {
        amount: getPaymentInputValue(),
        method: code,
        description: description
    };
}

export function togglePaymentModal() {
    Modals.toggleModal('checkout:modal');
}

export function toggleCheckoutBlock() {
    const checkoutView = document.getElementById('checkoutMainView');
    const cartView = document.getElementById('cartPane');

    if (checkoutView && cartView) {
        checkoutView.classList.toggle('hidden');
        cartView.classList.toggle('hidden');
    }
}

function updateConfirmButton(state) {
    const button = elements.confirmButton;
    if (button) {
        button.disabled = !(state.paymentsTotal >= state.total && state.total !== 0);
    }
}
