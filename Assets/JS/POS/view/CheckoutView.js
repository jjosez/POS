/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

import Modals from "../components/Modals.js";
import templates from "../views/TemplateManger.js";

const viewElements = {
    'confirmOrderButton': document.getElementById('orderSaveButton'),
    'changeAmountLabel': document.getElementById('checkoutChangeAmount'),
    'tenderedAmountLabel': document.getElementById('checkoutTenderedAmount'),
    'totalAmountLabel': document.getElementById('checkoutTotal'),
    'paymentApplyButton': document.getElementById('paymentApplyButton'),
    'paymentApplyInput': document.getElementById('paymentApplyInput'),
}

class CheckoutView {
    confirmOrderButton = () => viewElements['confirmOrderButton'];
    changeAmountLabel = () => viewElements['changeAmountLabel'];
    tenderedAmountLabel = () => viewElements['tenderedAmountLabel'];
    totalAmountLabel = () => viewElements['totalAmountLabel'];
    paymentApplyButton = () => viewElements['paymentApplyButton'];
    paymentAmountInput = () => viewElements['paymentApplyInput'];

    enableConfirmButton = (enable = true) => {
        this.confirmOrderButton().disabled = !enable;
    };

    getCurrentPaymentValue = () => parseFloat(this.paymentAmountInput().value) || 0;

    getCurrentPaymentData = ({code, description}) => ({
        amount: this.paymentAmountInput().value,
        description: description,
        method: code
    });

    updateView = (data) => {
        this.totalAmountLabel().textContent = data.total;
        this.tenderedAmountLabel().textContent = data.getPaymentsTotal();
        this.changeAmountLabel().textContent = data.change;

        templates.render('paymentListTemplate', data, 'paymentListTemplateView');

        this.enableConfirmButton(data.change >= 0 && data.total !== 0);
    };

    showPaymentModal(data = {}) {
        this.paymentAmountInput().dataset.method = data.code;
        this.paymentAmountInput().dataset.description = data.description;

        this.togglePaymentModal();
    }

    togglePaymentModal() {
        Modals.toggleModal('paymentModal');
    }

}

const checkoutViewInstance = () => Object.freeze(new CheckoutView());
export default checkoutViewInstance();
