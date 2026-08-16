import templates from './TemplateManger.js';
import Modals from '../components/Modals.js';
import {roundFixed} from '../Money.js';

const elements = {
    appliedPayments: document.getElementById('checkoutAppliedPayments'),
    balanceLabel: document.getElementById('checkoutBalanceLabel'),
    changeAmount: document.getElementById('checkoutChangeAmount'),
    changeLabel: document.getElementById('checkoutChangeLabel'),
    checkoutTotal: document.getElementById('checkoutTotal'),
    confirmButton: document.getElementById('orderSaveButton'),
    moreButton: document.querySelector('[data-action="checkout:payment:more"]'),
    moreMethods: document.getElementById('checkoutMoreMethods'),
    paymentInput: document.getElementById('paymentApplyInput'),
    paymentMethods: document.querySelectorAll('[data-payment-method]'),
    tenderedAmount: document.getElementById('checkoutTenderedAmount'),
};

let isProcessing = false;

export function render(model, previewAmount = 0) {
    const state = model.getState();
    const preview = Math.max(0, Number(previewAmount) || 0);
    const previewTotal = state.paymentsTotal + preview;
    const previewBalance = state.total - previewTotal;
    const payments = state.payments.map(payment => ({
        ...payment,
        formattedAmount: roundFixed(payment.amount),
    }));

    elements.checkoutTotal.textContent = roundFixed(state.total);
    elements.tenderedAmount.textContent = roundFixed(previewTotal);
    elements.changeAmount.textContent = roundFixed(Math.abs(previewBalance));

    const hasChange = previewBalance <= 0;
    elements.changeLabel.classList.toggle('hidden', !hasChange);
    elements.balanceLabel.classList.toggle('hidden', hasChange);
    elements.changeAmount.parentElement.classList.toggle('text-emerald-600', hasChange);
    elements.changeAmount.parentElement.classList.toggle('text-amber-600', !hasChange);

    elements.appliedPayments.classList.toggle('hidden', payments.length === 0);
    templates.render('payment:list:template', {payments}, 'payment:list:view');

    renderPaymentMethods(state.payments);
    updateConfirmButton(state);
}

export function setPaymentInputValue(value) {
    elements.paymentInput.value = roundFixed(Math.max(0, Number(value) || 0));
}

export function getPaymentInputValue() {
    return Math.max(0, parseFloat(elements.paymentInput?.value ?? 0) || 0);
}

export function getPaymentInput() {
    return elements.paymentInput;
}

export function showPaymentModal() {
    hideMoreMethods();
    Modals.showModal('checkout:modal');
}

export function hidePaymentModal() {
    Modals.hideModal('checkout:modal');
    hideMoreMethods();
}

export function isPaymentModalVisible() {
    return Modals.checkoutModal().isVisible;
}

export function focusPaymentInput() {
    requestAnimationFrame(() => {
        elements.paymentInput?.focus();
        elements.paymentInput?.select();
    });
}

export function toggleMoreMethods() {
    if (!elements.moreMethods || !elements.moreButton) return;

    const isHidden = elements.moreMethods.classList.toggle('hidden');
    elements.moreMethods.classList.toggle('grid', !isHidden);
    elements.moreButton.setAttribute('aria-expanded', String(!isHidden));
}

export function setProcessing(processing) {
    isProcessing = processing;
    elements.confirmButton.setAttribute('aria-busy', String(processing));
    elements.confirmButton.disabled = processing || elements.confirmButton.disabled;
}

function hideMoreMethods() {
    if (!elements.moreMethods || !elements.moreButton) return;

    elements.moreMethods.classList.add('hidden');
    elements.moreMethods.classList.remove('grid');
    elements.moreButton.setAttribute('aria-expanded', 'false');
}

function renderPaymentMethods(payments) {
    const selectedMethods = new Set(payments.map(payment => payment.method));

    elements.paymentMethods.forEach(button => {
        const isSelected = selectedMethods.has(button.dataset.code);
        button.setAttribute('aria-pressed', String(isSelected));
        button.classList.toggle('border-blue-500', isSelected);
        button.classList.toggle('bg-blue-50', isSelected);
        button.classList.toggle('text-blue-700', isSelected);
        const check = button.querySelector('[data-payment-check]');
        if (check) check.hidden = !isSelected;
    });
}

function updateConfirmButton(state) {
    elements.confirmButton.disabled = isProcessing
        || state.total === 0
        || state.paymentsTotal < state.total;
}
