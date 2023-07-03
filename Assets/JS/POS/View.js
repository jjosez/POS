import Modals from "./components/Modals.js";
import Templates from "./components/Templates.js";
import {getElement} from "./Core.js";

export const cart = () => {
    return Object.freeze(new Cart());
}

export const checkout = () => {
    return Object.freeze(new Checkout());
}

export const modals = () => {
    return Modals;
}

export const templates = () => {
    return Templates;
}

const cartElements = {
    cartTotalLabel: getElement('cartTotal'),
    orderDiscountAmountLabel: getElement('orderDiscountAmountLabel'),
    orderDiscountAmountInput: getElement('orderDiscountAmountInput'),
    orderHoldButton: getElement('orderHoldButton'),
    orderItemsNumberLabel: getElement('orderItemsNumber'),
    orderNetoLabel: getElement('orderTotalNet'),
    orderSubtotalLabel: getElement('orderSubtotal'),
    orderTaxesLabel: getElement('orderTaxes'),
    orderTotalLabel: getElement('orderTotal'),
    productQuantityInput: getElement('productQuantityInput')
}

class Cart {
    cartTotalLabel = () => cartElements['cartTotalLabel'];
    orderDiscountAmountLabel = () => cartElements['orderDiscountAmountLabel'];
    orderDiscountAmountInput = () => cartElements['orderDiscountAmountInput'];
    orderHoldButton = () => cartElements['orderHoldButton'];
    orderItemsNumberLabel = () => cartElements['orderItemsNumberLabel'];
    orderNetoLabel = () => cartElements['orderNetoLabel'];
    orderSubtotalLabel = () => cartElements['orderSubtotalLabel'];
    orderTaxesLabel = () => cartElements['orderTaxesLabel'];
    orderTotalLabel = () => cartElements['orderTotalLabel'];
    productQuantityInput = () => cartElements['productQuantityInput'];
}

const checkoutElements = {
    'confirmOrderButton': getElement('orderSaveButton'),
    'changeAmountLabel': getElement('checkoutChangeAmount'),
    'tenderedAmountLabel': getElement('checkoutTenderedAmount'),
    'totalAmountLabel': getElement('checkoutTotal'),
    'paymentModalButton': document.querySelectorAll('.payment-modal-btn'),
    'paymentAmountButton': document.querySelectorAll('.payment-add-btn'),
    'paymentApplyButton': getElement('paymentApplyButton'),
    'paymentApplyInput': getElement('paymentApplyInput'),
}

class Checkout {
    confirmOrderButton = () => checkoutElements['confirmOrderButton'];
    changeAmountLabel = () => checkoutElements['changeAmountLabel'];
    tenderedAmountLabel = () => checkoutElements['tenderedAmountLabel'];
    totalAmountLabel = () => checkoutElements['totalAmountLabel'];
    paymentModalButton = () => checkoutElements['paymentModalButton'];
    paymentAmountButton = () => checkoutElements['paymentAmountButton'];
    paymentApplyButton = () => checkoutElements['paymentApplyButton'];
    paymentAmountInput = () => checkoutElements['paymentApplyInput'];
}
