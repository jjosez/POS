import {getElement} from "../Core.js";

const backdrop = new BackDropElement();

let instance;

let modals = {
    closeSession: new ModalElement('closeSessionModal'),
    customerSearch: new ModalElement('customerSearchModal'),
    documentType: new ModalElement('documentTypeModal'),
    loadingModal: new ModalElement('loadingModal'),

    holdOrders: new ModalElement('holdOrdersModal'),
    lastOrders: new ModalElement('lastOrdersModal'),

    paymentDetail: new ModalElement('paymentModal'),
    printModal: new ModalElement('printModal'),

    productEditModal: new ModalElement('productEditModal'),
    productImages: new ModalElement('productImagesModal'),
    productQuantityEdit: new ModalElement('productQuantityEditModal'),
    productStockDetail: new ModalElement('stockDetailModal'),

    checkoutModal: new ModalElement('checkoutModal'),
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
    this.isVisible = false;
}

ModalElement.prototype.show = function () {
    if (!this.element || this.isVisible) return;

    this.element.classList.remove("hidden");
    this.element.classList.add("flex");

    backdrop.show();
    this.isVisible = true;
}

ModalElement.prototype.hide = function () {
    if (!this.element || !this.isVisible) return;

    this.element.classList.remove("flex");
    this.element.classList.add("hidden");

    backdrop.hide();
    this.isVisible = false;
}

class Modals {
    modalCache = {};

    /**
     * @param {HTMLElement} element
     */

    /*toggleModal = (element) => {
        if (!element) return;

        if (element.classList.contains("hidden")) {
            element.classList.remove("hidden");
            element.classList.add("flex");

            backdrop.show();
        } else {
            element.classList.remove("flex");
            element.classList.add("hidden");

            backdrop.hide();
        }
    };*/
    function

    constructor() {
        if (instance) throw new Error("New instance cannot be created!!");

        instance = this;

        document.addEventListener('click', this._modalToggleEventHandler);
    }

    _modalToggleEventHandler = event => {
        const target = event.target;

        // Verificamos que el atributo data-toggle sea "modal"
        if (target.dataset.toggle === 'modal') {
            const modalId = target.dataset.target;
            this.toggleModal(modalId);

            event.stopPropagation();
        }
    };

    backdrop() {
        return backdrop;
    }

    toggleModal = (modalId) => {
        let modal = this.modalCache[modalId] || modals[modalId];

        // If modal does not exist, create it and add to cache
        if (!modal) {
            modal = new ModalElement(modalId);
            this.modalCache[modalId] = modal;
        }

        // Toggle visibility
        modal.isVisible ? modal.hide() : modal.show();
    };

    documentTypeModal = () => modals['documentType'];
    closeSessionModal = () => modals['closeSession'];
    customerSearchModal = () => modals['customerSearch'];
    lastOrdersModal = () => modals['lastOrders'];
    loadingModal = () => modals['loadingModal'];
    pausedOrdersModal = () => modals['holdOrders'];
    stockDetailModal = () => modals['productStockDetail'];
    paymentModal = () => modals['paymentDetail'];
    printModal = () => modals['printModal'];
    productEditModal = () => modals['productEditModal'];
    productImagesModal = () => modals['productImages'];
    productQuantityEditModal = () => modals['productQuantityEdit'];
}

const modalsInstance = Object.freeze(new Modals());

export default modalsInstance;
