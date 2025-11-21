import CartModel from '../models/CartModel.js';
import CartView from '../views/CartView.js';
import eventDispatcher from '../core/EventDispatcher.js';
import eventManager from '../core/EventManager.js';
import {recalculateRequest} from './OrderController.js';

const Cart = new CartModel({
    'doc': {
        'codserie': AppSettings.document.serie,
        'codalmacen': AppSettings.codalmacen,
        'codcliente': AppSettings.customer.codcliente,
        'idpausada': null,
        'tipo-documento': AppSettings.document.code,
        'codpago': AppSettings.payment.codpago
    }, 'token': AppSettings.token
});

let isCartVisible = false;

const CartController = {
    Cart,
    beepAudio: null,

    update(data) {
        Cart.update(data);
    },

    hasLines() {
        return this.Cart.lines.length > 0;
    },

    getState() {
        return this.Cart;
    },

    isVisible() {
        return isCartVisible;
    },

    isDraftOrder(code) {
        return parseInt(code, 10) === parseInt(Cart.doc.idpausada, 10);
    },

    /**
     * Deletes a product from the cart using the provided element's dataset index.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     */
    deleteProduct(el) {
        const {index} = el.dataset;
        Cart.deleteProduct(index);
    },

    /**
     * Opens the product edit modal for the specified product using the provided element's dataset index.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     */
    editProduct(el) {
        const {index} = el.dataset;
        const item = Cart.getProduct(index);
        CartView.showProductEditModal(item);
    },

    /**
     * Opens the quantity edit modal for the specified product using the provided element's dataset index.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     */
    editProductQuantity(el) {
        const {index} = el.dataset;
        const item = Cart.getProduct(index);
        CartView.showQuantityEditModal(item);
    },

    /**
     * Edit a product model field, and update the view.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     * @param {string} el.dataset.field - The specific field of the product to be updated (e.g., 'cantidad' for quantity).
     * @param {any} value - The new value to set for the specified product field (e.g., updated quantity or price).
     */
    async editProductField(el, value) {
        const {index, field} = el.dataset;
        await Cart.editProduct(index, field, value);
        await CartController.cartChange();
        CartView.renderCartEditView(Cart.getProduct(index));
    },

    /**
     * Decreases the quantity of the specified product by 1. If the quantity is already 0, it remains at 0.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     */
    quantityDecrease(el) {
        const {index} = el.dataset;
        const product = Cart.getProduct(index);
        const value = Math.max(product.cantidad - 1, 0);
        CartController.editProductField({dataset: {index, field: 'cantidad'}}, value);
    },


    /**
     * Increases the quantity of the specified product by 1.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     */
    quantityIncrease(el) {
        const {index} = el.dataset;
        const product = Cart.getProduct(index);
        const value = product.cantidad + 1;
        CartController.editProductField({dataset: {index, field: 'cantidad'}}, value);
    },

    /**
     * Update the customer code on the document.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     * @property {string} el.dataset.description - The index of the product to delete.
     */
    setCustomer(el) {
        const {code, description} = el.dataset;
        if (!code) return;

        Cart.setCustomer(code);
        CartView.updateCustomerNameLabel(description);
        CartView.toggleCustomerSearchModal();
    },

    /**
     * Sets the document type in the cart based on a DOM element's dataset.
     *
     * @param {HTMLElement} el - The DOM element containing dataset attributes for document type.
     * @param {Object} el.dataset
     * @param {string|null} el.dataset.code - The code of the document type. If null or empty, the default document is used.
     * @param {string} el.dataset.serie - The series associated with the document type.
     * @param {string} el.dataset.description - The description of the document type.
     */
    setDocument(el) {
        if (!el || !el.dataset) return;

        const {code, serie, description} = el.dataset;

        if (!code) {
            CartController.resetDocument();
            return;
        }

        Cart.updateDocumentType(code, serie);
        CartView.updateDocumentClassLabel(description);
        CartView.toggleDocumentClassSearchModal();
    },

    /**
     * Resets the cart document type to the default configured in AppSettings.
     */
    resetDocument() {
        Cart.setDocumentClass(AppSettings.document.code, AppSettings.document.serie);
        CartView.updateDocumentClassLabel(AppSettings.document.description);
    },

    /**
     * @param {HTMLElement} el - The DOM element that triggered the action.
     *
     * @property {string | null} el.dataset.code - The unique identifier for the product.
     * @property {string} el.dataset.description - The name or description of the product to be added.
     * @property {string} el.dataset.thumbnail - The URL or path to the product's thumbnail image.
     */
    addProduct(el) {
        const {code, description, thumbnail} = el.dataset;
        if (!code) return;

        Cart.setProduct(code, description, thumbnail);
        CartController.playBeepSound();
    },

    /**
     * Adds a product to the cart based on product data.
     *
     * @param {{ code: string, description: string, thumbnail?: string }} data - The product data.
     */
    addScannedProduct(data) {
        if (!data || !data.code) return;

        this.setProduct(data.code, data.description, data.thumbnail);
    },

    cartUpdateTotals(data) {
        CartView.updateTotals(data);
    },

    orderResume(doc) {
        const documentClass = AppSettings['supported-documents']
            .find(item => item.codserie === doc.codserie && item.tipodoc === doc.generadocumento);

        if (documentClass) {
            CartView.updateDocumentClassLabel(documentClass.descripcion);
        }

        Cart.updateDocumentClass();
    },

    editDocumentField(el) {
        const {documentField} = el.dataset;
        if (!documentField) return;

        if (el.type === 'checkbox') {
            Cart.setCustomField(documentField, el.checked ?? false);
        } else {
            Cart.setCustomField(documentField, el.value);
        }
    },

    playBeepSound() {
        const audio = CartController.beepAudio;
        if (!audio) return;

        audio.currentTime = 0;
        audio.play().catch(() => {});
    },

    async cartChange() {
        recalculateRequest(Cart).then(data => {
            Cart.update(data);
        });
    },

    init() {
        eventDispatcher.register('deleteProductAction', CartController.deleteProduct);
        eventDispatcher.register('editProductAction', CartController.editProduct);
        eventDispatcher.register('editProductQuantityAction', CartController.editProductQuantity);
        eventDispatcher.register('quantityDecreaseAction', CartController.quantityDecrease);
        eventDispatcher.register('quantityIncreaseAction', CartController.quantityIncrease);
        eventDispatcher.register('setCustomerAction', CartController.setCustomer);
        eventDispatcher.register('setDocumentAction', CartController.setDocument);
        eventDispatcher.register('setProductAction', CartController.addProduct);

        eventManager.on('onCartChange', CartController.cartChange);
        eventManager.on('onCartUpdate', CartController.cartUpdateTotals);
        eventManager.on('onCustomerChange', CartController.setCustomer);
        eventManager.on('onOrderComplete', CartController.resetDocument);
        eventManager.on('onOrderResume', (doc) => CartController.orderResume(doc));

        document.addEventListener('change', (event) => {
            const {action, documentField} = event.target.dataset;
            if (!action) return;

            if (action === 'edit-document-field') {
                CartController.editDocumentField(event.target);
            } else if (action === 'editProductFieldAction') {
                CartController.editProductField(event.target, event.target.value);
            }
        });

        CartController.beepAudio = document.getElementById('beepAudio');
    }
};

export default CartController;
