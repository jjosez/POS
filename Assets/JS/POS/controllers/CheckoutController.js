import * as CheckoutView from '../views/CheckoutView.js';
import CheckoutModel from '../models/CheckoutModel.js';
import dispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';

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
            if (CheckoutView.isPaymentModalVisible()) {
                CheckoutView.render(CheckoutModel, CheckoutView.getPaymentInputValue());
            }
        });

        EventManager.on('event:cart:updated', ({doc}) => {
            CheckoutModel.updateTotal(doc.total);
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
