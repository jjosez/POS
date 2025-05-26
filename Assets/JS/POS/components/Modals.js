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

const backdrop = new BackDropElement();

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
        const target = event.target;

        if (target.dataset.toggle === 'modal') {
            const modalId = target.dataset.target;
            this.toggleModal(modalId);
            event.stopPropagation();
        }
    }

    _escapeKeyEventHandler = (event) => {
        if (event.key === "Escape" || event.key === "Esc") {
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

    // Métodos de acceso directo a cada modal
    documentTypeModal = () => this.modalCache['documentType'];
    closeSessionModal = () => this.modalCache['closeSession'];
    customerSearchModal = () => this.modalCache['customerSearch'];
    lastOrdersModal = () => this.modalCache['lastOrders'];
    loadingModal = () => this.modalCache['loadingModal'];
    pausedOrdersModal = () => this.modalCache['holdOrders'];
    stockDetailModal = () => this.modalCache['productStockDetail'];
    paymentModal = () => this.modalCache['paymentDetail'];
    printModal = () => this.modalCache['printModal'];
    productEditModal = () => this.modalCache['productEditModal'];
    productImagesModal = () => this.modalCache['productImages'];
    productQuantityEditModal = () => this.modalCache['productQuantityEdit'];
    checkoutModal = () => this.modalCache['checkoutModal'];

    // Acceso al fondo
    backdrop() {
        return backdrop;
    }
}

// Exportamos una única instancia
const modalsInstance = new Modals();
export default modalsInstance;
