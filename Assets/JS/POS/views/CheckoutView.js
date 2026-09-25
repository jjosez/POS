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
    accountLabel: document.getElementById('checkoutAccountLabel'),
    accountMessage: document.getElementById('checkoutAccountMessage'),
    availableCredit: document.getElementById('checkoutAvailableCredit'),
    accountLoading: document.getElementById('checkoutAccountLoading'),
};

let isProcessing = false;
const checkoutBaseTitle = elements.title?.textContent.trim() ?? '';

/**
 * @param {CheckoutModel} model
 */
export function render(model) {
    const state = model.getState();

    const onAccount = state.paymentPolicy === 'customer-account';
    const optional = state.paymentPolicy === 'optional';

    const payments = state.payments.map(payment => ({
        ...payment,
        formattedAmount: roundFixed(payment.amount),
    }));

    // Totales
    const remaining = Math.max(
        0,
        state.total - state.collectedAmount
    );

    const hasChange = state.change > 0;
    const hasRemaining = remaining > 0.005;

    elements.checkoutTotal.textContent = roundFixed(state.total);

    elements.changeAmount.textContent = roundFixed(
        hasChange ? state.change : remaining
    );

    // Saldo pendiente o cambio
    elements.balanceRow.classList.toggle(
        'hidden',
        !hasChange && (onAccount || optional || !hasRemaining)
    );

    elements.changeLabel.classList.toggle('hidden', !hasChange);
    elements.balanceLabel.classList.toggle('hidden', hasChange);

    elements.changeAmount.parentElement.classList.toggle(
        'text-emerald-600',
        hasChange
    );

    elements.changeAmount.parentElement.classList.toggle(
        'text-amber-600',
        !hasChange
    );

    // Pagos registrados
    elements.appliedPayments.classList.toggle(
        'hidden',
        payments.length === 0
    );

    templates.render(
        'payment:list:template',
        {payments},
        'payment:list:view'
    );

    // Resumen inferior
    const showAccountSummary =
        (onAccount || optional) && hasRemaining;

    elements.accountSummary.classList.toggle(
        'hidden',
        !showAccountSummary
    );

    elements.accountAmount.textContent = roundFixed(remaining);

    // Etiqueta según la política
    elements.accountLabel.textContent = optional
        ? elements.accountLabel.dataset.optional
        : elements.accountLabel.dataset.account;

    // Estado de consulta
    const checking = showAccountSummary && state.accountChecking;

    elements.accountLoading.classList.toggle(
        'hidden',
        !checking
    );

    // Mensaje traducido por el backend
    elements.accountMessage.textContent =
        showAccountSummary && !checking
            ? state.accountMessage ?? ''
            : '';

    // Crédito disponible
    const result = onAccount && showAccountSummary && !checking
        ? state.accountResult
        : null;

    const showCredit = result !== null
        && ['approved', 'insufficient-credit'].includes(result.status)
        && Number.isFinite(result.available_credit);

    elements.availableCredit.parentElement.classList.toggle(
        'hidden',
        !showCredit
    );

    if (showCredit) {
        elements.availableCredit.textContent = roundFixed(
            result.available_credit
        );
    }

    // Total cobrado
    elements.collectedAmount.textContent = roundFixed(
        state.collectedAmount
    );

    // Botón de confirmación
    elements.confirmLabel.textContent = onAccount || optional
        ? elements.confirmLabel.dataset.finalize
        : elements.confirmLabel.dataset.charge;

    // Mantener los controles actuales
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
