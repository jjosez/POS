import * as CheckoutView from '../views/CheckoutView.js';
import CheckoutModel from '../models/CheckoutModel.js';
import dispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';
import CartController from './CartController.js';
import {checkCustomerAccount} from '../Core.js';

let accountTimer;

function refreshAccount() {
    clearTimeout(accountTimer);

    const state = CheckoutModel.getState();
    const onAccount = state.paymentPolicy === 'customer-account';
    const optional = state.paymentPolicy === 'optional';
    const remaining = Math.max(0, state.total - state.collectedAmount);

    // Consultar solamente cuando exista un importe pendiente
    // y la política permita finalizar sin cobrarlo.
    if (
        !CheckoutView.isPaymentModalVisible()
        || remaining <= 0
        || (!onAccount && !optional)
    ) {
        return;
    }

    const revision = CheckoutModel.accountRevision;
    const requested = onAccount ? state.customerAccountAmount : 0;

    // Mostrar el spinner e invalidar resultados anteriores.
    CheckoutModel.beginAccountCheck(revision);

    // Conservar los pagos correspondientes a esta revisión.
    const payments = state.payments.map(payment => ({...payment}));

    accountTimer = setTimeout(async () => {
        if (
            revision !== CheckoutModel.accountRevision
            || !CheckoutView.isPaymentModalVisible()
        ) {
            return;
        }

        try {
            const response = await checkCustomerAccount(
                CartController.getState(),
                payments
            );

            const data = response?.data;

            // Validar la respuesta y el importe solicitado.
            const decimals = Number(AppSettings.currency.decimals ?? 2);
            const factor = 10 ** decimals;
            const responseAmount = Number(data?.requested_amount);

            if (
                response?.status !== 'success'
                || data?.policy !== state.paymentPolicy
                || !Number.isFinite(responseAmount)
                || Math.round(responseAmount * factor)
                !== Math.round(requested * factor)
            ) {
                CheckoutModel.setAccountResult(null, revision);
                return;
            }

            // OPTIONAL no necesita autorización de crédito.
            const result = onAccount
                ? data.customer_account ?? null
                : null;

            CheckoutModel.setAccountResult(
                result,
                revision,
                data.message ?? result?.message ?? ''
            );

        } catch (error) {
            // La consulta falló. No autorizar crédito.
            CheckoutModel.setAccountResult(null, revision);

            // Utilizar aquí el manejo global de errores
            // del POS si necesitas mostrar un aviso.
        }
    }, 250);
}

const CheckoutController = {
    inputHandler: null,

    deletePayment(el) {
        CheckoutModel.deletePayment(Number(el.dataset.index));
    },

    recalculatePayment(el) {
        if (el.dataset.value !== 'balance') return;

        CheckoutView.setPaymentInputValue(CheckoutModel.getOutstandingBalance());
        CheckoutView.render(CheckoutModel, CheckoutView.getPaymentInputValue());
        CheckoutView.focusPaymentInput();
    },

    setPayment(el) {
        let amount = CheckoutView.getPaymentInputValue();
        if (amount === 0) {
            amount = Math.max(0, CheckoutModel.getOutstandingBalance());
        }

        CheckoutView.setPaymentInputValue(0);
        CheckoutModel.setPayment({
            amount,
            method: el.dataset.code,
            description: el.dataset.description,
        });
        CheckoutView.focusPaymentInput();
    },

    showCheckoutModal() {
        CheckoutView.showPaymentModal();
        CheckoutModel.updateCheckoutEvent();
        CheckoutView.render(CheckoutModel);
        CheckoutView.focusPaymentInput();
    },

    hideCheckoutModal() {
        CheckoutView.hidePaymentModal();
    },

    getState() {
        return CheckoutModel.getState();
    },

    init() {
        dispatcher.register('checkout:payment:delete', this.deletePayment);
        dispatcher.register('checkout:payment:recalc', this.recalculatePayment);
        dispatcher.register('checkout:payment:add', this.setPayment);
        dispatcher.register('checkout:payment:more', CheckoutView.toggleMoreMethods);
        dispatcher.register('checkout:show', this.showCheckoutModal);
        dispatcher.register('checkout:hide', this.hideCheckoutModal);

        EventManager.on('keyboard:checkout:show', this.showCheckoutModal);
        EventManager.on('checkout:processing', processing => {
            CheckoutView.setProcessing(processing);
            if (CheckoutView.isPaymentModalVisible()) {
                CheckoutView.render(CheckoutModel, CheckoutView.getPaymentInputValue());
            }
        });

        EventManager.on('checkout:update', () => {
            refreshAccount();
            if (CheckoutView.isPaymentModalVisible()) {
                CheckoutView.render(CheckoutModel, CheckoutView.getPaymentInputValue());
            }
        });

        EventManager.on('checkout:account:updated', () => {
            if (CheckoutView.isPaymentModalVisible()) CheckoutView.render(CheckoutModel);
        });

        EventManager.on('event:cart:updated', ({doc}) => {
            CheckoutModel.updateDocument(doc);
            CheckoutView.updateTitle(doc.title);
        });

        EventManager.on('event:order:completed', () => {
            if (CheckoutView.isPaymentModalVisible()) {
                CheckoutView.hidePaymentModal();
            }
            CheckoutView.setPaymentInputValue(0);
            CheckoutModel.clear();
        });

        const input = CheckoutView.getPaymentInput();
        if (input && !this.inputHandler) {
            this.inputHandler = () => CheckoutView.render(CheckoutModel, CheckoutView.getPaymentInputValue());
            input.addEventListener('input', this.inputHandler);
        }
    },
};

export default CheckoutController;
