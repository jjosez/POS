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
    if (!CheckoutView.isPaymentModalVisible() || state.customerAccountAmount <= 0 || state.requiresCustomer) return;
    const revision = CheckoutModel.accountRevision;
    accountTimer = setTimeout(async () => {
        let result = {customer_account: false, status: 'error', available_credit: 0};
        try {
            const response = await checkCustomerAccount(CartController.getState(), state.payments);
            if (response.status === 'success'
                && response.data.policy === state.paymentPolicy
                && Number(response.data.requested_amount) === state.customerAccountAmount) {
                result = response.data.customer_account;
            }
        } catch (_) {
            // Keep failure inside the account section; payment controls remain usable.
        }
        CheckoutModel.setAccountResult(result, revision);
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
