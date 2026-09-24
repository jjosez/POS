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
    balanceRow: document.getElementById('checkoutBalanceRow'),
    title: document.getElementById('checkoutTitle'),
    accountSummary: document.getElementById('checkoutAccountSummary'),
    accountAmount: document.getElementById('checkoutAccountAmount'),
    collectedAmount: document.getElementById('checkoutCollectedAmount'),
    confirmLabel: document.getElementById('checkoutConfirmLabel'),
};

let isProcessing = false;
const checkoutBaseTitle = elements.title?.textContent.trim() ?? '';

/**
 * @param {CheckoutModel} model
 */
export function render(model) {
    const state = model.getState();
    const onAccount = state.paymentPolicy === 'customer-account';
    const payments = state.payments.map(payment => ({
        ...payment,
        formattedAmount: roundFixed(payment.amount),
    }));

    elements.checkoutTotal.textContent = roundFixed(state.total);
    const remaining = Math.max(0, state.total - state.collectedAmount);
    elements.changeAmount.textContent = roundFixed(state.change > 0 ? state.change : remaining);

    const hasChange = state.change > 0;
    elements.balanceRow.classList.toggle('hidden', !hasChange && (onAccount || remaining === 0));
    elements.changeLabel.classList.toggle('hidden', !hasChange);
    elements.balanceLabel.classList.toggle('hidden', hasChange);
    elements.changeAmount.parentElement.classList.toggle('text-emerald-600', hasChange);
    elements.changeAmount.parentElement.classList.toggle('text-amber-600', !hasChange);

    elements.appliedPayments.classList.toggle('hidden', payments.length === 0);
    elements.accountSummary.classList.toggle('hidden', !onAccount);
    elements.accountAmount.textContent = roundFixed(state.customerAccountAmount);
    elements.collectedAmount.textContent = roundFixed(state.collectedAmount);
    elements.confirmLabel.textContent = onAccount
        ? elements.confirmLabel.dataset.finalize
        : elements.confirmLabel.dataset.charge;
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
    if (!elements.moreMethods || !elements.moreButton) {
        return;
    }

    const isHidden = elements.moreMethods.classList.toggle('hidden');
    elements.moreMethods.classList.toggle('grid', !isHidden);
    elements.moreButton.setAttribute('aria-expanded', String(!isHidden));
}

export function setProcessing(processing) {
    isProcessing = processing;
    elements.confirmButton.setAttribute('aria-busy', String(processing));
    elements.confirmButton.disabled = processing || elements.confirmButton.disabled;
}

export function updateTitle(title = '') {
    if (!elements.title) {
        return;
    }

    elements.title.textContent = title
        ? `${checkoutBaseTitle} - ${title}`
        : checkoutBaseTitle;
}

function hideMoreMethods() {
    if (!elements.moreMethods || !elements.moreButton) {
        return;
    }

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

        if (check) {
            check.hidden = !isSelected;
        }
    });
}

function updateConfirmButton(state) {
    elements.confirmButton.disabled = isProcessing
        || !state.canFinalize;
}
