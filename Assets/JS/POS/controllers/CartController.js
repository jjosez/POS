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
     * Select a cart line using the provided element's dataset index'.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     */
    cartLineSelectionChange(el) {
        // Avoid selection if triggered by buttons/inputs
        if (el.closest('[data-stop="1"]')) return;

        const row = el.closest('.cart-line');
        if (!row) return;

        const index = Number(row.dataset.index);

        // Clear previous selection
        document
            .querySelectorAll('.cart-line[aria-selected="true"]')
            .forEach(r => r.setAttribute('aria-selected', 'false'));

        // Current cart line
        row.setAttribute('aria-selected', 'true');

        EventManager.emit('cart:line:select:request', index);
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
        //await CartController.cartChange();
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
        EventManager.emit('cart:line:select:request', index);
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
        EventManager.emit('cart:line:select:request', index);
    },

    /**
     * Update the agent code on the document.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.code - The codagente.
     * @property {string} el.dataset.description - The agent name.
     */
    setAgent(el) {
        const {code, description} = el.dataset;
        if (!code) return;

        Cart.setAgent(code);
        CartView.updateAgentLabel(description);
        CartView.toggleAgentSelectModal();
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
        /*
                const lastIndex = Cart.lines.length - 1;
                EventManager.emit('cart:line:selected', lastIndex);*/
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

        // Update agent label if agent is assigned
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
        audio.play().catch(() => {
        });
    },

    showLineToolbar(index) {
        const bar = document.getElementById('cartLineToolbar');
        const lbl = document.getElementById('cartLineToolbarIndex');
        if (!bar || !lbl) return;

        lbl.textContent = index + 1;
        bar.classList.remove('hidden');
    },

    async cartChange() {
        EventManager.emit('order:recalculate', this.Cart);
    },

    setSelectedIndex(index) {
        const idx = (index === null || index === undefined) ? null : Number(index);
        this.selectedIndex = Number.isFinite(idx) ? idx : null;

        // feedback inmediato (optimista) si existe en DOM
        this.applySelection();
    },

    applySelection() {
        if (this.selectedIndex === null) return;

        const row = document.querySelector(`.cart-line[data-index="${this.selectedIndex}"]`);
        if (!row) return; // todavía no existe en DOM (render pendiente)

        document
            .querySelectorAll('.cart-line[aria-selected="true"]')
            .forEach(r => r.setAttribute('aria-selected', 'false'));

        row.setAttribute('aria-selected', 'true');
        row.focus?.({preventScroll: true});

        // opcional: scroll
        row.scrollIntoView?.({behavior: 'smooth', block: 'nearest'});

        // toolbar
        this.showLineToolbar(this.selectedIndex);
    },

    init() {
        EventDispatcher.register('cart:product:delete', this.deleteProduct.bind(this));
        EventDispatcher.register('cart:product:edit', this.editProduct.bind(this));
        EventDispatcher.register('cart:product:quantity:edit', this.editProductQuantity.bind(this));
        EventDispatcher.register('cart:product:quantity:decrease', this.quantityDecrease.bind(this));
        EventDispatcher.register('cart:product:quantity:increase', this.quantityIncrease.bind(this));
        EventDispatcher.register('cart:product:add', this.addProduct.bind(this));
        EventDispatcher.register('cart:document:set', this.setDocument.bind(this));
        EventDispatcher.register('cart:agent:set', this.setAgent.bind(this));
        EventDispatcher.register('cart:line:selection:change', this.cartLineSelectionChange.bind(this));

        //eventManager.on('cart:changed', this.cartChange.bind(this));
        EventManager.on('product:scanned:success', this.addProduct.bind(this));
        EventManager.on('cart:updated', this.cartUpdateTotals.bind(this));
        EventManager.on('customer:changed', this.setCustomer.bind(this));
        EventManager.on('order:completed', this.resetDocument.bind(this));
        EventManager.on('order:resumed', (doc) => this.handleOrderResume(doc));
        EventManager.on('order:recalculated', (result) => {
            this.update(result);
        });


        let pendingSelectIndex = null;
        let applyingSelection = false;

        EventManager.on('cart:line:select:request', (index) => {
            pendingSelectIndex = Number(index);
        });

        EventManager.on('cart:rendered', () => {
            if (pendingSelectIndex === null) return;
            if (applyingSelection) return;

            const idx = Number(pendingSelectIndex);

            const row = document.querySelector(`.cart-line[data-index="${idx}"]`);
            if (!row) return;

            applyingSelection = true;
            try {
                // ahora sí: ya existe -> consumimos pending
                pendingSelectIndex = null;

                document
                    .querySelectorAll('.cart-line[aria-selected="true"]')
                    .forEach(r => r.setAttribute('aria-selected', 'false'));

                row.setAttribute('aria-selected', 'true');
                row.focus?.({preventScroll: true});
                row.scrollIntoView?.({behavior: 'smooth', block: 'nearest'});

                // toolbar aquí es más seguro (cuando ya se aplicó)
                this.showLineToolbar(idx);

                EventManager.emit('cart:line:selected', idx);
            } finally {
                applyingSelection = false;
            }
        });

        EventDispatcher.register('cart:line:toolbar:edit', () => {
            if (pendingSelectIndex == null) return;
            EventManager.emit('cart:line:edit', pendingSelectIndex);
        });

        EventDispatcher.register('cart:line:toolbar:delete', () => {
            if (pendingSelectIndex == null) return;
            EventManager.emit('cart:line:delete', pendingSelectIndex);
        });

        EventDispatcher.register('cart:line:toolbar:qty:increase', () => {
            if (pendingSelectIndex == null) return;
            EventManager.emit('cart:line:qty:increase', pendingSelectIndex);
        });

        EventDispatcher.register('cart:line:toolbar:qty:decrease', () => {
            if (pendingSelectIndex == null) return;
            EventManager.emit('cart:line:qty:decrease', pendingSelectIndex);
        });


        EventManager.on('cart:line:edit', (index) => {
            document.querySelector(`[data-action="cart:product:edit"][data-index="${index}"]`)?.click();
        });

        EventManager.on('cart:line:delete', (index) => {
            document.querySelector(`[data-action="cart:product:delete"][data-index="${index}"]`)?.click();
        });

        EventManager.on('cart:line:qty:increase', (index) => {
            document.querySelector(`[data-action="cart:product:quantity:increase"][data-index="${index}"]`)?.click();
        });

        EventManager.on('cart:line:qty:decrease', (index) => {
            document.querySelector(`[data-action="cart:product:quantity:decrease"][data-index="${index}"]`)?.click();
        });

        document.addEventListener('change', (event) => {
            const {action, documentField} = event.target.dataset;
            if (!action) return;

            if (action === 'document:field:edit') {
                CartController.editDocumentField(event.target);
            } else if (action === 'cart:product:field:edit') {
                CartController.editProductField(event.target, event.target.value);
            }
        });

        CartController.beepAudio = document.getElementById('beepAudio');
    }
};

export default CartController;
