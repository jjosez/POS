import CartModel from '../models/CartModel.js';
import CartView from '../views/CartView.js';
import EventDispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';

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
    selectedIndex: null,

    /**
     * Validates if the provided index is valid for the current cart lines
     * @param {number|string} index - The index to validate
     * @returns {boolean} True if the index is valid, false otherwise
     */
    validateIndex(index) {
        if (index === null || index === undefined) {
            return false;
        }
        const idx = Number(index);
        return !isNaN(idx) && idx >= 0 && idx < Cart.lines.length;
    },

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
     * @property {string | null} code - The unique identifier for the product.
     * @property {string} description - The name or description of the product to be added.
     * @property {string} thumbnail - The URL or path to the product's thumbnail image.
     * @param data
     */
    addLine(data) {
        const {code, description, thumbnail} = data;

        const freeLinesEnabled = !!AppSettings.cart.freeLines;
        const groupLinesEnabled = !!AppSettings.cart.groupLines;
        const isFreeLine = code === '';

        const forceNewLine = (isFreeLine && freeLinesEnabled) || !groupLinesEnabled;

        /* forceNewLine = true; // Always add a new line */
        if (forceNewLine) {
            Cart.addProduct(code, description, thumbnail);
        } else {
            Cart.addOrUpdateProduct(code, description, thumbnail);
        }

        CartController.playBeepSound();
        this.setSelectedIndex(0);
    },


    /**
     * Deletes a product from the cart using the provided element's dataset index.
     *
     * @param index - The index of the Cart line to delete.
     */
    deleteLine(index) {
        if (!this.validateIndex(index)) {
            console.error('[CartController] Invalid index for deleteLine:', index);
            return;
        }

        const idx = Number(index);
        Cart.deleteProduct(idx);

        if (!Cart.lines.length) {
            CartView.clearSelection();
            return;
        }

        const next = Math.min(idx, Cart.lines.length - 1);
        this.setSelectedIndex(next);
    },

    /**
     * Opens the product edit modal for the specified Cart line using the provided index.
     *
     * @param index - The index of the Cart line to edit.
     */
    editProduct(index) {
        if (!this.validateIndex(index)) {
            console.error('[CartController] Invalid index for editProduct:', index);
            return;
        }

        const item = Cart.getProduct(index);
        CartView.showProductEditModal(item);
    },

    /**
     * Opens the product edit quantity modal for the specified Cart line using the provided index.
     *
     * @param index - The index of the Cart line to edit.
     */
    editProductQuantity(index) {
        if (!this.validateIndex(index)) {
            console.error('[CartController] Invalid index for editProductQuantity:', index);
            return;
        }

        const item = Cart.getProduct(index);
        CartView.showQuantityEditModal(item);
    },

    /**
     * Edit a product model field and update the view.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     * @param {string} el.dataset.field - The specific field of the product to be updated (e.g., 'cantidad' for quantity).
     * @param {any} value - The new value to set for the specified product field (e.g., updated quantity or price).
     */
    async editProductField(el, value) {
        const {index, field} = el.dataset;
        await Cart.editProduct(index, field, value);
        //await CartController.cartChange();
        CartView.renderCartEditView(Cart.getProduct(index));
    },

    /**
     * Updates the quantity of a product line by a specified delta.
     * This is a helper method to avoid code duplication.
     *
     * @param {number|string} index - The index of the Cart line to update.
     * @param {number} delta - The amount to add (positive) or subtract (negative) from the quantity.
     */
    updateQuantity(index, delta) {
        if (!this.validateIndex(index)) {
            console.error('[CartController] Invalid index for updateQuantity:', index);
            return;
        }

        const product = Cart.getProduct(index);
        if (!product) {
            console.error('[CartController] Product not found at index:', index);
            return;
        }

        const value = Math.max(product.cantidad + delta, 0);
        CartController.editProductField({dataset: {index, field: 'cantidad'}}, value);
    },

    /**
     * Decreases the quantity of the specified Cart line using the provided index.
     *
     * @param index - The index of the Cart line to edit.
     */
    quantityDecrease(index) {
        this.updateQuantity(index, -1);
    },


    /**
     * Increase the quantity of the specified Cart line using the provided index.
     *
     * @param index - The index of the Cart line to edit.
     */
    quantityIncrease(index) {
        this.updateQuantity(index, 1);
    },

    /**
     * Update the customer code on the document.
     *
     * @param {code, description} customer
     * @property {string} customer.code - The codcliente.
     * @property {string} customer.description - The cliente name.
     */
    setCustomer(customer) {
        const {code, description} = customer;
        if (!code) return;

        Cart.setCustomer(code);
        CartView.updateCustomerNameLabel(description);
        CartView.toggleCustomerSearchModal();
    },

    /**
     * Resets the cart document type to the default configured in AppSettings.
     */
    resetDocument() {
        Cart.setDocumentClass(AppSettings.document.code, AppSettings.document.serie);
        CartView.updateDocumentClassLabel(AppSettings.document.description);
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

    handleOrderResume(doc) {
        const documentClass = AppSettings['supported-documents']
            .find(item => item.codserie === doc.codserie && item.tipodoc === doc.generadocumento);

        if (documentClass) {
            CartView.updateDocumentClassLabel(documentClass.descripcion);
        }

        if (doc.codagente) {
            const agent = AppSettings['agents']?.find(item => item.codagente === doc.codagente);
            if (agent) {
                CartView.updateAgentLabel(agent.nombre);
            }
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
        EventManager.emit('order:recalculate', this.Cart);
    },

    setSelectedIndex(index) {
        const idx = (index === null || index === undefined) ? null : Number(index);
        this.selectedIndex = Number.isFinite(idx) ? idx : null;

        this.applySelection();
    },

    applySelection() {
        if (!this.validateIndex(this.selectedIndex)) {
            CartView.clearSelection();
            return;
        }

        CartView.applySelection(this.selectedIndex);
    },

    /**
     * Deletes a cart line using the provided element's dataset index.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     */
    handleLineDelete(el) {
        const {index} = el.dataset;

        this.deleteLine(index);
    },

    /**
     * Edit a cart line using the provided element's dataset index.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the cart line to edit.
     */
    handleLineEdit(el) {
        const {index} = el.dataset;

        this.editProduct(index);
    },

    /**
     * Edit a cart line using the provided element's dataset index.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the cart line to edit.
     */
    handleLineQuantityEdit(el) {
        const {index} = el.dataset;

        this.editProductQuantity(index);
    },

    /**
     * Decreases the quantity of the specified product by 1. If the quantity is already 0, it remains at 0.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the cart line to decrease.
     */
    handleLineQuantityDecrease(el) {

        const {index} = el.dataset;

        this.quantityDecrease(index);
    },

    /**
     * Decreases the quantity of the specified product by 1. If the quantity is already 0, it remains at 0.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the cart line to decrease.
     */
    handleLineQuantityIncrease(el) {

        const {index} = el.dataset;

        this.quantityIncrease(index);
    },

    /**
     * @param {HTMLElement} el - The DOM element that triggered the action.
     *
     * @property {string | null} el.dataset.code - The unique identifier for the product.
     * @property {string} el.dataset.description - The name or description of the product to be added.
     * @property {string} el.dataset.thumbnail - The URL or path to the product's thumbnail image.
     */
    handleLineAdd(el) {
        const {code, description, thumbnail} = el.dataset;

        this.addLine({code, description, thumbnail});
    },

    /**
     * Update the agent code on the document.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.code - The codagente.
     * @property {string} el.dataset.description - The agent name.
     */
    handleSetAgent(el) {
        const {code, description} = el.dataset;
        if (!code) return;

        Cart.setAgent(code);
        CartView.updateAgentLabel(description);
        CartView.toggleAgentSelectModal();
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
    handleSetDocument(el) {
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
     * Select a cart line using the provided element's dataset index'.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     */
    handleLineSelectionChange(el) {
        // Avoid selection if triggered by buttons/inputs
        if (el.closest('[data-stop="1"]')) return;

        const row = el.closest('.cart-line');
        if (!row) return;

        this.setSelectedIndex(row.dataset.index);
    },


    init: function () {
        EventDispatcher.register('ui:cart:agent:set', this.handleSetAgent.bind(this));
        EventDispatcher.register('ui:cart:document:set', this.handleSetDocument.bind(this));
        EventDispatcher.register('ui:cart:line:add', this.handleLineAdd.bind(this));
        EventDispatcher.register('ui:cart:line:delete', this.handleLineDelete.bind(this));
        EventDispatcher.register('ui:cart:line:edit', this.handleLineEdit.bind(this));
        EventDispatcher.register('ui:cart:line:quantity:decrease', this.handleLineQuantityDecrease.bind(this));
        EventDispatcher.register('ui:cart:line:quantity:increase', this.handleLineQuantityIncrease.bind(this));
        EventDispatcher.register('ui:cart:line:selection:change', this.handleLineSelectionChange.bind(this));

        EventDispatcher.register('ui:cart:line:toolbar:edit', () => this.editProduct(this.selectedIndex));
        EventDispatcher.register('ui:cart:line:toolbar:delete', () => this.deleteLine(this.selectedIndex));
        EventDispatcher.register('ui:cart:line:toolbar:qty:increase', () => this.quantityIncrease(this.selectedIndex));
        EventDispatcher.register('ui:cart:line:toolbar:qty:decrease', () => this.quantityDecrease(this.selectedIndex));

        EventManager.on('event:cart:line:selected', (index) => this.setSelectedIndex(index));
        EventManager.on('event:cart:rendered', () => this.applySelection());
        EventManager.on('event:cart:updated', this.cartUpdateTotals.bind(this));
        EventManager.on('event:customer:changed', this.setCustomer.bind(this));
        EventManager.on('event:order:completed', this.resetDocument.bind(this));
        EventManager.on('event:order:resumed', (doc) => this.handleOrderResume(doc));
        EventManager.on('event:order:recalculated', (result) => this.update(result));
        EventManager.on('event:product:scanned', this.addLine.bind(this));
        EventManager.on('event:cart:line:edit', (index) => this.editProduct(index));
        EventManager.on('event:cart:line:delete', (index) => this.deleteLine(index));
        EventManager.on('event:cart:line:qty:increase', (index) => this.quantityIncrease(index));
        EventManager.on('event:cart:line:qty:decrease', (index) => this.quantityDecrease(index));

        document.addEventListener('change', (event) => {
            const {action, documentField} = event.target.dataset;
            if (!action) return;

            if (action === 'ui:order:field:edit') {
                CartController.editDocumentField(event.target);
            } else if (action === 'ui:cart:line:field:edit') {
                CartController.editProductField(event.target, event.target.value);
            }
        });

        CartController.beepAudio = CartView.beepAudio;
    }
};

export default CartController;
