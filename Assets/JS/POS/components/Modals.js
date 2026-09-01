import {getElement} from "../Core.js";

function BackDropElement() {
    this.element = document.createElement('div');
    this.element.classList.add('modal-backdrop');
    this.visibleCount = 0;
}

BackDropElement.prototype.show = function () {
    if (this.visibleCount === 0) {
        document.body.append(this.element);
    }
    this.visibleCount++;
}

BackDropElement.prototype.hide = function () {
    this.visibleCount = Math.max(0, this.visibleCount - 1);

    if (this.visibleCount === 0) {
        const el = document.querySelector('.modal-backdrop');
        if (el) el.remove();
    }
}

function ModalElement(id) {
    this.element = getElement(id);
    this.isVisible = false;
    this.previousFocus = null;
}

ModalElement.prototype.show = function () {
    if (!this.element || this.isVisible) return;

    this.element.classList.remove("hidden");
    this.element.classList.add("flex");
    this.previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    backdrop.show();
    this.isVisible = true;
    this.element.dispatchEvent(new CustomEvent('pos:modal:shown', {bubbles: true}));

    const initialFocus = this.element.querySelector(
        '[autofocus], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'
    );
    initialFocus?.focus();
}

ModalElement.prototype.hide = function () {
    if (!this.element || !this.isVisible) return;

    this.element.classList.remove("flex");
    this.element.classList.add("hidden");

    backdrop.hide();
    this.isVisible = false;
    this.element.dispatchEvent(new CustomEvent('pos:modal:hidden', {bubbles: true}));

    if (this.previousFocus?.isConnected) {
        this.previousFocus.focus();
    }
    this.previousFocus = null;
}

const backdrop = new BackDropElement();

let modals = {
    'session:close:modal': new ModalElement('session:close:modal'),
    'customer:search:modal': new ModalElement('customer:search:modal'),
    'document:type:modal': new ModalElement('document:type:modal'),
    loadingModal: new ModalElement('loadingModal'),

    'order:draft:list:modal': new ModalElement('order:draft:list:modal'),
    'order:last:list:modal': new ModalElement('order:last:list:modal'),

    paymentDetail: new ModalElement('paymentModal'),
    'context:action:modal': new ModalElement('context:action:modal'),

    'product:edit:modal': new ModalElement('product:edit:modal'),
    'product:image:modal': new ModalElement('product:image:modal'),
    productQuantityEdit: new ModalElement('productQuantityEditModal'),
    'product:stock:modal': new ModalElement('product:stock:modal'),

    'checkout:modal': new ModalElement('checkout:modal'),
    'return:sale:modal': new ModalElement('return:sale:modal'),
}

class Modals {
    constructor() {
        if (Modals._instance) {
            throw new Error("¡Ya existe una instancia de Modals!");
        }

        Modals._instance = this;
        this.modalCache = {...modals};
        this.currentModal = null;

        document.addEventListener('click', this._modalToggleEventHandler);
        document.addEventListener('keydown', this._escapeKeyEventHandler);
    }

    // Manejo automático al hacer clic en botones con data-toggle="modal"
    _modalToggleEventHandler = event => {
        const element = event.target.closest('[data-toggle]');
        if (!element) return;

        if (element.dataset.toggle === 'modal') {
            const modalId = element.dataset.target;
            this.toggleModal(modalId);

            //event.stopPropagation();
        }
    }

    _escapeKeyEventHandler = (event) => {
        if (event.key === 'Tab' && this.currentModal?.isVisible) {
            const focusable = Array.from(this.currentModal.element.querySelectorAll(
                'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'
            )).filter(element => !element.hidden && element.offsetParent !== null);
            if (!focusable.length) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (!this.currentModal.element.contains(document.activeElement)) {
                event.preventDefault();
                first.focus();
            } else if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
            return;
        }

        if (event.key === "Escape" || event.key === "Esc") {
            if (this.modalCache.loadingModal.isVisible) return;

            for (const modal of Object.values(this.modalCache)) {
                if (modal.isVisible) {
                    modal.hide();
                    break;
                }
            }
        }
    };

    toggleModal(modalId) {
        let modal = this.modalCache[modalId];

        // Si no existe en la caché, lo creamos y lo guardamos
        if (!modal) {
            modal = new ModalElement(modalId);
            this.modalCache[modalId] = modal;
        }

        // Si ya está abierto, simplemente lo cerramos
        if (modal.isVisible) {
            modal.hide();
            this.currentModal = null;
            return;
        }

        // Si hay otro modal abierto, lo cerramos antes
        if (this.currentModal && this.currentModal !== modal) {
            this.currentModal.hide();
        }

        // Mostramos el nuevo modal
        modal.show();
        this.currentModal = modal;
    }

    showModal(modalId) {
        let modal = this.modalCache[modalId];
        if (!modal) {
            modal = new ModalElement(modalId);
            this.modalCache[modalId] = modal;
        }

        if (this.currentModal && this.currentModal !== modal) {
            this.currentModal.hide();
        }

        modal.show();
        this.currentModal = modal;
    }

    hideModal(modalId) {
        const modal = this.modalCache[modalId];
        if (!modal) return;

        modal.hide();
        if (this.currentModal === modal) {
            this.currentModal = null;
        }
    }

    // Métodos de acceso directo a cada modal
    documentTypeModal = () => this.modalCache['document:type:modal'];
    closeSessionModal = () => this.modalCache['session:close:modal'];
    customerSearchModal = () => this.modalCache['customer:search:modal'];
    lastOrdersModal = () => this.modalCache['order:last:list:modal'];
    loadingModal = () => this.modalCache['loadingModal'];
    pausedOrdersModal = () => this.modalCache['order:draft:list:modal'];
    stockDetailModal = () => this.modalCache['product:stock:modal'];
    paymentModal = () => this.modalCache['paymentDetail'];
    contextActionModal = () => this.modalCache['context:action:modal'];
    productEditModal = () => this.modalCache['product:edit:modal'];
    productImagesModal = () => this.modalCache['product:image:modal'];
    productQuantityEditModal = () => this.modalCache['productQuantityEdit'];
    checkoutModal = () => this.modalCache['checkout:modal'];

    returnSaleModal = () => this.modalCache['return:sale:modal'];



    // Acceso al fondo
    backdrop() {
        return backdrop;
    }
}

// Exportamos una única instancia
const modalsInstance = new Modals();
export default modalsInstance;
