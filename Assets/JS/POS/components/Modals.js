import {getElement} from "../Core.js";

const backdrop = new BackDropElement();

let instance;

let modals = {
    closeSession: new ModalElement('closeSessionModal'),
    customerSearch: new ModalElement('customerSearchModal'),
    documentType: new ModalElement('documentTypeModal'),

    holdOrders: new ModalElement('holdOrdersModal'),
    lastOrders: new ModalElement('lastOrdersModal'),

    paymentDetail: new ModalElement('paymentModal'),

    productEdit: new ModalElement('productEditModal'),
    productImages: new ModalElement('productImagesModal'),
    productQuantityEdit: new ModalElement('productQuantityEditModal'),
    productStockDetail: new ModalElement('stockDetailModal'),
}

function BackDropElement() {
    this.element = document.createElement('div');
    this.element.classList.add('modal-backdrop');
}

BackDropElement.prototype.show = function () {
    document.querySelector('body').append(this.element);
}

BackDropElement.prototype.hide = function () {
    document.querySelector('.modal-backdrop').remove();
}

function ModalElement(id) {
    this.element = getElement(id);
}

ModalElement.prototype.show = function () {
    if (!this.element) return;

    this.element.classList.toggle("flex");
    this.element.classList.toggle("hidden");

    backdrop.show();
}

ModalElement.prototype.hide = function () {
    if (!this.element) return;

    this.element.classList.toggle("flex");

    if (this.element.classList.toggle("hidden")) {
        backdrop.hide();
    }
}

class Modals {
    constructor() {
        if (instance) throw new Error("New instance cannot be created!!");

        instance = this;
    }

    backdrop() {
        return backdrop;
    }

    /**
     * @param {HTMLElement} element
     */
    toggleModal = element => {
        if (!element) return;

        element.classList.toggle("flex");

        if (element.classList.toggle("hidden")) {
            backdrop.hide();
            return;
        }

        backdrop.show();
    };

    documentTypeModal = () => modals['documentType'];
    closeSessionModal = () => modals['closeSession'];
    customerSearchModal = () => modals['customerSearch'];
    lastOrdersModal = () => modals['lastOrders'];
    pausedOrdersModal = () => modals['holdOrders'];
    stockDetailModal = () => modals['productStockDetail'];
    paymentModal = () => modals['paymentDetail'];
    productEditModal = () => modals['productEdit'];
    productImagesModal = () => modals['productImages'];
    productQuantityEditModal = () => modals['productQuantityEdit'];
}

let modalsInstance = Object.freeze(new Modals());

export default modalsInstance;
