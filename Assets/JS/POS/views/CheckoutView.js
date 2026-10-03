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
    balanceRow: document.getElementById('checkoutBalanceRow'),
    title: document.getElementById('checkoutTitle'),
    pendingSummary: document.getElementById('checkoutPendingSummary'),
    pendingAmount: document.getElementById('checkoutPendingAmount'),
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

    const optional = state.paymentPolicy === 'optional';

    const mergedList = [
        ...state.payments.map(payment => ({
            ...payment,
            formattedAmount: roundFixed(payment.amount),
            kind: 'native'
        })),
        ...state.paymentSources.map(source => ({
            description: source.label,
            amount: source.amount,
            formattedAmount: roundFixed(source.amount),
            kind: 'source',
            code: source.code
        }))
    ];
    const payments = mergedList;

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
        !hasChange && (optional || !hasRemaining)
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
    const showPendingSummary = optional && hasRemaining;

    elements.pendingSummary.classList.toggle(
        'hidden',
        !showPendingSummary
    );

    elements.pendingAmount.textContent = roundFixed(remaining);

    // Total cobrado
    elements.collectedAmount.textContent = roundFixed(
        state.collectedAmount
    );

    // Botón de confirmación
    elements.confirmLabel.textContent = optional
        ? elements.confirmLabel.dataset.finalize
        : elements.confirmLabel.dataset.charge;

    // Mantener los controles actuales
    renderPaymentMethods(state.payments, state.paymentSources);
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

function renderPaymentMethods(payments, sources) {
    const selectedNative = new Set(
        payments.filter(p => p.kind === 'native').map(payment => payment.method)
    );
    const selectedSources = new Set(
        sources.map(source => source.code)
    );

    document.querySelectorAll('[data-payment-method], [data-payment-kind="source"]').forEach(button => {
        const kind = button.dataset.paymentKind ?? 'native';
        const key = button.dataset.code;
        const selected = kind === 'source'
            ? selectedSources.has(key)
            : selectedNative.has(key);

        button.setAttribute('aria-pressed', String(selected));
        button.classList.toggle('border-blue-500', selected);
        button.classList.toggle('bg-blue-50', selected);
        button.classList.toggle('text-blue-700', selected);
        const check = button.querySelector(kind === 'source'
            ? '[data-payment-source-check]'
            : '[data-payment-check]');

        if (check) {
            check.hidden = !selected;
        }
        if (kind === 'source') {
            button.disabled = button.dataset.available === 'false';
        }
    });
}

function updateConfirmButton(state) {
    elements.confirmButton.disabled = isProcessing
        || !state.canFinalize;
}
