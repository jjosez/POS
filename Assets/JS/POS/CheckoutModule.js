/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import CheckoutClass from "./CheckoutClass.js";
import {checkoutView, cartView} from "./UI.js";

export const Checkout = new CheckoutClass('01');

function applyPayment() {
    Checkout.setPayment(checkoutView().paymentInput.value, checkoutView().paymentInput.dataset.method);
    checkoutView().togglePaymentModal();
}

function recalculatePaymentAmount() {
    if (this.dataset.value === 'balance') {
        checkoutView().paymentInput.value = Checkout.getOutstandingBalance();
    } else {
        let value = parseFloat(checkoutView().paymentInput.value) || 0;
        value += parseFloat(this.dataset.value) || 0;
        checkoutView().paymentInput.value = value;
    }
}

function showPaymentModal() {
    checkoutView().showPaymentModal(this.dataset.code);
}

function updateCheckoutView() {
    if (Checkout.change >= 0) {
        checkoutView().confirmButton.removeAttribute('disabled');
        checkoutView().confirmButton.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        checkoutView().confirmButton.setAttribute('disabled', 'disabled');
    }

    checkoutView().updateTotals(Checkout);
    checkoutView().updatePaymentList(Checkout);
}

function updateOrderDiscount() {
    //Cart.setDiscountPercent(this.value);
}

function updateViewTotals(data) {
    cartView().updateTotals(data.detail);
    Checkout.total = data.detail.doc.total;

    if (data.detail.doc.total === 0) {
        Checkout.clear();
    }
}

document.addEventListener('updateCartViewEvent', updateViewTotals);
document.addEventListener('updateCheckout', updateCheckoutView);
cartView().discountPercent.addEventListener('focusout', updateOrderDiscount);
checkoutView().paymentApplyButton.addEventListener('click', applyPayment);

checkoutView().paymentAmounButton.forEach(element => {
    element.addEventListener('click', recalculatePaymentAmount);
});
checkoutView().paymentModalButton.forEach(element => {
    element.addEventListener('click', showPaymentModal);
});
