import CheckoutModel from '../models/CheckoutModel.js';
import * as CheckoutView from '../views/CheckoutView.js';
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
        dispatcher.register('deletePaymentAction', this.deletePayment);
        dispatcher.register('orderSaveAction', this.hideCheckoutModal);
        dispatcher.register('recalculatePaymentAction', this.recalculatePayment);
        dispatcher.register('setPaymentAction', this.setPayment);
        dispatcher.register('showCheckoutModalAction', this.showCheckoutModal);

        EventManager.on('onCheckoutUpdate', () => {
            if (isCheckoutVisible) CheckoutView.render(CheckoutModel);
        });

        EventManager.on('onCartUpdate', ({doc}) => {
            CheckoutModel.updateTotal(doc.total);
        });

        EventManager.on('onOrderComplete', () => {
            CheckoutModel.clear();
            CheckoutView.render(CheckoutModel);
        });
    }
}

export default CheckoutController;
