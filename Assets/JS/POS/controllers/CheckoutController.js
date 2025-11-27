import * as CheckoutView from '../views/CheckoutView.js';
import CheckoutModel from '../models/CheckoutModel.js';
import dispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';

let isCheckoutVisible = false;

const CheckoutController = {
    deletePayment(el) {
        const {index} = el.dataset;
        CheckoutModel.deletePayment(index);
    },

    recalculatePayment(el) {
        const {value} = el.dataset;
        if (value === 'balance') {
            CheckoutView.setPaymentInputValue(CheckoutModel.getOutstandingBalance());
        } else {
            const current = CheckoutView.getPaymentInputValue();
            CheckoutView.setPaymentInputValue(current + parseFloat(value) || 0);
        }
    },

    setPayment(el) {
        const code = el.dataset.code;
        const description = el.dataset.description;

        let amount = CheckoutView.getPaymentInputValue();

        if (!amount || amount === 0) {
            amount = CheckoutModel.getOutstandingBalance();
        }

        const paymentData = {
            amount: amount,
            method: code,
            description: description
        };
        CheckoutModel.setPayment(paymentData);

        CheckoutView.setPaymentInputValue(0);
    },

    handleConfirmOrder(el) {
        console.log('💵 Confirmar orden');
    },

    showCheckoutModal() {
        isCheckoutVisible = true;

        CheckoutView.togglePaymentModal();
        CheckoutView.render(CheckoutModel);
    },

    hideCheckoutModal() {
        isCheckoutVisible = false;
        CheckoutView.togglePaymentModal();
    },

    getState() {
        return CheckoutModel.getState();
    },

    init() {
        dispatcher.register('checkout:payment:delete', this.deletePayment);
        dispatcher.register('checkout:payment:recalc', this.recalculatePayment);
        dispatcher.register('checkout:payment:add', this.setPayment);
        dispatcher.register('checkout:show', this.showCheckoutModal);
        dispatcher.register('order:save', this.hideCheckoutModal);

        EventManager.on('checkout:update', () => {
            if (isCheckoutVisible) CheckoutView.render(CheckoutModel);
        });

        EventManager.on('cart:update', ({doc}) => {
            CheckoutModel.updateTotal(doc.total);
        });

        EventManager.on('order:completed', () => {
            CheckoutModel.clear();
            CheckoutView.render(CheckoutModel);
        });
    }
}

export default CheckoutController;
